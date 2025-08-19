<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameRolesOrder extends Model
{
    use HasFactory;
    protected $table = "game_roles_order";
    public static $entityLabel="GameRolesOrder";
    protected $fillable = ['id','role_id','position','gamers_min','gamers_max'];
    public static function labels() {
        return ['id'=>'id','role_id'=>'Роль','role'=>'Роль','position'=>'Порядок/номер','gamers_min'=>'Мин. игроков','gamers_max'=>'Макс. игроков'];
    }
    public static function viewFields() {
        return ['id','role','position','gamers_min','gamers_max'];
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
}