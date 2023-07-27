<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLog extends Model
{
    protected $table = 'approval_log';
    protected $fillable = ['user_id', 'request', 'real_approval'];
    protected $casts = [
        'request' => 'json',
        'real_approval' => 'json'
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
