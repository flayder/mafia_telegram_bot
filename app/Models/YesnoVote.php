<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YesnoVote extends Model
{
    use HasFactory;
    protected $table = "yesno_votes";
    public static $entityLabel="YesnoVote";
    protected $fillable = ['id','voiting_id','gamer_id','vote_user_id','vote_role_id','answer'];
    public static function labels() {
        return ['id'=>'id','voiting_id'=>'voiting_id','gamer_id'=>'gamer_id','vote_user_id'=>'vote_user_id','answer'=>'answer'];
    }
    public static function viewFields() {
        return ['id','voiting_id','gamer_id','vote_user_id','answer'];
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