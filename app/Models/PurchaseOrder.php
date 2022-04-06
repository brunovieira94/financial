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
    protected static $logAttributes = ['cost_centers', 'attachments', 'services', 'products', '*'];
    protected static $logName = 'purchase_orders';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='purchase_orders';
    protected $fillable = ['order_type', 'provider_id', 'currency_id', 'exchange_rate', 'billing_date', 'payment_condition', 'observations', 'percentage_discount_services', 'money_discount_services', 'percentage_discount_products', 'money_discount_products', 'increase_tolerance', 'unique_product_discount'];
    protected $hidden = ['currency_id', 'provider_id'];

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

    public static function boot() {
        parent::boot();
        self::deleting(function($attachments) {
            $attachments->attachments()->delete();
        });
    }
}
