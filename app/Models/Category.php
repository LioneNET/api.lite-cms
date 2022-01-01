<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $guarded = [];
    public $timestamps = false;

    public function materials(){
        return $this->hasMany(Topic::class, "category_id", "id");
    }
}
