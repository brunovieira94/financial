<?php

namespace App\Models;

use App\Scopes\ProfileCostCenterScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabGeneratedHasPaymentRequests extends Model
{
    protected $table='cnab_generated_has_payment_requests';
    public $timestamps = false;
    protected $fillable = ['cnab_generated_id', 'payment_request_id'];
    protected $hidden = ['pivot', 'cnab_generated_id', 'id', 'payment_request_id'];


    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['provider', 'company', 'bank_account_provider', ])->withoutGlobalScope(ProfileCostCenterScope::class);
    }

    public function installments_cnab()
    {
        return $this->hasMany(CnabPaymentRequestsHasInstallments::class, 'cnab_generated_has_payment_requests_id', 'id')->with('installment');
    }
}
