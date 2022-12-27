<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlutoTableStateHasColumn extends Model
{
    protected $table = 'pluto_table_state_has_columns';
    protected $fillable = ['pluto_table_state_id', 'field', 'width', 'position', 'fixed', 'visible', 'sort', 'sort_attribute'];

    public function pluto_table_state()
    {
        return $this->hasOne(PlutoTableState::class, 'id', 'pluto_table_state_id');
    }
}
