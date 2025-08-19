<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Models\RolesNeedFromSave;
use Illuminate\Support\Facades\Log;

trait MafDoctor {
    protected static $mafdocTreatMessages = [];
    public static function mafdoc_treat($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if(!$gamer) return null;
        GamerParam::saveParam($gamer,'mafdoctor_treat',$params['cmd_param']);
        GamerParam::saveParam($gamer,'mafdoctor_treat'.$params['cmd_param'],$params['cmd_param']);
        NightFunction::push_func($gamer->game, 'mafdoctor_treat_itog',20);
    }
    public static function mafdoctor_treat_itog($game) {
        sleep(1);
        $doctor_role_id = 16;
        $gameParams = GamerParam::gameParams($game);
        $params = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night, 'param_name' => 'mafdoctor_treat'])->get();
        $whoMaySave = RolesNeedFromSave::with('role')->where('saved_role_id',$doctor_role_id)->get();

        //В игре может быть несколько подпольных врачей, обрабатываем всех
        foreach($params as $k => $gameParam) {
            $doctor = $gameParam->gamer;
            //Log::info('MafDoctor 1', ['$doctor' => print_r($doctor, true)]);
            if(!self::isCanMove($doctor)) {     
                continue;  //доктора заглушила красотка или ведьма
            }
            $mafia_roles = [16,17,18,19,20,21,22,23,24,25];
            $victim = GameUser::where('id',$gameParam->param_value)->first(); //потерпевший
            if(!$victim || !$victim->isActive()) continue; //ошибочный запуск функции
            if(!in_array($victim->role_id,$mafia_roles)) {
                $bot = AppBot::appBot();
                $bot->sendAnswer([['text'=>'Вы не можете лечить нейтральные и мирные роли...']], $doctor->user_id);
                continue; //не лечит не мафов
            }
            if($doctor->id == $gameParam->param_value) { //лечит себя 1 раз за игру
                LimitSelect::create(['gamer_id'=>$doctor->id,'limit_select'=>$doctor->id]);
            }
            //Log::info('MafDoctor 2');
            $victimText = "👩🏻‍⚕Подпольный врач навестил тебя этой ночью! Аптечка не пригодилась..";
            $vicMessArr = [];
            $gmMessArr = []; 
            if($victim->role_id != 23) { //не нужно лечить уголовника
                foreach(self::get_clear_actions() as $gmpArrItem ) { //искуственно дополним gameParams из сохраненного массива удаленных
                    $gameParams[$gmpArrItem['param_name']] = $gmpArrItem['param_value'];
                }
            
                foreach($whoMaySave as $maySave) {
                    $night_actions_str = $maySave->role->night_action;
                    if(!empty($night_actions_str)) {
                        $night_actions = explode(',',$night_actions_str);
                        foreach($night_actions as $night_action) {
                            if(isset($gameParams[$night_action.$victim->id]) && $gameParams[$night_action.$victim->id] == $gameParam->param_value) {

                                $napadGamer = GameUser::where('is_active', 1)->where('role_id', $maySave->role_id)->where('game_id', $game->id)->first();
                                if ($napadGamer && (self::isKrasotkaSelect($game, $napadGamer->id) || self::isVedmaFrost($game, $napadGamer->id))) {
                                    //нападающий заглушен. От него не надо спасать
                                  //  Log::channel('daily')->info('нападающий заглушен. От него не надо спасать');
                                    continue;
                                }

                                if(!$vicMessArr) $vicMessArr[] = "Вас вылечил 👩🏻‍⚕Подпольный врач!";  // от ".$maySave->role;  //если от кого, тогда убрать if в начале строки

                                $gmMessArr[] = "Вы вылечили ".Game::userUrlName($victim->user) . " от ".$maySave->role;                                  
                            }
                        }
                    }            
                }
            }

            $gamerMessage = ['text' => ''];

            if(!$gmMessArr) {
                $gamerMessage = ['text'=>"Ваша аптечка не пригодилась ".Game::userUrlName($victim->user)];  
            }
            
            if($vicMessArr) {  //список всех, от кого спас
                $victimText = implode("\n", $vicMessArr);              
                $gamerMessage = ['text'=>implode("\n", $gmMessArr)]; 
                self::setMafdocTreatMessage($victim->id, $victim->user_id,$victimText,$gamerMessage['text'],1);
            }             
            else {
                self::setMafdocTreatMessage($victim->id, $victim->user_id,$victimText,$gamerMessage['text'],0);
            }

            //Log::info('MafDoctor $gamerMessage', ['$gamerMessage' => $gamerMessage, '$victimText' => $victimText]);

            //GamerParam::saveParam($doctor,'mafdoctor_doctor_message'.$k, $gamerMessage['text']);
            //GamerParam::saveParam($victim,'mafdoctor_victim_message'.$k, $victimText);

            //сообщение для цыганки
            //self::ciganka_message($victim, $victimText);
        }

        
        NightFunction::push_func($game, 'sendMafdocTreatMessage');

        /*
        if($doctor) {
            Game::message(['message'=>$gamerMessage,'chat_id'=>$doctor->user_id]);
        } */
    }
    public static function isMafDoctorTreate($game, $gamer_id, $role_id=null) { //спасенный
        $gameParams = GamerParam::gameParams($game);
        if(!$role_id) {
            $gamer = GameUser::where('id',$gamer_id)->first();
            if(!$gamer) return false;
            $role_id = $gamer->role_id;
        }
        if(!in_array($role_id,[16,17,18,19,20,21,22,24,25])) return false;
        if(isset($gameParams['mafdoctor_treat']) && $gameParams['mafdoctor_treat'] == $gamer_id ) return true;        
        return false;
    }

    public static function setMafdocTreatMessage($gamer_id, $user_id, $textUser, $textdoctor, $isNeed) {
        if(isset(self::$mafdocTreatMessages['user'][$gamer_id]) && self::$mafdocTreatMessages['user'][$gamer_id]['is_need']) {
            //тогда дополнить
            if($isNeed) { //чтоб не дополнять сообщением -- навестил
                self::$mafdocTreatMessages['doctor'][$gamer_id]['text'] .= "\n".$textdoctor;
                self::$mafdocTreatMessages['user'][$gamer_id]['text'] .= "\n".$textUser;
            }
        }
        else { //иначе добавить или заменить
            self::$mafdocTreatMessages['doctor'][$gamer_id] = [            
                'text'=>$textdoctor
            ];
            self::$mafdocTreatMessages['user'][$gamer_id] = [
                'user_id'=>$user_id,
                'text'=>$textUser,
                'is_need'=>$isNeed
            ];
        }
    }
    public static function sendMafdocTreatMessage($game) {
        $bot = AppBot::appBot();
        //$arrDMessages = [];
        //$arrVMessages = [];
        //$dMessages = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night])->where('param_name', 'like', 'mafdoctor_doctor_message%')->get();

        //foreach($dMessages as $dMessage) {
        //    $arrDMessages[$dMessage->gamer->user_id][] = $dMessage->param_value;
        //}

        //foreach($arrDMessages as $userId => $messages) {
        //    $bot->sendAnswer([['text'=>implode("\n",$messages)]],$userId);
        //}

        //Log::info('$dMessages', [
        //    '$dMessages' => print_r($arrDMessages, true)
        //]);

        //$vMessages = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night])->where('param_name', 'like', 'mafdoctor_victim_message%')->get();

        //foreach($vMessages as $vMessage) {
        //    $arrVMessages[$vMessage->gamer->user_id][] = $vMessage->param_value;
        //}

        //foreach($arrVMessages as $userId => $messages) {
        //    $bot->sendAnswer([['text'=>implode("\n",$messages)]], $userId);
        //}

        //Log::info('$vMessages', [
        //    '$vMessages' => print_r($arrVMessages, true)
        //]);


        $doctors = [];

        if(isset(self::$mafdocTreatMessages['doctor'])) {            
            $checkDoctors = [];
            foreach(self::$mafdocTreatMessages['doctor'] as $gamerId => $vmess) {
                if(!isset($checkDoctors[$gamerId])) {
                    $gameParam = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night, 'param_name' => 'mafdoctor_treat', 'param_value' => $gamerId])->first();
                    if(!$gameParam) continue; //ошибочный запуск функции
                    $doctor = $gameParam->gamer;
                    $doctors[$doctor->user_id][] = $vmess['text'];
                    $checkDoctors[$gamerId] = $doctor->user_id;
                } else {
                    $doctors[$checkDoctors[$gamerId]][] = $vmess['text'];
                }
            }
            foreach($doctors as $doctorId => $messages) {
                $bot->sendAnswer([['text'=>implode("\n",$messages)]],$doctorId);
            }        
            
        }

        if(!$doctors) return null;

        if(isset(self::$mafdocTreatMessages['user'])) {           
            foreach(self::$mafdocTreatMessages['user'] as $gamerId=>$vmess) {                  
                self::victim_message($gamerId, $vmess['text']);                
            }            
        }
    }
}