<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PurchaseOrder extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['installments', 'cost_centers', 'attachments', 'services', 'products', 'purchase_requests', 'company', 'currency', 'provider', 'user', '*'];
    protected static $logName = 'purchase_orders';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $user->role = Role::findOrFail($user->role_id);
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table = 'purchase_orders';
    protected $fillable = ['user_id', 'order_type', 'provider_id', 'currency_id', 'exchange_rate', 'billing_date', 'payment_condition', 'observations', 'percentage_discount_services', 'money_discount_services', 'percentage_discount_products', 'money_discount_products', 'increase_tolerance', 'unique_product_discount', 'frequency_of_installments', 'installments_quantity', 'unique_discount', 'initial_date', 'company_id'];
    protected $hidden = ['currency_id', 'provider_id', 'user_id', 'company_id'];
    protected $appends = ['applicant_can_edit', 'approver_stage'];

    public function attachments()
    {
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

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
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

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($attachments) {
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


    public function getApproverStageAttribute()
    {
        $approverStage = [];
        if (SupplyApprovalFlow::where('id_purchase_order', $this->id)->exists()) {
            $approvalId = SupplyApprovalFlow::where('id_purchase_order', $this->id)->first();
            $roles = ApprovalFlowSupply::where('order', $approvalId->order)->with('role')->get();
            $costCenters = PurchaseOrderHasCostCenters::where('purchase_order_id', $this->id)->get();
            $costCenterId = null;
            $maxPercentage = 0;
            $constCenterEqual = false;
            foreach ($costCenters as $costCenter) {
                if ($costCenter->percentage > $maxPercentage) {
                    $costCenterId = $costCenter->cost_center_id;
                    $maxPercentage = $costCenter->percentage;
                } else if ($costCenter->percentage == $maxPercentage) {
                    $constCenterEqual = true;
                    $maxPercentage = $costCenter->percentage;
                }
            }

            foreach ($roles as $role) {
                if ($role->role->id != 1) {
                    $checkUser = User::where('role_id', $role->role->id)->with('cost_center')->orderby('name')->get();
                    $names = [];
                    foreach ($checkUser as $user) {
                        if ($constCenterEqual == false) {
                            foreach ($user->cost_center as $userCostCenter) {
                                if ($userCostCenter->id == $costCenterId) {
                                    $names[] = $user->name;
                                }
                            }
                        } else {
                            $names[] = $user->name;
                        }
                    }
                    $approverStage[] = [
                        'title' => $role->role->title,
                        'name' => count($names) > 0 ? $names[0] : '',
                        'names' => $names,
                    ];
                }
            }
            return $approverStage;
        } else {
            return $approverStage;
        }
    }
}
