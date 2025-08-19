<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SendCurHistory extends Model
{
    use HasFactory;
    protected $table = "send_cur_history";
    public static $entityLabel="SendCurHistory";
    protected $fillable = ['id','currency','amount','sender','recipient','group_id'];
    public static function labels() {
        return ['id'=>'id','currency'=>'currency','amount'=>'amount','sender'=>'sender','recipient'=>'recipient','group_id'=>'group_id'];
    }
    public static function viewFields() {
        return ['id','currency','amount','sender','recipient','group_id'];
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