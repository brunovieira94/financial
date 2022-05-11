<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentRequestHasAttachments extends Model
{
    protected $table='payment_request_has_attachments';
    public $timestamps = false;
    protected $fillable = ['payment_request_id', 'attachment'];
    protected $hidden = ['payment_request_id'];
    protected $appends = ['attachment_link'];

    public function getAttachmentLinkAttribute()
    {
        if (!is_null($this->attributes['attachment'])) {
            $attachment = $this->attributes['attachment'];
            return Storage::disk('s3')->temporaryUrl("attachment-payment-request/{$attachment}", now()->addMinutes(30));
        }
    }
}
