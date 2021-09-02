<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;
    protected $table='bank_accounts';
<<<<<<< HEAD
    protected $fillable = ['agency_number', 'agency_check_number', 'account_number', 'account_check_number', 'bank_id'];
=======
>>>>>>> b63e0ebf4354fa82953f2598b3c6263b11850d84
}
