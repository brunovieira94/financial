<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormPayment extends Model
{
    protected $table='form_payment';
    protected $fillable = ['title','code_cnab','bank_code'];
}
