<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasServices extends Model
{
    protected $table='purchase_order_has_services';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'service_id', 'unitary_value', 'initial_date', 'end_date', 'automatic_renovation', 'notice_time_to_renew', 'percentage_discount', 'money_discount', 'frequency_of_installments', 'installments_quantity', 'unique_discount', 'contract_time', 'contract_frequency'];
    protected $hidden = ['purchase_order_id', 'service_id'];
    protected $appends = ['end_contract_date'];

    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }

    public function installments()
    {
        return $this->hasMany(PurchaseOrderServicesHasInstallments::class, 'po_services_id', 'id');
    }

    public function getEndContractDateAttribute()
    {
        if($this->contract_frequency == 0){
            return date('Y-m-d', strtotime("+".$this->contract_time." days", strtotime($this->initial_date)));
        }
        if($this->contract_frequency == 1){
            return date('Y-m-d', strtotime("+".$this->contract_time." months", strtotime($this->initial_date)));
        }
        if($this->contract_frequency == 2){
            return date('Y-m-d', strtotime("+".$this->contract_time." years", strtotime($this->initial_date)));
        }
    }
}
