<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PosItems extends Model
{
    protected static $store = null;
    protected $table = 'pos_items';
    protected $guarded = [];
    public $timestamps = false;

    public function positiable()
    {
        return $this->morphTo();
    }

    public function position()
    {
        return $this->belongsTo(Position::class, "position_id", "id");
    }

    protected static function booted()
    {
        //установить связаный список после задания позиции для объекта
        static::created(function ($model) {
            $item = PosItems::where('position_id', $model->position_id)->where('next', null)->first();
            if ($item && $item->id != $model->id) {
                $item->next = $model->id;
                $model->prev = $item->id;
                $item->save();
                $model->save();
            }
        });

        static::deleting(function ($model) {
            //изьять меню из позиций
            $nodePrev = null;
            $nodeNext = null;
            $node = $model->where('id', $model->id)->first();
            if ($node['prev']) {
                $nodePrev = $model->where('id', $node['prev'])->first();
            }
            if ($node['next']) {
                $nodeNext = $model->where('id', $node['next'])->first();
            }
            if ($nodePrev) {
                $nodePrev->update([
                    'next' => $nodeNext['id'] ?? null
                ]);
            }
            if ($nodeNext) {
                $nodeNext->update([
                    'prev' => $nodePrev['id'] ?? null
                ]);
            }
        });
        
        static::saving(function ($model) {
            if (self::$store == null) {
                $node = $model->where('id', $model->id)->first();
                $last_next_id = $model->where('position_id', $model->position_id)
                    ->where('next', null)
                    ->first();
                self::$store = [
                    'last_position_id' => $node->position_id ?? $model->position_id,
                    'new_position_id' => $model->position_id ?? $model->position_id,
                    'last_next_id' => $last_next_id->id ?? null
                ];
            } else {
                self::$store = 'done';
            }
        });

        static::saved(function ($model) {
            //если изменили позицию
            if (self::$store != 'done' && self::$store['last_position_id'] != self::$store['new_position_id']) {
                $last_next_id = self::$store['last_next_id'];
                //изьять меню из позиций
                $nodePrev = null;
                $nodeNext = null;
                $node = $model->where('id', $model->id)->first();
                if ($node['prev']) {
                    $nodePrev = $model->where('id', $node['prev'])->first();
                }
                if ($node['next']) {
                    $nodeNext = $model->where('id', $node['next'])->first();
                }
                if ($nodePrev) {
                    $nodePrev->update([
                        'next' => $nodeNext['id'] ?? null
                    ]);
                }
                if ($nodeNext) {
                    $nodeNext->update([
                        'prev' => $nodePrev['id'] ?? null
                    ]);
                }

                //последняя нода из новой позиции
                if ($last_next_id) {
                    $nodeLast = $model->where('id', $last_next_id)->first();
                    $nodeLast->update([
                        'next' => $node->id
                    ]);
                    $node->update([
                        'prev' => $nodeLast->id,
                        'next' => null
                    ]);
                } else {
                    $node->update([
                        'prev' => null,
                        'next' => null
                    ]);
                }
            }
            //при перемещении по уровню

        });
    }
}
