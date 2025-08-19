<?php

namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Models\UnionParticipant;
use App\Models\RolesNeedFromSave;
use Illuminate\Support\Facades\Log;

trait Doctor
{
    protected static $doctorTreatMessages = [];
    public static function getDoctor($game_id)
    {
        return GameUser::where('game_id', $game_id)->where('role_id', 15)->where('is_active', 1)->first();
    }
    public static function getTreatDoctor(\App\Models\Game $game) { //получим доктора, который лечил этой ночью
        //current_night
        $treatParam = GamerParam::where(['game_id'=>$game->id,'night'=>$game->current_night,'param_name'=>'doctor_treat'])->first();
        if($treatParam) { 
            return $treatParam->gamer;
        }
        return self::getDoctor($game->id); //не найден параметр лечения? вернем доктора текущего если есть
    }
    public static function doctor_treat($params)
    {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if (!$gamer) return null;
        $doctor = $gamer;
        GamerParam::saveParam($gamer, 'doctor_treat', $params['cmd_param']);
        $victim = GameUser::where('id', $params['cmd_param'])->first(); //потерпевший
        if (!$victim || !$victim->isActive()) return null; //ошибочный запуск функции
        $uniParticips = UnionParticipant::unionParticipantsByGamer($doctor);
        if ($uniParticips) {
            foreach ($uniParticips as $uParticip) {
                if ($uParticip->gamer_id == $gamer->id) continue;
                if ($uParticip->gamer->isActive()) {
                    Game::message(['message' => ['text' => "👨🏼‍⚕Доктор выбрал " . Game::userUrlName($victim->user)], 'chat_id' => $uParticip->gamer->user_id]);
                    usleep(35000);
                }
            }
        }
        NightFunction::push_func($gamer->game, 'doctor_treat_itog', 20);
    }
    public static function doctor_treat_itog($game)
    {
        $doctor_role_id = 15;
        $gameParams = GamerParam::gameParams($game);
        if (!isset($gameParams['doctor_treat'])) return null; //ошибочный запуск функции
        $doctor = self::getDoctor($game->id);
        if ($doctor && $doctor->id == $gameParams['doctor_treat']) { //доктор пришел к себе. Лимитуем
            LimitSelect::create(['gamer_id' => $doctor->id, 'limit_select' => $doctor->id]);
        }
        $assist = GameUser::where('role_id', 14)->where('is_active', 1)->first();
        if ($assist && $assist->id == $gameParams['doctor_treat']) { //Лимитируем лечение асистента
            LimitSelect::create(['gamer_id' => $doctor->id, 'limit_select' => $gameParams['doctor_treat']]);
        }
        if (!$doctor || !self::isCanMove($doctor)) {
            GamerParam::deleteAction($game, 'doctor_treat');
            return null;  //доктора заглушила красотка или ведьма
        }
        $victim = GameUser::where('id', $gameParams['doctor_treat'])->first(); //потерпевший
        if (!$victim || !$victim->isActive()) return null; //ошибочный запуск функции
        $victimText = "👨🏼‍⚕Доктор навестил тебя этой ночью";
        $unionText = "👨🏼‍⚕Доктор навестил " . Game::userUrlName($victim->user);
        $gamerMessage = [];
        $vicMessArr = [];
        $gmMessArr = [];
        $unionMessArr = [];
        if($victim->role_id != 23) {
            $whoMaySave = RolesNeedFromSave::with('role')->where('saved_role_id', $doctor_role_id)->get();        
            foreach (self::get_clear_actions() as $gmpArrItem) { //искуственно дополним gameParams из сохраненного массива удаленных
                $gameParams[$gmpArrItem['param_name']] = $gmpArrItem['param_value'];
            }
            $isSavedOtMaf = false;
            foreach ($whoMaySave as $maySave) {
                $night_actions_str = $maySave->role->night_action;
                if (!empty($night_actions_str)) {
                    $night_actions = explode(',', $night_actions_str);
                    foreach ($night_actions as $night_action) {
                        if (isset($gameParams[$night_action]) && $gameParams[$night_action] == $gameParams['doctor_treat']) {
                           // Log::channel('daily')->info('проверим, не заглушила ли красотка или ведьма того, от кого спасаем...');
                            //проверим, не заглушила ли красотка или ведьма того, от кого спасаем
                            $napadGamer = GameUser::where('is_active', 1)->where('role_id', $maySave->role_id)->where('game_id', $game->id)->first();
                            if ($napadGamer && (self::isKrasotkaSelect($game, $napadGamer->id) || self::isVedmaFrost($game, $napadGamer->id))) {
                                //нападающий заглушен. От него не надо спасать
                              //  Log::channel('daily')->info('нападающий заглушен. От него не надо спасать');
                                continue;
                            }
                            //----------------------------------------------------------------------
                           // Log::channel('daily')->info("спасаем от $night_action + {$napadGamer->id}");
                            if (isset($gameParams['karleone_select']) && $night_action == 'mafiya_select') {
                                if ($isSavedOtMaf) continue;
                                if (isset($gameParams['karleone_select'])) continue;  //не спасаем от мафа, когда дон сделал ход. Не важно, заглушен или нет. Так как если заглушен, убийства не будет
                                $isSavedOtMaf = true;
                                $vicMessArr[] = "👨🏼‍⚕Доктор спас вас этой ночью от 🤵🏻 Дон Корлеоне";
                                $gmMessArr[] = "Вы спасли " . Game::userUrlName($victim->user) . " от 🤵🏻 Дон Корлеоне";
                                $unionMessArr[] = "👨🏼‍⚕Доктор спас " . Game::userUrlName($victim->user) . " от 🤵🏻 Дон Корлеоне";
                            } else {
                                if ($night_action == 'karleone_select')  $isSavedOtMaf = true;
                                if($maySave->role_id == 25) {
                                    $a_don = GameUser::where('game_id',$game->id)->where('role_id',17)->where('is_active',1)->first();
                                    $donhod = GamerParam::where(['night'=>$game->current_night,'gamer_id'=>$a_don->id])->first();
                                    if($a_don && $donhod && $donhod->param_name == 'nightactionempty') { //дон пропустил ход. мафия тоже не может убить
                                        //спасать не надо. Дон пропустил ход
                                        continue;                                    
                                    }
                                }                                

                                $vicMessArr[] = "👨🏼‍⚕Доктор спас вас этой ночью от " . $maySave->role;
                                $gmMessArr[] = "Вы спасли " . Game::userUrlName($victim->user) . " от " . $maySave->role;
                                $unionMessArr[] = "👨🏼‍⚕Доктор спас " . Game::userUrlName($victim->user) . "  от " . $maySave->role;
                            }
                        }
                    }
                }
            }
        }
        if ($vicMessArr) {  //список всех, от кого спас
            $victimText = implode("\n", $vicMessArr);
            $$unionText = implode("\n", $unionMessArr);
            $doctorText = implode("\n", $gmMessArr);
            self::setDoctorTreatMessage($victim->id, $victim->user_id, $victimText, $unionText, $doctorText, 1);
        } else {
            $doctorText = "Вы навестили " . Game::userUrlName($victim->user);
            self::setDoctorTreatMessage($victim->id, $victim->user_id, $victimText, $unionText, $doctorText, 0);
        }

        //сообщение для цыганки
        self::ciganka_message($victim, $victimText);
        
        NightFunction::push_func($game, 'sendDoctorTreatMessage');
    }
    public static function isDoctorTreate($game, $gamer_id)
    { //спасенный
        $gameParams = GamerParam::gameParams($game);
        if (isset($gameParams['doctor_treat']) && $gameParams['doctor_treat'] == $gamer_id) return true;
        return false;
    }

