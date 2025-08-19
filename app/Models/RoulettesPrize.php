<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoulettesPrize extends Model
{
    use HasFactory;
    protected $table = "roulettes_prizes";
    public static $entityLabel="RoulettesPrize";
    protected $fillable = ['id','name','percent','season','prize_type','add_function'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','percent'=>'Вероятность %','season'=>'Сезон','add_function'=>'Функция выдачи','prize_type'=>'Тип приза'];
    }
    public static function viewFields() {
        return ['id','name','percent','season','add_function'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function __toString()
    {
        return $this->name;
    }
}