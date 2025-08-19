<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameRole extends Model
{
    use HasFactory;
    protected $table = "game_roles";
    public static $entityLabel="GameRole";
    protected $fillable = ['id','name','max_amount_in_game','description','comment','first_message','kill_message','is_select_partner','night_message_priv','night_message_publ','message_to_partner','role_type_id','night_action'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','max_amount_in_game'=>'Макс. кол-во в игре','description'=>'Описание','comment'=>'Комментарий','first_message'=>'Первое сообщение','kill_message'=>'Сообщение при убийстве',
        'is_select_partner'=>'Есть партнер','night_message_priv'=>'Ночное сообщение в ЛС','night_message_publ'=>'Ночное сообщение в группу','message_to_partner'=>'Сообщение партнеру','role_type_id'=>'Тип роли','role_type'=>'Тип роли'];
    }
    public static function viewFields() {
        return ['id','name','max_amount_in_game','role_type'];
    }
    public function role_type() {
        return $this->hasOne(RoleType::class,'id','role_type_id');
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
    public function switchStatus($setRoleList) {
        if($setRoleList === 'all') return 1;
        if(is_array($setRoleList) && isset($setRoleList[$this->id])) return $setRoleList[$this->id];
        return 1;
    }
}