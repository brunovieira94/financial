<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupFormPayment extends Model
{
    protected $table='group_form_payment';
    protected $fillable = ['title'];


    public function form_payment()
    {
        return $this->hasMany(FormPayment::class, 'group_form_payment_id', 'id');
    }
}
