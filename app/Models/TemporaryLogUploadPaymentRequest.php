<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryLogUploadPaymentRequest extends Model
{
    protected $table='temporary_log_upload_payment_request';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'error', 'folder'];
    protected $hidden = ['payment_request_id'];
}
