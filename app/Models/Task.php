<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $table = "tasks";
    public static $entityLabel="Task";
    protected $fillable = ['id','name','is_active','options','delay','game_id'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','is_active'=>'Активен','options'=>'Опции','delay'=>'Задержка (сек)','game_id'=>'Игра'];
    }
    public static function viewFields() {
        return ['id','name','is_active','game_id'];
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