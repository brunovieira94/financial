<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PurchaseOrder extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['installments', 'cost_centers', 'attachments', 'services', 'products', 'purchase_requests', 'companies', 'currency', 'provider', 'user', '*'];
    protected static $logName = 'purchase_orders';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='purchase_orders';
    protected $fillable = ['user_id','order_type', 'provider_id', 'currency_id', 'exchange_rate', 'billing_date', 'payment_condition', 'observations', 'percentage_discount_services', 'money_discount_services', 'percentage_discount_products', 'money_discount_products', 'increase_tolerance', 'unique_product_discount', 'frequency_of_installments', 'installments_quantity', 'unique_discount', 'initial_date'];
    protected $hidden = ['currency_id', 'provider_id', 'user_id'];
    protected $appends = ['applicant_can_edit'];

    public function attachments(){
        return $this->hasMany(PurchaseOrderHasAttachments::class, 'purchase_order_id', 'id');
    }

    public function cost_centers()
    {
        return $this->hasMany(PurchaseOrderHasCostCenters::class, 'purchase_order_id', 'id')->with('cost_center');
    }

    public function purchase_requests()
    {
        return $this->hasMany(PurchaseOrderHasPurchaseRequests::class, 'purchase_order_id', 'id')->with('purchase_request');
    }

    public function services()
    {
        return $this->hasMany(PurchaseOrderHasServices::class, 'purchase_order_id', 'id')->with(['service', 'installments']);
    }

    public function products()
    {
        return $this->hasMany(PurchaseOrderHasProducts::class, 'purchase_order_id', 'id')->with('product');
    }

    public function companies()
    {
        return $this->hasMany(PurchaseOrderHasCompanies::class, 'purchase_order_id', 'id')->with('company');
    }

    public function currency()
    {
        return $this->hasOne(Currency::class, 'id', 'currency_id');
    }

    public function provider()
    {
        return $this->hasOne(Provider::class, 'id', 'provider_id');
    }

    public function approval()
    {
        return $this->hasOne(SupplyApprovalFlow::class, 'id_purchase_order', 'id')->with('approval_flow');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function installments()
    {
        return $this->hasMany(PurchaseOrderHasInstallments::class, 'purchase_order_id', 'id');
    }

    public static function boot() {
        parent::boot();
        self::deleting(function($attachments) {
            $attachments->attachments()->delete();
        });
    }

    public function getApplicantCanEditAttribute()
    {
        if (isset($this->approval)) {
            if ($this->approval->order == 1 && $this->approval->status == 0) {
                return true;
            } else if ($this->approval->order == 0) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}
