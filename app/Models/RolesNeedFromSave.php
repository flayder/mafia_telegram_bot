<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolesNeedFromSave extends Model
{
    use HasFactory;
    protected $table = "roles_need_from_save";
    public static $entityLabel="RolesNeedFromSave";
    protected $fillable = ['id','role_id','saved_role_id'];
    public static function labels() {
        return ['id'=>'id','role_id'=>'Нападающая роль','saved_role_id'=>'Спасающая роль',
        'role'=>'Нападающая роль','saved_role'=>'Спасающая роль',
        'night_command'=>'Ночная команда'];
    }
    public static function viewFields() {
        return ['id','role','saved_role'];
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
    public function saved_role() {
        return $this->hasOne(GameRole::class,'id','saved_role_id');
    }
}