    public static function ifAssistentTop($game)
    {
        $gameParams = GamerParam::gameParams($game);
        if (isset($gameParams['assistent_top'])) {
            GamerParam::deleteAction($game, 'assistent_top');
            $asist = self::getDoctor($game->id);  //ассистеннт уже Доктор
            if (!$asist) return null;
            $message = ['text' => "Ты занял место 👨🏼‍⚕Доктора"];
            Game::message(['message' => $message, 'chat_id' => $asist->user_id]);
            $group_mes = ['text' => "<b>🧑🏼‍⚕Ассистент</b> занял место <b>👨🏼‍⚕Доктора</b>"];
            Game::message(['message' => $group_mes, 'chat_id' => $asist->game->group_id]);
        }
    }

    public static function setDoctorTreatMessage($gamer_id, $user_id, $textUser, $textUnion, $textDoctor, $isNeed)
    {
        if (isset(self::$doctorTreatMessages['user'][$gamer_id]) && self::$doctorTreatMessages['user'][$gamer_id]['is_need']) {
            //тогда дополнить
            if($isNeed) { //чтоб не дополнять сообщением -- навестил
                self::$doctorTreatMessages['doc'][$gamer_id]['text'] .= "\n" . $textDoctor;
                self::$doctorTreatMessages['union'][$gamer_id]['text'] .= "\n" . $textUnion;
                self::$doctorTreatMessages['user'][$gamer_id]['text'] .= "\n" . $textUser;
            }
        } else { //иначе добавить или заменить
            self::$doctorTreatMessages['doc'][$gamer_id] = [
                'text' => $textDoctor
            ];
            self::$doctorTreatMessages['union'][$gamer_id] = [
                'text' => $textUnion
            ];
            self::$doctorTreatMessages['user'][$gamer_id] = [
                'user_id' => $user_id,
                'text' => $textUser,
                'is_need' => $isNeed
            ];
        }
    }
    public static function sendDoctorTreatMessage($game)
    {
        $doctor = self::getTreatDoctor($game);
        if (!$doctor) return null;
        $bot = AppBot::appBot();

        if (isset(self::$doctorTreatMessages['doc'])) {
            $docTextArr = []; //вдрдуг несколько сообщений
            foreach (self::$doctorTreatMessages['doc'] as $vmess) {
                $docTextArr[] = $vmess['text'];
            }
            $bot->sendAnswer([['text' => implode("\n", $docTextArr)]], $doctor->user_id);
        }
        if (isset(self::$doctorTreatMessages['union'])) {
            $unionMessArr = []; //вдрдуг несколько сообщений
            foreach (self::$doctorTreatMessages['union'] as $vmess) {
                $unionMessArr[] = $vmess['text'];
            }

            $uniParticips = UnionParticipant::unionParticipantsByGamer($doctor);
            $unionMessage = ['text' => implode("\n", $unionMessArr)];
            if ($uniParticips) {
                foreach ($uniParticips as $uParticip) {
                    if ($uParticip->gamer_id == $doctor->id) continue;
                    if ($uParticip->gamer->isActive()) {
                        Game::message(['message' => $unionMessage, 'chat_id' => $uParticip->gamer->user_id]);
                        usleep(35000);
                    }
                }
            }            
        }
        if (isset(self::$doctorTreatMessages['user'])) {
            foreach (self::$doctorTreatMessages['user'] as $gamerId => $vmess) {
                self::victim_message($gamerId, $vmess['text']);
            }
        }
    }
}
