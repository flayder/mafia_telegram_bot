<?php

namespace App\Models;

use App\Modules\Game\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyRole extends Model
{
    use HasFactory;
    protected $table = "buy_roles";
    public static $entityLabel="BuyRole";
    protected $fillable = ['id','role_id','price','cur_code'];
    public static function labels() {
        return ['id'=>'id','role_id'=>'Роль','role'=>'Роль','price'=>'Цена','cur_code'=>'Валюта'];
    }
    public static function viewFields() {
        return ['id','role','price','cur_code'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function role() {
        return $this->hasOne(GameRole::class,'id','role_id');
    }
    public function getRoleWithPriceAttribute() {
        $curs = Currency::allCurrencies();
        return ''.$this->role." - ".$this->price.$curs[$this->cur_code];
    }
}