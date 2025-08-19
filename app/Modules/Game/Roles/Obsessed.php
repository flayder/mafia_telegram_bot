<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Bot\AppBot;
use App\Models\DeactivatedCommand;

trait Obsessed {
    public static function oderjim_select($params) {        
        self::gamer_set_move($params, 'oderjim_select',null,100,true); //красотка на выбор не действует
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first(); 
        if(!$gamer) return "";
        //деактивируем все действия
        DeactivatedCommand::create(['game_id'=>$gamer->game_id,'command'=>'oderjim_select']);
        $dc = DeactivatedCommand::create(['game_id'=>$gamer->game_id,'command'=>'oderjim_kill']);  //чтоб не убивал, пока жив идол
        GamerParam::saveParam($gamer, 'oderjim_killcmd', $dc->id);
    }   
    public static function oderjim_kill($params) {
        self::gamer_set_move($params, 'oderjim_kill', 'oderjim_kill_itog',100, false, 'shot');
    }
    public static function oderjim_kill_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['oderjim_kill'])) return null; //ошибочный запуск функции
        $oderjim = GameUser::where('game_id',$game->id)->where('role_id',10)->first();
        if(!$oderjim || !self::isCanMove($oderjim)) return null;

        $victim = GameUser::where('id', $gameParams['oderjim_kill'])->first();
        
        if($victim && $victim->role_id == 23) {
            self::user_kill($oderjim->id, $gameParams['oderjim_kill']);
        } elseif(!self::isTreated($gameParams['oderjim_kill'], $game, $gameParams)) {
            //если не спас док
            self::user_kill($oderjim->id, $gameParams['oderjim_kill']);
        }
    } 
    public static function ifIdolOderjimActivate(GameUser $killed_gamer) { //активирует режим убийцы если умер идол
        $gameParams = GamerParam::gameBeforeNightsParams($killed_gamer->game,$killed_gamer->game->current_night+1);
        if(isset($gameParams['oderjim_select']) && $gameParams['oderjim_select'] == $killed_gamer->id && 
                isset($gameParams['oderjim_killcmd']) ) {
            GamerParam::saveParam($killed_gamer, 'oderjim_neytral',1);
            GamerParam::saveParam($killed_gamer, 'oderjim_neytr_mess',1);
            $dc = DeactivatedCommand::where('id',$gameParams['oderjim_killcmd'])->first();
            if($dc) $dc->delete();            
        }
    }
    public static function oderjimIsNeytral($game) {
        $gameParams = GamerParam::gameBeforeNightsParams($game,$game->current_night+1);
        return isset($gameParams['oderjim_neytral']);
    }
    public static function ifOderjimAngryMess($game) {
        $oderjim = GameUser::where('game_id',$game->id)->where('role_id',10)->first();
        if(!$oderjim) return null;
        $gamearams = GamerParam::gameParams($game);
        if(isset($gamearams['oderjim_neytr_mess'])) {
            GamerParam::deleteAction($game,'oderjim_neytr_mess');
            $bot = AppBot::appBot();
            $bot->sendAnswer([['text'=>"Ваш идол был убит. Отомстите за него."]],$oderjim->user_id);
            $bot->sendAnswer([['text'=>"<b>🤩Одержимый</b> разозлился из-за смерти своего идола. Месть будет страшна!"]],$oderjim->game->group_id);
        }
    }

}