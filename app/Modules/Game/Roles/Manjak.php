<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Models\NightFunction;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

trait Manjak {
    public static function manjak_select($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'manjak_select',$params['cmd_param']);           
        }  
        NightFunction::push_func($gamer->game,'manjak_itog');
        self::execBafMethod($gamer, 'shot');
        return '';
    }
    public static function manjak_itog($game) {
        //если накрыла красотка
        $gameParams = GamerParam::gameParams($game);
        $manjak = GameUser::where('game_id',$game->id)->where('role_id',28)->first();
        if(!isset($gameParams['manjak_select']) || !$manjak || !self::isCanMove($manjak)) return null; //не может сделать ход
        $victim = GameUser::where('id', $gameParams['manjak_select'])->first();
        
        if($victim && $victim->role_id == 23) {
            self::user_kill($manjak->id, $gameParams['manjak_select']);
        } else if(!self::isTreated($gameParams['manjak_select'],$game)) {
            self::user_kill($manjak->id, $gameParams['manjak_select']);
        }  
        else {
            if($gameParams['manjak_select'] == 23) {
                $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
                $bot = AppBot::appBot();
                $bot->sendAnswer([['text'=>$text]],$manjak->user_id);
            } 
        }
    }
}