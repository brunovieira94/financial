<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRequestHasInstallments extends Model
{
    protected $table='payment_requests_installments';
    public $timestamps = false;
    protected $fillable = ['percentage_discount', 'initial_value', 'discount', 'fees', 'extension_date', 'competence_date', 'parcel_number', 'payment_request_id', 'due_date', 'note', 'portion_amount', 'status', 'status', 'amount_received'];

    public function payment_request()
    {
        return $this->hasOne(PaymentRequest::class, 'id', 'payment_request_id')->with(['provider', 'company', 'purchase_order', 'group_payment', 'attachments', 'approval', 'bank_account_provider', 'business', 'cost_center', 'chart_of_accounts', 'currency', 'user', 'tax']);
    }
}
