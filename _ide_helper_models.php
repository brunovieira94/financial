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
 * App\Models\Hotel
 *
 * @property int $id
 * @property string $id_hotel_cangooroo
 * @property string|null $id_hotel_omnibees
 * @property string|null $hotel_name
 * @property string|null $chain
 * @property string|null $email
 * @property string|null $email_omnibees
 * @property string|null $phone
 * @property int|null $billing_type
 * @property int|null $bank_account_id
 * @property string|null $holder_full_name
 * @property string|null $cpf_cnpj
 * @property int $is_valid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $cnpj_hotel
 * @property string|null $observations
 * @property int|null $form_of_payment
 * @property-read \Illuminate\Database\Eloquent\Collection|\Spatie\Activitylog\Models\Activity[] $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BankAccount[] $bank_account
 * @property-read int|null $bank_account_count
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel newQuery()
 * @method static \Illuminate\Database\Query\Builder|Hotel onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel query()
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereBankAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereBillingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereChain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereCnpjHotel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereCpfCnpj($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereEmailOmnibees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereFormOfPayment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereHolderFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereHotelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereIdHotelCangooroo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereIdHotelOmnibees($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereIsValid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereObservations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hotel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Hotel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Hotel withoutTrashed()
 */
	class Hotel extends \Eloquent {}
}

