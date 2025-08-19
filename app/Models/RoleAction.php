<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleAction extends Model
{
    use HasFactory;
    protected $table = "role_actions";
    public static $entityLabel="RoleAction";
    protected $fillable = ['id','action','role_id'];
    public static function labels() {
        return ['id'=>'id','action'=>'action','role_id'=>'role_id'];
    }
    public static function viewFields() {
        return ['id','action','role_id'];
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