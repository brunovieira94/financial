<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BankAccount;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;

class Bank extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'banks';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $user = auth()->user();
        $activity->causer_id = $user->id;
        $activity->causer_object = $user;
    }

    use SoftDeletes;
    protected $table='banks';
    protected $fillable = ['title','cnab400','cnab240', 'bank_code'];
    protected $appends = ['linked_accounts'];

    public function getLinkedAccountsAttribute()
    {
        return $this->hasMany(BankAccount::class, 'bank_id', 'id')->count();
    }

    public function bankAccount(){
        return $this->hasMany(BankAccount::class, 'bank_id', 'id');
    }

    //delete relationship
    public static function boot() {
        parent::boot();
        self::deleting(function($bankAccount) {
            $bankAccount->bankAccount()->delete();
        });
    }

    public function form_payment()
    {
        return $this->hasMany(FormPayment::class, 'bank_code', 'bank_code')->where('group_form_payment_id', '!=', null)->with('group_payment');
    }
}
