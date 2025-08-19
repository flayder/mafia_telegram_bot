<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NightFunction extends Model
{
    use HasFactory;
    protected $table = "night_functions";
    public static $entityLabel="NightFunction";
    protected $fillable = ['id','game_id','night','func_name','priority'];
    public static $func_array = [];
    protected static $testUnic = [];
    public static function labels() {
        return ['id'=>'id','game_id'=>'game_id','night'=>'night','func_name'=>'func_name'];
    }
    public static function viewFields() {
        return ['id','game_id','night','func_name'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public static function push_func(Game $game, string $func, $priority = 100) {
        if(!in_array($func,self::$testUnic)) { //чтоб добавить только 1 раз. но не гарантирует единичное попадание в БД. Поэтому firstOrCreate
            $obj = self::firstOrCreate(['game_id'=>$game->id,'night'=>$game->current_night,'func_name'=>$func, 'priority'=>$priority ]);        
            self::$testUnic[] = $func;
            self::$func_array[] = $obj;
        }
    }
    public static function load_funcs($game) {
        self::$func_array = self::where(['game_id'=>$game->id,'night'=>$game->current_night])->
        orderBy('priority')->get()->all();  
        self::$testUnic = array_column(self::$func_array, 'func_name');  
    }
    public static function shift_func() {  //читаем массив ф-й как очередь
        if(self::$func_array) {
            return array_shift(self::$func_array);
        }
        return null;
    }
}