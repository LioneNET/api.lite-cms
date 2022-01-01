<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = 'material';
    protected $guarded = [];

    public function category(){
        return $this->belongsTo(Category::class, "category_id", "id");
    }

    public function section() {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function tag(){
        return $this->belongsTo("App\Models\Tag", "tag_id", "id");
    }
    
    //Форматирование даты в момент вызова
    public function getCreatedAtAttribute($value) {
        $month = [1 => "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря"];
        $strtotime = strtotime($value);
        return date("d", $strtotime)." ".$month[intval(date("m", $strtotime))]." ".date("Y H:i", $strtotime);
    }
}
