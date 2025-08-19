<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SleepKillRole extends Model
{
    use HasFactory;
    protected $table = "sleep_kill_roles";
    public static $entityLabel="SleepKillRole";
    protected $fillable = ['id','role_id','need_commands','test_nights_count','is_one'];
    public static function labels() {
        return ['id'=>'ID','role_id'=>'Роль','role'=>'Роль','need_commands'=>'Требуемые команды','test_nights_count'=>'После скольки ночей убивать','is_one'=>'Раз за игру'];
    }
    public static function viewFields() {
        return ['id','role','need_commands','test_nights_count','is_one'];
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
        return $this->hasOne(GameRole::class, 'id', 'role_id');
    }
}