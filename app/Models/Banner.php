<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banner';
    protected $guarded = [];
    public $timestamps = false;
    public static $title = "Банер";

    public function pos_items() {
        return $this->morphMany(PosItems::class, "positiable");
    }

    public function position() {
        return $this->belongsTo(Position::class, "position_id", "id");
    }

    protected static function booted()
    {
        //создать зависимость
        static::created(function ($model) {
            $model->pos_items()->create([
                'position_id' => $model->position_id,
                'positiable_id'=> $model->id,
            ]);
        });

        //обновить зависимость
        static::saved(function ($model) {
            $model->pos_items[0]->position_id = $model->position_id;
            $model->pos_items[0]->save();
        });
    }
}
