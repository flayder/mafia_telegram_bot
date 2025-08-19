<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Models\RolesNeedFromSave;
use Illuminate\Support\Facades\DB;

trait Vedma {
    //ведьма может спасать и убивать
    //если спасаем от ведьмы, то от убийства или заморозки ?
    public static $vedmaTreatMessages = [];
    public static function vedma_lechit($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'vedma_lechit',$params['cmd_param']);  
            NightFunction::push_func($gamer->game, 'vedma_lechit_itog',99);
        }
    }
    public static function vedma_lechit_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $vedma = GameUser::where('role_id', 27)->where('game_id', $game->id)->first();
        if(!$vedma) return null;
        if(self::isKrasotkaSelect($game,$vedma->id) && !self::isTreated($vedma->id, $game) && !self::isVedmaTreat($game,$vedma->id) )  { //пришла красотка. ведьма не сработает
            $param = GamerParam::where(['night'=>$game->current_night,'param_name'=>'vedma_lechit'])->first();
            if($param) {
                $param->delete(); 
                GamerParam::saveParam($vedma,'nightactionempty',1);
                GamerParam::gameParams($game, null, true); //update
            }
        }
        else {
            $victim = GameUser::where('id',$gameParams['vedma_lechit'])->first(); //потерпевший
            if(!$victim || !$victim->isActive()) return null; //ошибочный запуск функции  
            DB::table('limit_select')->where('gamer_id',$vedma->id)->where('limit_select','!=',$vedma->id)->delete();
            LimitSelect::create(['gamer_id'=>$vedma->id,'limit_select'=>$victim->id]);
            $victimText = "🧝‍♀️ Ведьма навестила тебя этой ночью";                
            $vedmaText = "Лечебное зелье не пригодилось";
            $whoMaySave = RolesNeedFromSave::with('role')->where('saved_role_id',27)->get();
            $vicMessArr = [];
            $gmMessArr = [];
            if($victim->role_id != 23) { //не лечим от уголовника
                $victimText = '🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Но зелье не пригодилось..';
                foreach($whoMaySave as $maySave) {
                    $night_action = $maySave->role->night_action;
                    if(!empty($night_action) && 
                        isset($gameParams[$night_action]) && $gameParams[$night_action] == $gameParams['vedma_lechit']) {
                        //пришел к роли, на которую попала роль, от которой может спасти
                        $vicMessArr[] = "🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Она исцелила тебя от ".$maySave->role;
                        $gmMessArr[] = "Вы спасли ".Game::userUrlName($victim->user)." от ".$maySave->role;                
                    }            
                }
                
            }
            if($vicMessArr) {  //список всех, от кого спас
                $victimText = implode("\n", $vicMessArr);                        
                $vedmaText = implode("\n", $gmMessArr);                
                self::setVedmaTreatMessage($victim->id,$victim->user_id,$victimText,$vedmaText,1);
            }  
            else {
                self::setVedmaTreatMessage($victim->id,$victim->user_id,$victimText,$vedmaText,0);
            }            
            
            //сообщение для цыганки
            self::ciganka_message($victim, $victimText);

            NightFunction::push_func($game, 'sendVedmaTreatMessage');                
        }
    }
    public static function vedma_morozit($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {            
            GamerParam::saveParam($gamer,'vedma_morozit',$params['cmd_param']);  
            NightFunction::push_func($gamer->game, 'vedma_morozit_itog',99);
        }        
    }
    public static function vedma_morozit_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $vedma = GameUser::where('role_id', 27)->where('game_id', $game->id)->first();
        if(!$vedma) return null;
        if(!self::isCanMove($vedma)) { //пришла красотка. ведьма не сработает
            self::saved_clear_action(GamerParam::deleteAction($game, 'vedma_morozit'));            
        }
        else {      
            DB::table('limit_select')->where('gamer_id',$vedma->id)->where('limit_select','!=',$vedma->id)->delete();
            if(isset($gameParams['vedma_morozit'])) {
                LimitSelect::create(['gamer_id'=>$vedma->id,'limit_select'=>$gameParams['vedma_morozit']]);
                $victim = GameUser::where('id', $gameParams['vedma_morozit'])->first();
                if($victim) {
                    GamerParam::saveParam($victim,'nightactionempty',1);
                    self::victim_message($gameParams['vedma_morozit'], "🧝‍♀️ Ведьма сварила для тебя зелье заморозки! До следующей ночи твои действия заморожены");
                }
            }
        }
    }
    public static function vedma_kill($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'vedma_kill',$params['cmd_param']);  
            NightFunction::push_func($gamer->game, 'vedma_kill_itog');
        }
    }
    public static function vedma_kill_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $vedma = GameUser::where('role_id', 27)->where('game_id', $game->id)->first();
        if(!$vedma || !self::isCanMove($vedma)) return null;
        DB::table('limit_select')->where('gamer_id',$vedma->id)->where('limit_select','!=',$vedma->id)->delete();

        $victim = GameUser::where('id', $gameParams['vedma_kill'])->first();
        
        if($victim && $victim->role_id == 23) {
            self::user_kill($vedma->id, $gameParams['vedma_kill']);
        } elseif(!self::isTreated($gameParams['vedma_kill'], $game)) {                
            self::victim_message($gameParams['vedma_kill'], "Ведьма сварила для тебя убийственное зелье!");                ;
            self::user_kill($vedma->id, $gameParams['vedma_kill']);
        }
    }
    public static function isVedmaTreat($game, $gamer_id) { // ведьма спасла?
        $gameParams = GamerParam::gameParams($game);
        return isset($gameParams['vedma_lechit']) && $gameParams['vedma_lechit'] == $gamer_id;
    }
    public static function isVedmaFrost($game, $gamer_id) { // ведьма заморозила?
        $gameParams = GamerParam::gameParams($game);
        return isset($gameParams['vedma_morozit']) && $gameParams['vedma_morozit'] == $gamer_id;
    }
    public static function setVedmaTreatMessage($gamer_id, $user_id, $textUser, $textVedma, $isNeed) {
        if(isset(self::$vedmaTreatMessages['user'][$gamer_id]) && self::$vedmaTreatMessages['user'][$gamer_id]['is_need']) {
            //тогда дополнить
            if($isNeed) { //чтоб не дополнять сообщением -- навестил
                self::$vedmaTreatMessages['vedma'][$gamer_id]['text'] .= "\n".$textVedma;
                self::$vedmaTreatMessages['user'][$gamer_id]['text'] .= "\n".$textUser;
            }
        }
        else { //иначе добавить или заменить
            self::$vedmaTreatMessages['vedma'][$gamer_id] = [            
                'text'=>$textVedma
            ];
            self::$vedmaTreatMessages['user'][$gamer_id] = [
                'user_id'=>$user_id,
                'text'=>$textUser,
                'is_need'=>$isNeed
            ];
        }
    }
    public static function sendVedmaTreatMessage($game) {
        $vedma = GameUser::where('role_id', 27)->where('game_id', $game->id)->first();
        if(!$vedma) return null;
        $bot = AppBot::appBot();
        if(isset(self::$vedmaTreatMessages['vedma'])) {
            echo "пишем ведьме...";
            $vedmaTextArr = [];//вдрдуг несколько сообщений
            foreach(self::$vedmaTreatMessages['vedma'] as $vmess) {
                $vedmaTextArr[] = $vmess['text'];
            }           
            $bot->sendAnswer([['text'=>implode("\n",$vedmaTextArr)]],$vedma->user_id);
        }
        if(isset(self::$vedmaTreatMessages['user'])) {           
            foreach(self::$vedmaTreatMessages['user'] as $gamerId=>$vmess) {                  
                self::victim_message($gamerId, $vmess['text']);                
            }            
        }
    }
}