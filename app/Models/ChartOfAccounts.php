<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccounts extends Model
{
    use SoftDeletes;
    protected $table='chart_of_accounts';
    protected $fillable = ['title', 'parent', 'cost_center_id'];
}
