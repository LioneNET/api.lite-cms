<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $table = 'position';
    protected $guarded = [];
    
    public function pos_items() {
        return $this->hasMany(PosItems::class, 'position_id', 'id');
    }
}
