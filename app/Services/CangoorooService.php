<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Cangooroo;

class CangoorooService
{

    private $cangooroo;

    public function __construct(Cangooroo $cangooroo)
    {
        $this->cangooroo = $cangooroo;
    }

    public function updateCangoorooData($bookingId)
    {
        $response = Http::post(env('CANGOOROO_URL'), [
            'Credential' => [
                "Username" => env('CANGOOROO_USERNAME'),
                "Password" => env('CANGOOROO_PASSWORD'),
            ],
            'BookingId' => $bookingId,
        ])->throw()->json()['BookingDetail'];

        $room = $response['Rooms'][0];

        $supplierName = null;
        foreach ($room['CustomFields'] as $customField) {
            if($customField['Name'] == 'SupplierName')
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
            "agency_name" => $room['CreationUserDetail']['AgencyName'],
            "creation_user" => $room['CreationUserDetail']['Name'],
            "selling_price" => $room['SellingPrice']['Value'],
        ];

        $cangooroo = $this->cangooroo->where('booking_id',$bookingId)->first('id');
        $this->cangooroo->findOrFail($cangooroo['id'])->fill($data)->save();
    }
}
