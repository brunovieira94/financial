<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;

class AttachmentLogDownload extends Model
{
    use SoftDeletes;
    protected $table = 'attachment_download_log';
    protected $fillable = [
        'archive',
        'payment_request_id',
        'error',
    ];
}
