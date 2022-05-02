<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabPaymentRequestsHasInstallments extends Model
{
    protected $table='cnab_payment_requests_has_installments';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'installment_id', 'cnab_generated_id'];
    protected $hidden = ['pivot'];

}
