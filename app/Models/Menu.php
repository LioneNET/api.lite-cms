<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    private static $store = null;
    protected $table = 'menu';
    protected $guarded = [];
    public $timestamps = false;
    public static $title = "Меню";

    public function pos_items() {
        return $this->morphMany(PosItems::class, "positiable");
    }

    public function position() {
        return $this->belongsTo(Position::class, "position_id", "id");
    }

    protected static function booted()
    {
        
        //создать зависимость #1
        static::created(function ($model) {
            self::$store = self::$store ?? 'created';
            if($model->id_parent == null) {
                $model->pos_items()->create([
                    'position_id' => $model->position_id,
                    'positiable_id'=> $model->id,
                ]);
            }
        });
        
        //удалить зависимость
        static::deleted(function ($model) {
            $pos_items = $model->pos_items;
            if(count($pos_items)) {
                $model->pos_items[0]->delete($model->id);
            }
        });
        
        
        //обновить зависимость #2
        static::saved(function ($model) {
            $pos_items = $model->pos_items;
            if(self::$store == null && count($pos_items)) {
                $model->pos_items[0]->position_id = $model->position_id;
                $model->pos_items[0]->save();
            }
        });
    }
}
