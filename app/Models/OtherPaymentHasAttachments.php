<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class OtherPaymentHasAttachments extends Model
{
    protected $table = 'other_payment_has_attachments';
    protected $fillable = ['attachment', 'other_payment_id'];
    protected $appends = ['attachment_link'];
    public $timestamps = false;

    public function other_payment()
    {
        return $this->hasOne(OtherPayment::class, 'id', 'other_payment_id');
    }

    public function getAttachmentLinkAttribute()
    {
        if (isset($this->attributes['attachment'])) {
            $attachment = $this->attributes['attachment'];
            return Storage::disk('s3')->temporaryUrl("attachment-payment-request-installment/{$attachment}", now()->addMinutes(30));
        }
    }
}
