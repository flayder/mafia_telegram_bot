<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Baf extends Model
{
    use HasFactory;
    protected $table = "bafs";
    public static $entityLabel="Baf";
    protected $fillable = ['id','name','description','price','cur_code','baf_class','assign_role_ids'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','description'=>'Описание','price'=>'Цена','cur_code'=>'Валюта','baf_class'=>'Класс',
    'assign_role_ids'=>'ID связанных ролей'];
    }
    public static function viewFields() {
        return ['id','name','baf_class','price','cur_code'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function switchStatus($setBafList) {
        if($setBafList === 'all') return 1;
        if(is_array($setBafList) && isset($setBafList[$this->id])) return $setBafList[$this->id];
        return 1;
    }
    public function __toString()
    {
        return $this->name;
    }
}