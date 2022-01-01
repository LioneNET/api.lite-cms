<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;
    protected $table='section';
    protected $guarded = [];

    public function materials() {
        return $this->hasMany('App\Models\Topic', 'section_id', 'id');
    }
}
