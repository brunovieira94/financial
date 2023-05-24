<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProviderHasAttachments extends Model
{
    protected $table='provider_has_attachments';
    public $timestamps = false;
    protected $fillable = ['provider_id', 'attachment'];
    protected $hidden = ['provider_id'];
    protected $appends = ['attachment_link'];

    public function getAttachmentLinkAttribute()
    {
        if (!is_null($this->attributes['attachment'])) {
            $attachment = $this->attributes['attachment'];
            return Storage::disk('s3')->temporaryUrl("attachment/{$attachment}", now()->addMinutes(30));
        }
    }
}
