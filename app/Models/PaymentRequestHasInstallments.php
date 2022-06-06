<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentRequestHasInstallments extends Model
{
    protected $table = 'payment_requests_installments';
    public $timestamps = false;
    protected $fillable = ['type_billet', 'billet_file', 'fine', 'billet_number', 'bar_code', 'group_form_payment_id', 'bank_account_provider_id', 'percentage_discount', 'initial_value', 'discount', 'fees', 'extension_date', 'competence_date', 'parcel_number', 'payment_request_id', 'due_date', 'note', 'portion_amount', 'status', 'status', 'amount_received'];
    protected $appends = ['billet_link'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['provider', 'company', 'purchase_order', 'group_payment', 'attachments', 'approval', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax']);
    }

    public function group_payment()
    {
        return $this->hasOne(GroupFormPayment::class, 'id', 'group_form_payment_id')->with(['form_payment']);
    }

    public function bank_account_provider()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_provider_id')->with('bank');
    }

    public function getBilletLinkAttribute()
    {
        if (!is_null($this->attributes['billet_file'])) {
            $billet = $this->attributes['billet_file'];
            return Storage::disk('s3')->temporaryUrl("billet/{$billet}", now()->addMinutes(30));
        }
    }
}
