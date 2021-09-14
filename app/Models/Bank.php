<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BankAccount;

class Bank extends Model
{
    use SoftDeletes;
    protected $table='banks';
    protected $fillable = ['title'];
    protected $appends = ['linked_accounts'];

    public function getLinkedAccountsAttribute()
    {
        return $this->hasMany(BankAccount::class, 'bank_id', 'id')->count();
    }
}



