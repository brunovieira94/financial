<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class PurchaseRequest extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['cost_centers', 'companies', 'attachments', 'services', 'products', '*'];
    protected static $logName = 'purchase_requests';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='purchase_requests';
    protected $fillable = ['request_type', 'observations', 'status'];
    protected $hidden = ['currency_id'];

    public function attachments(){
        return $this->hasMany(PurchaseRequestHasAttachments::class, 'purchase_request_id', 'id');
    }

    public function cost_centers()
    {
        return $this->hasMany(PurchaseRequestHasCostCenters::class, 'purchase_request_id', 'id')->with('cost_center');
    }

    public function services()
    {
        return $this->hasMany(PurchaseRequestHasServices::class, 'purchase_request_id', 'id')->with(['service']);
    }

    public function products()
    {
        return $this->hasMany(PurchaseRequestHasProducts::class, 'purchase_request_id', 'id')->with('product');
    }

    public function companies()
    {
        return $this->hasMany(PurchaseRequestHasCompanies::class, 'purchase_request_id', 'id')->with('company');
    }

    public static function boot() {
        parent::boot();
        self::deleting(function($attachments) {
            $attachments->attachments()->delete();
        });
    }
}
