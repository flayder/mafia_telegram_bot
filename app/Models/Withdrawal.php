<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;
    protected $table = "withdrawals";
    public static $entityLabel="Withdrawal";
    protected $fillable = ['id','user_id','groups','amount','way','status'];
    public static function labels() {
        return ['id'=>'id','user_id'=>'user_id','groups'=>'groups','amount'=>'amount','way'=>'way','status'=>'status'];
    }
    public static function viewFields() {
        return ['id','user_id','amount','way','status'];
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