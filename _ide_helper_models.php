<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * App\Models\Cangooroo
 *
 * @property int $id
 * @property int $booking_id
 * @property string|null $guests
 * @property string|null $service_id
 * @property string|null $supplier_reservation_code
 * @property string|null $status
 * @property string|null $reservation_date
 * @property string|null $check_in
 * @property string|null $check_out
 * @property int|null $number_of_nights
 * @property string|null $supplier_hotel_id
 * @property string|null $hotel_id
 * @property string|null $hotel_name
 * @property string|null $city_name
 * @property string|null $agency_name
 * @property string|null $creation_user
 * @property string|null $123_id
 * @property string|null $supplier_name
 * @property string|null $cancellation_policies_start_date
 * @property float|null $cancellation_policies_value
 * @property float|null $selling_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Hotel|null $hotel
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo newQuery()
 * @method static \Illuminate\Database\Query\Builder|Cangooroo onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo query()
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo where123Id($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereAgencyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereBookingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCancellationPoliciesStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCancellationPoliciesValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCheckIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCheckOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCityName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereCreationUser($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereGuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereHotelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereHotelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereNumberOfNights($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereReservationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereSellingPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereSupplierHotelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereSupplierName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereSupplierReservationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Cangooroo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Cangooroo withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Cangooroo withoutTrashed()
 */
	class Cangooroo extends \Eloquent {}
}

