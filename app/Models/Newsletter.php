<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;
    protected $table = "newsletters";
    public static $entityLabel="Newsletter";
    protected $fillable = ['id','message','status','type_id'];
    public static function labels() {
        return ['id'=>'id','message'=>'message','status'=>'status','type_id'=>'type_id'];
    }
    public static function viewFields() {
        return ['id','message','status','type_id'];
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