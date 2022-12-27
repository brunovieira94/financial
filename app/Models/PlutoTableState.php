<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlutoTableState extends Model
{
    protected $table = 'pluto_table_state';
    protected $fillable = ['user_id', 'name', 'route'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function columns_states()
    {
        return $this->hasMany(PlutoTableStateHasColumn::class, 'pluto_table_state_id', 'id');
    }
}
