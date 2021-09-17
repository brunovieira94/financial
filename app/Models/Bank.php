<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BankAccount;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class Bank extends Model
{
    // Logs
    use LogsActivity;
    protected static $logAttributes = ['title','cnab400','cnab240'];
    protected static $logName = 'banks';
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = 1;
    }

    use SoftDeletes;
    protected $table='banks';
    protected $fillable = ['title','cnab400','cnab240'];
    protected $appends = ['linked_accounts'];

    public function getLinkedAccountsAttribute()
    {
        return $this->hasMany(BankAccount::class, 'bank_id', 'id')->count();
    }
}



