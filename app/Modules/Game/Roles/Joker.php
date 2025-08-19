<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\Log;

trait Joker {
    public static function shutnik_select($params) {
        self::gamer_set_move($params, 'shutnik_select', 'shutnik_select_itog',13);  //перед красоткой но после Сут
    }
    public static function shutnik_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $shutnik = GameUser::where('game_id', $game->id)->where('role_id', 32)->first();
        if(!isset($gameParams['shutnik_select'])) return null; //ошибочный запуск функции        
        if(!$shutnik || !self::isCanMove($shutnik)) return null; 

        $victim = GameUser::where('id', $gameParams['shutnik_select'])->first();
        if(!$victim) return null;
        $bot = AppBot::appBot();
        if(in_array($victim->role_id, [6,3,4,17]) && self::isTreated($victim->id, $game)) {
            //кто-то спас
            /* говорят, что дублирует сообщения. закоментим
            if(self::isDoctorTreate($game, $victim->id)) {
                $textUser = "👨🏼‍⚕Доктор спас вас этой ночью от 🌚Шутника";
                $textUnion = "👨🏼‍⚕Доктор спас ".Game::userUrlName($victim->user)." этой ночью от 🌚Шутника";
                $textDoctor= "Вы спасли ".Game::userUrlName($victim->user)." от 🌚Шутника";
                self::setDoctorTreatMessage($victim->id, $victim->user_id, $textUser, $textUnion, $textDoctor, 1);
            }
            else if(self::isVedmaTreat($game, $victim->id)) {
                $textUser = "🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Она исцелила тебя от 🌚Шутника";
                $textVedma= "Вы спасли ".Game::userUrlName($victim->user)." от 🌚Шутника";
                self::setVedmaTreatMessage($victim->id, $victim->user_id, $textUser, $textVedma, 1);
            }
            else { //подпольный
                $victimText = "Вас вылечил 👩🏻‍⚕Подпольный врач!";
                $textDoctor= "Вы спасли ".Game::userUrlName($victim->user)." от 🌚Шутника";
                self::setMafdocTreatMessage($victim->id, $victim->user_id,$victimText,$textDoctor,1);
            }
            */
            return;
        }
        switch($victim->role_id) {
            case 6:  //красотка
                if(isset($gameParams['krasotka_select'])) {
                    $param = GamerParam::where(['game_id'=>$game->id,'night'=>$game->current_night,
                        'param_name'=>'krasotka_select'])->first();
                    if($param) {
                        $param->param_value = $victim->id;
                        $param->save();
                        $gameParams = GamerParam::gameParams($game,null,true);
                    }
                }
                break;
            case 3: //поет                
                GamerParam::saveParam($shutnik, 'shutnik_poet', 1);
                break;
            case 4: //комиссар                
                GamerParam::saveParam($shutnik, 'shutnik_puaro', 1);
                break;    
            case 17: //Дон
                $maf = GameUser::where('game_id',$victim->game_id)->where('role_id',25)->where('is_active',1)->first();
                if($maf) {

                    if(!$maf->first_role_id) $maf->first_role_id = $maf->role_id;
                    $maf->role_id = 17;
                    $maf->save(); //назначили приемником
                    //$message = ['text'=>"Ты повышен до роли 🤵🏻 Дон Корлеоне"];
                    //Game::message(['message'=>$message,'chat_id'=>$maf->user_id]);
                    $victim->role_id = 25;
                    $victim->save();
                    //сообщение в игровой чат
                    $text = "🤵🏻 Дон потерял свои права, кое-кто очень удачно подшутил над ним...";
                    $bot->sendAnswer([['text'=>$text]],$game->group_id);

                    //сообщение для цыганки
                    self::ciganka_message($maf, $text);
                }
                else { //если мафов больше нет. Дон умрет от отчаяния
                    $victim->is_active = 0;                    
                    $victim->killer_id = -1;
                    $victim->kill_night_number = $game->current_night;
                    $victim->save();
                    $dvulikiy = GameUser::where('game_id', $game->id)->where('role_id', 20)->first();
                    if($dvulikiy) {
                        $isKnight = DeactivatedCommand::where(['game_id'=>$game->id,'command'=>'dvulikiy_find'])->first();
                        if(!$isKnight) {
                            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'dvulikiy_find']);
                            $text = "Последний Дон умер. Теперь в твои руки попал нож...";
                            $bot = AppBot::appBot();
                            $bot->sendAnswer([['text'=>$text]], $dvulikiy->user_id);
                            //сообщение для цыганки
                            self::ciganka_message($dvulikiy, $text);
                        }
                    }
                    $text = "🤵🏻 Дон потерял свои права, а так как передать их некому, он умер от отчаяния...";
                    $bot->sendAnswer([['text'=>$text]],$game->group_id);
                    self::ifIdolOderjimActivate($victim); //начнет играть одержимый

                    $bot = AppBot::appBot();
                    $bot->addCmd('lastword_'.$victim->id."_",$victim->user_id);
                    $message = ['text'=>"Вы можете сказать последнее слово. Оно будет отправлено в чат"];
                    Game::message(['message'=>$message,'chat_id'=>$victim->user_id]);
                }   
                break;  
        }
    }

    public static function jokerNotSelectKrasotka($game) {
        $gameParams = GamerParam::gameParams($game);
        $krasotka = self::getKrasotka($game->id);

        if(isset($gameParams['shutnik_select']) && $gameParams['shutnik_select'] == $krasotka->id)
            return false;

        return true;
    }
}