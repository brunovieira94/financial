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
        $this->getCangoorooData($billing['reserve']);
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

        $cangooroo = $this->billing->findOrFail($bookingId);
        $cangooroo->fill([
            "bookingId" => $bookingId,
            "guests" => array_map(
                fn ($e) => $e['Name'] . ' ' . $e['Surname'],
                $response['Paxs']
            ),
            "service_id" => $response['ServiceId'],
            "supplier_reservation_code" => $response['SupplierReservationCode'],
            "status" => $response['Status'],
            "reservation_date" => $response['ReservationDate'],
            "check_in" => $response['CheckIn'],
            "check_out" => $response['CheckOut'],
            "number_of_nights" => $response['NumberOfNights'],
            "supplier_hotel_id" => $response['SupplierHotelId'],
            "hotel_id" => $response['HotelId'],
            "hotel_name" => $response['HotelName'],
            "city_name" => $response['CityName'],
            "agency_name" => $response['CreationUserDetail']['AgencyName'],
            "creation_user" => $response['CreationUserDetail']['Name'],
            "selling_price" => $response['SellingPrice']['Value'],
        ])->save();
        return $cangooroo;
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
