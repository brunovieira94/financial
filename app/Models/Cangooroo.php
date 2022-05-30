<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Cangooroo extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'billing';
    public function tapActivity(Activity $activity)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    // Model attributes
    use SoftDeletes;
    protected $table = 'billing';
    protected $fillable = [
        'booking_id',
        'guests',
        'service_id',
        'supplier_reservation_code',
        'status',
        'reservation_date',
        'check_in',
        'check_out',
        'number_of_nights',
        'supplier_hotel_id',
        'hotel_id',
        'hotel_name',
        'city_name',
        'agency_name',
        'creation_user',
        'selling_price',
    ];
    // private String $booking_id;
    // private array $guests;
    // private String $service_id;
    // private String $supplier_reservation_code;
    // private String $status;
    // private String $reservation_date;
    // private String $check_in;
    // private String $check_out;
    // private int $number_of_nights;
    // private String $supplier_hotel_id;
    // private String $hotel_id;
    // private String $hotel_name;
    // private String $city_name;
    // private String $agency_name;
    // private String $creation_user;
    // private float $selling_price;

    // function __construct(String $bookingId, array $guests, String $serviceId, String $supplierReservationCode, String $status, String $reservationDate, String $checkIn, String $checkOut, int $numberOfNights, String $supplierHotelId, String $hotelId, String $hotelName, String $cityName, String $agencyName, String $creation_user, float $sellingPrice)
    // {
    //     $this->booking_id = $bookingId;
    //     $this->guests = $guests;
    //     $this->service_id = $serviceId;
    //     $this->supplier_reservation_code = $supplierReservationCode;
    //     $this->status = $status;
    //     $this->reservation_date = $reservationDate;
    //     $this->check_in = $checkIn;
    //     $this->check_out = $checkOut;
    //     $this->number_of_nights = $numberOfNights;
    //     $this->supplier_hotel_id = $supplierHotelId;
    //     $this->hotel_id = $hotelId;
    //     $this->hotel_name = $hotelName;
    //     $this->city_name = $cityName;
    //     $this->agency_name = $agencyName;
    //     $this->creation_user = $creation_user;
    //     $this->selling_price = $sellingPrice;
    // }
    // public function expose()
    // {
    //     return get_object_vars($this);
    // }
}
