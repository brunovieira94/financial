<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class UserHasPaymentRequest extends Model
{
    protected $table = 'user_has_payment_request';
    public $timestamps = false;
    protected $fillable = ['user_id', 'payment_request_id', 'status'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->withoutGlobalScopes();
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
