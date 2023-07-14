<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BankAccount;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\LogOptions;

class Export extends Model
{
    use SoftDeletes;
    protected $table='export';
    protected $fillable = ['status','link','user_id', 'path', 'name', 'extension', 'test', 'error'];

    public function user(){
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
