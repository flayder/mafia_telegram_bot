<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;
    protected $table = "currency_rate";
    public static $entityLabel="CurrencyRate";
    protected $fillable = ['id','base_cur','calc_cur','rate'];
    public static function labels() {
        return ['id'=>'id','base_cur'=>'base_cur','calc_cur'=>'calc_cur','rate'=>'rate'];
    }
    public static function viewFields() {
        return ['id','base_cur','calc_cur','rate'];
    }
    public static function calcCurrencySum($baseCur,$baseSum,$calcCur) {
        $rate = self::where(['base_cur'=>$baseCur,'calc_cur'=>$calcCur])->first();
        if(!$rate) return 0;
        $calcSum = $baseSum * $rate->rate;
        return $calcSum;
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