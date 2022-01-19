<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasServices extends Model
{
    protected $table='purchase_order_has_services';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'service_id', 'unitary_value', 'initial_date', 'end_date', 'automatic_renovation', 'notice_time_to_renew', 'percentage_discount', 'money_discount', 'frequency_of_installments', 'contract_duration', 'unique_discount'];
    protected $hidden = ['purchase_order_id', 'service_id'];

    public function service()
    {
        return $this->hasOne(Service::class, 'id', 'service_id');
    }

    public function installments()
    {
        return $this->hasMany(PurchaseOrderServicesHasInstallments::class, 'po_services_id', 'id');
    }
}
