<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttachmentReport extends Model
{
    use SoftDeletes;
    protected $table = 'attachment_reports';
    protected $fillable = ['link', 'path', 'mails', 'to', 'from', 'status', 'error', 'user_id', 'title'];


    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id')->with(['role'])->withTrashed();
    }
}
