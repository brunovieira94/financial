<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalFlow extends Model
{
    protected $table='approval_flow';
    protected $fillable = ['parent_role_id','role_id'];
    public $timestamps = false;
}
