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

    public function updateCangoorooData($reserve)
    {
        $bookingId = $this->getCangoorooBookingIDData($reserve);
        if(!$bookingId) return ['error' => 'Código de reserva inválido'];
        $apiCall = Http::post(env('CANGOOROO_URL', "http://123milhas.cangooroo.net/API/REST/CangoorooBackOffice.svc/GetBookingDetail"), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME', "Backoffice_Financeiro_IN8"),
                "Password" => env('CANGOOROO_PASSWORD', "zS2HMrhk2TbwmYxM"),
            ],
            'BookingId' => $bookingId,
        ]);
        if ($apiCall->status() == 400) return (object) [];
        $response = $apiCall->json()['BookingDetail'];
        //dd($apiCall->json());

        $roomIndex = null;
        $possibleRooms = [];
        foreach ($response['Rooms'] as $key => $room) {
            array_push($possibleRooms, explode('-', $room['SupplierReservationCode'])[0]);
            if (strpos($room['SupplierReservationCode'], $reserve) !== false && strlen($reserve) > 4) {
                $roomIndex = $key;
            }
        }
        if ($roomIndex === null) {
            return ['error' => 'Dados de reserva inválidos. Possíveis números de reserva para esse  Id: ' . implode(', ', $possibleRooms)];
        }

        $room = $response['Rooms'][$roomIndex];

        $supplierName = null;
        foreach ($room['CustomFields'] as $customField) {
            if ($customField['Name'] == 'SupplierName')
                $supplierName = $customField['Value'];
        }

        $data =
            [
                "booking_id" => $bookingId,
                "guests" => join(", ", array_map(
                    fn ($e) => $e['Name'] . ' ' . $e['Surname'],
                    $room['Paxs']
                )),
                "service_id" => $room['ServiceId'],
                "supplier_reservation_code" => $room['SupplierReservationCode'],
                "status" => $room['Status'],
                "reservation_date" => $room['ReservationDate'],
                "check_in" => $room['CheckIn'],
                "check_out" => $room['CheckOut'],
                "cancellation_policies_start_date" => $room['CancellationPolicies'][0]['StartDate'],
                "cancellation_policies_value" => $room['CancellationPolicies'][0]['Value']['Value'],
                "number_of_nights" => $room['NumberOfNights'],
                "supplier_hotel_id" => $room['SupplierHotelId'],
                "hotel_id" => $room['HotelId'],
                "hotel_name" => $room['HotelName'],
                "city_name" => $room['CityName'],
                "123_id" => $response['ControlNumber'],
                "supplier_name" => $supplierName,
                "agency_name" => array_key_exists('AgencyName', $room['CreationUserDetail']) ? $room['CreationUserDetail']['AgencyName'] : $room['CreationUserDetail']['Name'],
                "creation_user" => $room['CreationUserDetail']['Name'],
                "selling_price" => $room['SellingPrice']['Value'],
            ];

        if (!Hotel::where('id_hotel_cangooroo', $data['hotel_id'])->first()) {
            return ['error' => 'Hotel não cadastrado na base de dados. Id_hotel_cangooroo: ' . $data['hotel_id']];
        }

        $cangooroo = $this->cangooroo->where('booking_id', $bookingId)->first('id');
        if ($cangooroo) {
            $updatedCangooroo = $this->cangooroo->findOrFail($cangooroo['id']);
            $updatedCangooroo->fill($data)->save();
            return $this->cangooroo->with('hotel')->findOrFail($cangooroo['id']);
        }
        $cangooroo = new Cangooroo();
        $cangooroo = $cangooroo->create($data);
        return $cangooroo;
    }

    public function getCangoorooBookingIDData($reserve)
    {
        $apiCall = Http::post(env('CANGOOROO_BOOKING_LIST_URL', "http://123milhas.cangooroo.net/API/REST/CangoorooBackOffice.svc/GetBookingList"), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME', "Backoffice_Financeiro_IN8"),
                "Password" => env('CANGOOROO_PASSWORD', "zS2HMrhk2TbwmYxM"),
            ],
            'SearchBookingCriteria' => [
                "SupplierLoc" => $reserve
            ]
        ]);
        if (array_key_exists('Error', $apiCall->json()) && $apiCall->json()['Error']['Message'] == 'Object reference not set to an instance of an object.') return $this->getCangoorooBookingIDData($reserve);
        if ($apiCall->status() == 400 || $apiCall->json()['TotalResults'] < 1) return false;
        $response = $apiCall->json()['Reservations'];
        return $response[0]['BookingId'];
    }
}
