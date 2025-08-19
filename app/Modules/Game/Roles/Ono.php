<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

trait Ono {
    public static function ono_gallow($gamer) {        
        $gamer->is_active = 2;  //победа
        $gamer->save();
        $message['text'] = "Тебя повесили. Чью роль хочешь раскрыть?";
        $bot = AppBot::appBot();
        $actives = GameUser::where('game_id',$gamer->game_id)->where('is_active',1)->get();   
        $tm = time();
        $message['inline_keyboard'] = $bot->inlineKeyboard($actives, 1, "onoopenrole_{$tm}_",false, 'id','user',[$gamer->id]);
        $bot->sendAnswer([$message],$gamer->user_id);
    }
}