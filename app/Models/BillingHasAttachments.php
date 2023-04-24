<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BillingHasAttachments extends Model
{
    protected $table='billing_has_attachments';
    public $timestamps = false;
    protected $fillable = ['billing_id', 'attachment'];
    protected $hidden = ['billing_id'];
    protected $appends = ['attachment_link'];

    public function getAttachmentLinkAttribute()
    {
        if (!is_null($this->attributes['attachment'])) {
            $attachment = $this->attributes['attachment'];
            return Storage::disk('s3')->temporaryUrl("attachment/{$attachment}", now()->addMinutes(30));
        }
    }
}
