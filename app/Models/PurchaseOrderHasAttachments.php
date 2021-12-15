<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderHasAttachments extends Model
{
    protected $table='purchase_order_has_attachments';
    public $timestamps = false;
    protected $fillable = ['purchase_order_id', 'attachment'];
    protected $hidden = ['purchase_order_id'];
}
