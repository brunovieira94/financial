<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnabGenerated extends Model
{
    protected $table='cnab_generated';
    public $timestamps = false;
    protected $fillable = ['archive_return', 'header_date', 'header_time', 'user_id', 'user_id', 'file_date', 'status', 'file_name', 'company_id', 'bank_account_company_id'];
    protected $hidden = ['pivot'];
    protected $appends = ['cnab_link', 'cnab_return_link'];

    public function getCnabLinkAttribute()
    {
        if (!is_null($this->attributes['file_name'])) {
            $fileName = $this->attributes['file_name'];
            return Storage::disk('s3')->temporaryUrl("tempCNAB/{$fileName}", now()->addMinutes(30));
        }
    }

    public function payment_requests()
    {
        return $this->hasMany(CnabGeneratedHasPaymentRequests::class, 'cnab_generated_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function bank_account_company()
    {
        return $this->hasOne(BankAccount::class, 'id', 'bank_account_company_id');
    }

    public function getCnabReturnLinkAttribute()
    {
        if (!is_null($this->attributes['archive_return'])) {
            $fileName = $this->attributes['archive_return'];
            return Storage::disk('s3')->temporaryUrl("tempCNAB/{$fileName}", now()->addMinutes(30));
        }
    }

}
