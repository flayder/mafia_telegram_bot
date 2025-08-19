<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;

trait Ugolovnik {
    public static function ugolovnik_select($params) {
        self::gamer_set_move($params,'ugolovnik_select','ugolovnik_select_itog',100,false,'shot');
    }
    
    public static function ugolovnik_select_itog($game) {
        $ugolovnik = GameUser::where('game_id', $game->id)->where('role_id', 23)->first();
        if (!$ugolovnik || !self::isCanMoveWithoutKrasotka($ugolovnik)) return null; 
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['ugolovnik_select'])) return null;
        $victim = GameUser::where('id',$gameParams['ugolovnik_select'])->first();
        if(!$victim) return;
        if($victim->role_id == 4) { //ком
            $bot = AppBot::appBot();
            $message = ['text'=>"Вы попали на 🕵️Комиссара Пуаро\n\nВы можете сказать последнее слово. Оно будет отправлено в чат"];            
            $bot->addCmd('lastword_'.$ugolovnik->id."_",$ugolovnik->user_id);            
            Game::message(['message'=>$message,'chat_id'=>$ugolovnik->user_id]);
            $ugolovnik->is_active = 0;
            $ugolovnik->kill_night_number = $game->current_night;
            $ugolovnik->save();
            return;
        }
        self::user_kill($ugolovnik->id, $gameParams['ugolovnik_select']); //доктор и ведьма не лечат        
    }
}