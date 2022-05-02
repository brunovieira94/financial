<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabGenerated extends Model
{
    protected $table='cnab_generated';
    public $timestamps = false;
    protected $fillable = ['user_id', 'user_id', 'file_date', 'status', 'file_name'];
    protected $hidden = ['pivot'];
    protected $appends = ['cnab_link'];

    public function getCnabLinkAttribute()
    {
        if (!is_null($this->attributes['file_name'])) {
            $fileName = $this->attributes['file_name'];
            return Storage::disk('s3')->temporaryUrl("tempCNAB/{$fileName}", now()->addMinutes(5));
        }
    }

    public function payment_requests()
    {
        return $this->hasMany(CnabGeneratedHasPaymentRequests::class, 'cnab_generated_id', 'id')->with(['installments_cnab', 'payment_request']);
    }
}
