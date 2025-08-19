<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;

trait Godfather {
    public static function krestnotec_save($params) {
        self::gamer_set_move($params,'krestnotec_save','krestnotec_save_itog', 50);
    }
    public static function krestnotec_save_itog($game) {
        $krotec = GameUser::where('game_id', $game->id)->where('role_id', 13)->first();
        $gameParams = GamerParam::gameParams($game);
        
        //$victim = GameUser::where(['game_id' => $game->id, 'user_id' => $gameParams['krestnotec_save']])->first();
        $victim = GameUser::where(['id' => $gameParams['krestnotec_save']])->first();
        if(!$victim) return;
        
        if (!$krotec || !self::isCanMoveWithoutKrasotka($krotec)) return null;
        GamerParam::saveParam($krotec,'krestnotec_save_itog',$victim->user_id,false);
        $mess = "Крестный отец взял вас под свою защиту! Вас не смогут повесить на дневном голосование.";
        $message = ['text'=> $mess];
        //сообщение для цыганки
        self::ciganka_message($victim, $mess);
        Game::message(['message'=>$message,'chat_id'=>$victim->user_id]);
    }
    public static function isGodfatherSave($gamer) {
        $gameParams = GamerParam::gameParams($gamer->game);
        if(isset($gameParams['krestnotec_save_itog']) && $gameParams['krestnotec_save_itog'] == $gamer->user_id) return true;
        return false;
    }
}