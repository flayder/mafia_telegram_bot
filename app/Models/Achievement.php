<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;
    protected $table = "achievements";
    public static $entityLabel="Achievement";
    protected $fillable = ['id','name','role_id','win_amount'];
    public static function labels() {
        return ['id'=>'id','name'=>'name','role_id'=>'role_id','win_amount'=>'win_amount'];
    }
    public static function viewFields() {
        return ['id','name','role_id','win_amount'];
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