<?php

namespace App\Models;

class Cangooroo
{
    private array $guests;
    private String $service_id;
    private String $supplier_reservation_code;
    private String $status;
    private String $reservation_date;
    private String $check_in;
    private String $check_out;
    private int $number_of_nights;
    private String $supplier_hotel_id;
    private String $hotel_id;
    private String $hotel_name;
    private String $city_name;
    private String $agency_name;
    private String $creation_user;
    private float $selling_price;

    function __construct(array $guests, String $serviceId, String $supplierReservationCode, String $status, String $reservationDate, String $checkIn, String $checkOut, int $numberOfNights, String $supplierHotelId, String $hotelId, String $hotelName, String $cityName, String $agencyName, String $creation_user, float $sellingPrice)
    {
        $this->guests = $guests;
        $this->service_id = $serviceId;
        $this->supplier_reservation_code = $supplierReservationCode;
        $this->status = $status;
        $this->reservation_date = $reservationDate;
        $this->check_in = $checkIn;
        $this->check_out = $checkOut;
        $this->number_of_nights = $numberOfNights;
        $this->supplier_hotel_id = $supplierHotelId;
        $this->hotel_id = $hotelId;
        $this->hotel_name = $hotelName;
        $this->city_name = $cityName;
        $this->agency_name = $agencyName;
        $this->creation_user = $creation_user;
        $this->selling_price = $sellingPrice;
    }
    public function expose()
    {
        return get_object_vars($this);
    }
}
