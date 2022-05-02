<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabGeneratedHasPaymentRequests extends Model
{
    protected $table='cnab_generated_has_payment_requests';
    public $timestamps = false;
    protected $fillable = ['cnab_generated_id', 'payment_request_id'];
    protected $hidden = ['pivot', 'cnab_generated_id'];

    public function installments()
    {
        return $this->hasMany(CnabPaymentRequestsHasInstallments::class, 'payment_request_id', 'payment_request_id');
    }
}
