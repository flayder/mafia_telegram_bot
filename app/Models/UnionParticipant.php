<?php

namespace App\Models;

use App\Modules\Bot\AppBot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnionParticipant extends Model
{
    use HasFactory;
    protected $table = "union_participants";
    public static $entityLabel="UnionParticipant";
    protected $fillable = ['id','union_id','gamer_id','game_id','pos_in_union'];
    public static function labels() {
        return ['id'=>'id','union_id'=>'union_id','gamer_id'=>'gamer_id'];
    }
    public static function viewFields() {
        return ['id','union_id','gamer_id'];
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
        return $this->hasOne(GameUser::class,'id','gamer_id');
    }
    public static function unionParticipantsByGamer(GameUser $gamer) {
        $participant = self::where('gamer_id',$gamer->id)->where('game_id',$gamer->game->id)->first();
        $result = [];
        if($participant) {
            $result = self::with('gamer')->where('union_id',$participant->union_id)->get()->all();
        }
        return $result;
    }   
    public static function gamerIdsOfUnions($game_id) {
        $models = self::where('game_id',$game_id)->get();
        $result = [];
        foreach($models as $model) {
            $result[$model->union_id][] = $model->gamer_id;
        }
        return $result;
    }
    public static function unionGamerMessage(GameUser $gamer, $message, $exludeMe = true) {        
        $model = self::where('gamer_id',$gamer->id)->first();
        if($model) {
            $model->unionMessage($message, $exludeMe);
        }
        if(!$exludeMe && !$model) {
            $bot = AppBot::appBot();
            $bot->sendAnswer([$message], $gamer->user_id);
        }
    }
    public function unionMessage($message, $exludeMe = true) {        
        $bot = AppBot::appBot();
        $unionGamers = self::with('gamer')->where('union_id',$this->union_id)->get();
        foreach($unionGamers as $unGamer) {
            if($exludeMe && ($unGamer->id == $this->id)) continue;
            if(!$unGamer->gamer || !$unGamer->gamer->isActive()) continue;
            $bot->sendAnswer([$message], $unGamer->gamer->user_id);
            usleep(35000);
        }
    }
}