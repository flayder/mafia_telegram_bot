<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupTarif extends Model
{
    use HasFactory;
    protected $table = "group_tarifs";
    public static $entityLabel="GroupTarif";
    protected $fillable = ['id','name','max_gamer_count','role_ids','price','reward'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','max_gamer_count'=>'Кол-во игроков','role_ids'=>'Роли','price'=>'Цена','reward'=>'Награда %'];
    }
    public static function viewFields() {
        return ['id','name','max_gamer_count','price','reward'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    protected static function boot()
    {
        parent::boot();
        
        # Проверка данных пользователя перед сохранением
        static::saving(
            function($model) {
                if(is_string($model->role_ids) &&  empty(trim($model->role_ids))) $model->role_ids = null;
            }
        );        
    }
    public function __toString()
    {
        return $this->name;
    }
    public static function clearTarif(BotGroup $grp) { //сбраcывает тариф на базовый и удаляют настройку по кол-ву пользователей
        $grp->tarif_id = 1;
        $tarif = GroupTarif::where('id',1)->first();
        $grp->reward = $tarif->reward;
        $grp->save();
        $set = Setting::where('set_key','gamers_count')->where('group_id',$grp->id)->first();
        if($set) $set->delete();        
    }
     
}