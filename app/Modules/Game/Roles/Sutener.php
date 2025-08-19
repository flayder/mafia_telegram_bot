<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;

trait Sutener {
    public static function sutener_select($params) {
        self::gamer_set_move($params, 'sutener_select', 'sutener_select_itog',10);
    }
    public static function sutener_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['sutener_select'])) return null; //ошибочный запуск функции
        $sutener = GameUser::where('game_id',$game->id)->where('role_id',36)->first();
        if(!$sutener || !self::isCanMove($sutener)) return null;
        GamerParam::saveParam($sutener, 'sutener_itog', $gameParams['sutener_select']);
        $bot = AppBot::appBot();
        $victim = GameUser::where('id',$gameParams['sutener_select'])->first();
        if(!$victim) return null;
        if(self::isKrasotkaSelect($game,$gameParams['sutener_select'])) {  
            $bot->sendAnswer([['text'=>"Ты навестил ".Game::userUrlName($victim->user)." и увёл у него 💃🏻Красотку!"]], $sutener->user_id);
            self::victim_message($gameParams['sutener_select'], "Похоже, 👣Сутенер сегодня увел у тебя 💃🏻Красотку!");

            $row = GamerParam::where([
                'game_id'       => $game->id,
                'param_name'    => 'krasotka_select',
                'night'         => $game->current_night
            ])->first();

            if($row) {
                GamerParam::deleteAction($game, 'krasotka_select');
                GamerParam::saveParam($row->gamer,'nightactionempty',1);
            }

            GamerParam::saveParam($sutener, 'sutener_find', 1);
        }
        else {
            $bot->sendAnswer([['text'=>"Ты навестил ".Game::userUrlName($victim->user).". 💃🏻Красотки там не было..."]], $sutener->user_id);
        }
    }
    public static function isSutenerSelect($game, $gamer_id) {
        $gameParams = GamerParam::gameParams($game);
        return isset($gameParams['sutener_itog']) && $gameParams['sutener_itog'] == $gamer_id;
    }
}