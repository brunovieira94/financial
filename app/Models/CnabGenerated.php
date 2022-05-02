<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabGenerated extends Model
{
    protected $table='cnab_generated';
    public $timestamps = false;
    protected $fillable = ['user_id', 'user_id', 'file_date', 'status', 'file_name'];
    protected $hidden = ['pivot'];

    public function payment_requests()
    {
        return $this->hasMany(CnabPaymentRequestsHasInstallments::class, 'cnab_generated_id', 'id');
    }

}
