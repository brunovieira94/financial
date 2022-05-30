<?php

namespace App\Services;


use App\Models\Billing;
use Illuminate\Support\Facades\Http;
use App\Models\Cangooroo;

class BillingService
{

    private $billing;

    public function __construct(Billing $billing)
    {
        $this->billing = $billing;
    }

    public function getAllBilling($requestInfo)
    {
        $billing = Utils::search($this->billing, $requestInfo);
        return Utils::pagination($billing, $requestInfo);
    }

    public function getBilling($id)
    {
        $billing = $this->billing->findOrFail($id);
        $cagooroo = json_encode($this->getCangoorooData($billing['bookingId'])->expose());
        $billing['cangooroo'] = $cagooroo;
        return $billing;
    }

    public function getCangoorooData(String $bookingId): Cangooroo
    {
        $response = Http::post(env('CANGOOROO_URL'), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME'),
                "Password" => env('CANGOOROO_PASSWORD'),
            ],
            'BookingId' => $bookingId,
        ])->throw()->json()['BookingDetail']['Rooms'][0];

        return new Cangooroo(
            guests: array_map(
                fn ($e) => $e['Name'] . ' ' . $e['Surname'],
                $response['Paxs']
            ),
            serviceId: $response['ServiceId'],
            supplierReservationCode: $response['SupplierReservationCode'],
            status: $response['Status'],
            reservationDate: $response['ReservationDate'],
            checkIn: $response['CheckIn'],
            checkOut: $response['CheckOut'],
            numberOfNights: $response['NumberOfNights'],
            supplierHotelId: $response['SupplierHotelId'],
            hotelId: $response['HotelId'],
            hotelName: $response['HotelName'],
            cityName: $response['CityName'],
            agencyName: $response['CreationUserDetail']['AgencyName'],
            creation_user: $response['CreationUserDetail']['Name'],
            sellingPrice: $response['SellingPrice']['Value'],
        );
    }

    public function postBilling($billingInfo)
    {
        $billing = new Billing;
        return $billing->create($billingInfo);
    }

    public function putBilling($id, $billingInfo)
    {
        $billing = $this->billing->findOrFail($id);
        $billing->fill($billingInfo)->save();
        return $billing;
    }

    public function deleteBilling($id)
    {
        $this->billing->findOrFail($id)->delete();
        return true;
    }
}
