<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use SoftDeletes;
    protected $table='bank_accounts';
    protected $fillable = ['agency_number', 'agency_check_number', 'account_number', 'account_check_number', 'bank_id'];
}
