<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Cangooroo;
use App\Models\Hotel;

class CangoorooService
{

    private $cangooroo;

    public function __construct(Cangooroo $cangooroo)
    {
        $this->cangooroo = $cangooroo;
    }

    public function updateCangoorooData($reserve, $bookingId = null, $serviceId = null)
    {
        $bookingId = $bookingId ? $bookingId : $this->getCangoorooBookingIDData($reserve);
        if(is_array($bookingId)) return $bookingId;
        if (!$bookingId) return ['error' => 'Código de reserva inválido'];
        $apiCall = Http::post(env('CANGOOROO_URL', "http://123milhas.cangooroo.net/API/REST/CangoorooBackOffice.svc/GetBookingDetail"), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME', "Backoffice_Financeiro_IN8"),
                "Password" => env('CANGOOROO_PASSWORD', "zS2HMrhk2TbwmYxM"),
            ],
            'BookingId' => $bookingId,
        ]);
        if ($apiCall->status() == 400) return (object) [];
        $response = $apiCall->json()['BookingDetail'];

        $roomIndex = null;
        $possibleRooms = [];
        foreach ($response['Rooms'] as $key => $room) {
            array_push($possibleRooms, explode('-', $room['SupplierReservationCode'])[0]);
            if (strpos($room['SupplierReservationCode'], $reserve) !== false && strlen($reserve) > 4) {
                if($serviceId){
                    if($serviceId == $room['ServiceId']) $roomIndex = $key;
                }
                else{
                    $roomIndex = $key;
                }

            }
        }
        if ($roomIndex === null) {
            return ['error' => 'Dados de reserva inválidos. Possíveis números de reserva para esse bookingId: ' . implode(', ', $possibleRooms)];
        }

        $room = $response['Rooms'][$roomIndex];

        $supplierName = null;
        $sellingPrice = null;
        $isVcn = 0;
        $hasUsdConvertion = false;
        $convertion = 1;
        foreach ($room['CustomFields'] as $customField) {
            if ($customField['Name'] == 'SupplierName')
                $supplierName = $customField['Value'];
            if ($customField['Name'] == 'SupplierPrice.Price')
            {
                if (mb_strpos($customField['Value'], ',') !== false)
                {
                    $sellingPrice = floatval(str_replace(",", ".",$customField['Value']));
                }
                else{
                    $sellingPrice = floatval($customField['Value']);
                }
            }
            if ($customField['Name'] == 'SupplierVCNpayment')
                $isVcn = $customField['Value'] == 'true' ? 1 : 0;
            if ($customField['Name'] == 'SupplierTax.Currency' && $customField['Value'] == "USD")
                $hasUsdConvertion = true;
        }

        if($hasUsdConvertion){
            foreach ($room['CustomFields'] as $customField) {
                if ($customField['Name'] == 'Currency')
                    $convertion = floatval(explode("}",explode('ConvertionValue":',$customField['Value'])[1])[0]);
            }
            $sellingPrice = $sellingPrice * $convertion;
        }

        $lastUpdate = explode("-0300", explode("Date(", $response['LastUpdate'])[1])[0];

        $isInvalidContract = $this->invalidateCangoorooContract($response, $room);
        if($isInvalidContract) return ['error' => 'Contrato inválido. Campo ausente na API Cangooroo: ' . $isInvalidContract];

        $data =
            [
                "booking_id" => $bookingId,
                "guests" => array_key_exists('Paxs', $room) ? join(", ", array_map(
                    fn ($e) => $e['Name'] . ' ' . $e['Surname'],
                    $room['Paxs']
                )) : '',
                "service_id" => intval($room['ServiceId']),
                "supplier_reservation_code" => $room['SupplierReservationCode'],
                "status" => $room['Status'],
                "reservation_date" => $room['ReservationDate'],
                "check_in" => $room['CheckIn'],
                "check_out" => $room['CheckOut'],
                "cancellation_policies_start_date" => $room['CancellationPolicies'][0]['StartDate'],
                "cancellation_policies_value" => $room['CancellationPolicies'][0]['Value']['Value'],
                "cancellation_date" => $room['CancellationDate'],
                "last_update" => date('Y-m-d H:i:s', $lastUpdate*0.001),
                "provider_name" => $room['ProviderName'],
                "number_of_nights" => $room['NumberOfNights'],
                "supplier_hotel_id" => $room['SupplierHotelId'],
                "hotel_id" => $room['HotelId'],
                "hotel_name" => $room['HotelName'],
                "city_name" => $room['CityName'],
                "123_id" => $response['ControlNumber'],
                "client_name" => $response['ClientName'],
                "supplier_name" => $supplierName,
                "agency_name" => array_key_exists('AgencyName', $room['CreationUserDetail']) ? $room['CreationUserDetail']['AgencyName'] : $room['CreationUserDetail']['Name'],
                "creation_user" => $room['CreationUserDetail']['Name'],
                "selling_price" => $sellingPrice,
                "is_vcn" => $isVcn,
            ];

        if (!Hotel::where('id_hotel_cangooroo', $data['hotel_id'])->first()) return ['invalid_hotel' => 'Hotel não cadastrado na base de dados. Id_hotel_cangooroo: ' . $data['hotel_id'], 'id_hotel_cangooroo' => $data['hotel_id']];

        $cangooroo = $this->cangooroo->where('service_id', $data['service_id'])->first('id');
        if ($cangooroo) {
            $updatedCangooroo = $this->cangooroo->findOrFail($cangooroo['id']);
            $updatedCangooroo->fill($data)->save();
            $cangoorooToReturn = $this->cangooroo->with('hotel')->findOrFail($cangooroo['id']);
            $cangoorooToReturn['multiples_services'] = [$cangoorooToReturn['service_id']];
            $cangoorooToReturn['payment_status'] = BillingService::getPaymentStatus($cangoorooToReturn);
            $cangoorooToReturn['status_123'] = BillingService::get123Status($cangoorooToReturn);
            return $cangoorooToReturn;
        }
        $cangooroo = new Cangooroo();
        $cangooroo = $cangooroo->create($data);
        $cangoorooToReturn = $this->cangooroo->with('hotel')->findOrFail($cangooroo['id']);
        $cangoorooToReturn['multiples_services'] = [$cangoorooToReturn['service_id']];
        $cangoorooToReturn['payment_status'] = BillingService::getPaymentStatus($cangoorooToReturn);
        $cangoorooToReturn['status_123'] = BillingService::get123Status($cangoorooToReturn);
        return $cangoorooToReturn;
    }

    public function getCangoorooBookingIDData($reserve, $retrys = 5)
    {
        $retrys--;
        $apiCall = Http::post(env('CANGOOROO_BOOKING_LIST_URL', "http://123milhas.cangooroo.net/API/REST/CangoorooBackOffice.svc/GetBookingList"), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME', "Backoffice_Financeiro_IN8"),
                "Password" => env('CANGOOROO_PASSWORD', "zS2HMrhk2TbwmYxM"),
            ],
            'SearchBookingCriteria' => [
                "SupplierLoc" => $reserve
            ]
        ]);
        if ($retrys > 0 && array_key_exists('Error', $apiCall->json()) && $apiCall->json()['Error']['Message'] == 'Object reference not set to an instance of an object.') return $this->getCangoorooBookingIDData($reserve, $retrys);
        if ($apiCall->status() == 400 || $apiCall->json()['TotalResults'] < 1) return false;
        if ($apiCall->json()['TotalResults'] > 1){
            $serviceIds = [];
            foreach ($apiCall->json()['Reservations'] as $key => $reservation) {
                array_push($serviceIds, $reservation['ServiceId']);
            }
            return [
                'booking_id' => $apiCall->json()['Reservations'][0]['BookingId'],
                'multiples_services' => $serviceIds
            ];
        }
        return $apiCall->json()['Reservations'][0]['BookingId'];
    }

    public function invalidateCangoorooContract($response, $room)
    {
        if(!array_key_exists('ServiceId', $room)) return 'ServiceId';
        if(!array_key_exists('SupplierReservationCode', $room)) return 'SupplierReservationCode';
        if(!array_key_exists('Status', $room)) return 'Status';
        if(!array_key_exists('ReservationDate', $room)) return 'ReservationDate';
        if(!array_key_exists('CheckIn', $room)) return 'CheckIn';
        if(!array_key_exists('CheckOut', $room)) return 'CheckOut';
        if(!array_key_exists('CancellationDate', $room)) return 'CancellationDate';
        if(!array_key_exists('ProviderName', $room)) return 'ProviderName';
        if(!array_key_exists('NumberOfNights', $room)) return 'NumberOfNights';
        if(!array_key_exists('SupplierHotelId', $room)) return 'SupplierHotelId';
        if(!array_key_exists('HotelId', $room)) return 'HotelId';
        if(!array_key_exists('HotelName', $room)) return 'HotelName';
        if(!array_key_exists('CityName', $room)) return 'CityName';
        if(!array_key_exists('ControlNumber', $response)) return 'ControlNumber';
        if(!array_key_exists('CancellationPolicies', $room) || !array_key_exists(0, $room['CancellationPolicies']) || !array_key_exists('StartDate', $room['CancellationPolicies'][0])) return 'CancellationPoliciesStartDate';
        if(!array_key_exists('CancellationPolicies', $room) || !array_key_exists(0, $room['CancellationPolicies']) || !array_key_exists('Value', $room['CancellationPolicies'][0])) return 'CancellationPoliciesValue';
        if(!array_key_exists('CreationUserDetail', $room) || !array_key_exists('Name', $room['CreationUserDetail'])) return 'CreationUserDetailName';
        return false;
    }
}
