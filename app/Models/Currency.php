<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    protected $table = "currencies";
    public static $entityLabel="Currency";
    protected $fillable = ['id','name','code','chat_command','is_season'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','code'=>'Код','chat_command'=>'Команда в чат','is_season'=>'Сезонная'];
    }
    public static function viewFields() {
        return ['id','name','code','chat_command','is_season'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
}