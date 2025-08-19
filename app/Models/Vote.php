<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;
    protected $table = "votes";
    public static $entityLabel="Vote";
    protected $fillable = ['id','voiting_id','gamer_id','vote_user_id','vote_role_id'];
    public static function labels() {
        return ['id'=>'id','voiting_id'=>'voiting_id','gamer_id'=>'gamer_id','vote_user_id'=>'vote_user_id'];
    }
    public static function viewFields() {
        return ['id','voiting_id','gamer_id','vote_user_id'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function gamer() {
        return $this->hasOne(GameUser::class, 'id','gamer_id');
    }
}