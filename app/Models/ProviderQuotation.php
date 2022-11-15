<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class ProviderQuotation extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['user', '*'];
    protected static $logName = 'provider_quotations';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'provider_quotations';
    protected $casts = [
        'request_ids' => 'array',
        'services_list_export' => 'array',
    ];
    protected $fillable = ['user_id', 'status', 'request_ids', 'services_list_export', 'company_id'];
    protected $hidden = ['user_id', 'company_id'];
    protected $appends = ['applicant_can_edit', 'purchase_orders'];

    public function quotation_items()
    {
        return $this->hasMany(ProviderQuotationItems::class, 'provider_quotation_id', 'id')->with('products', 'services', 'provider');
    }

    public function cost_centers()
    {
        return $this->hasMany(ProviderQuotationHasCostCenters::class, 'provider_quotation_id', 'id')->with('cost_center');
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

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function getPurchaseOrdersAttribute()
    {
        $purchaseOrdersIDs = [];
        foreach (PurchaseOrder::where('quotation_id', $this->id)->get() as $purchaseorder) {
            $purchaseOrdersIDs[] = [
                'id' => $purchaseorder->id
            ];
        }
        return $purchaseOrdersIDs;
    }
}
