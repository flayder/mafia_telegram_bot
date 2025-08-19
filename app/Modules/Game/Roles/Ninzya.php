<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

trait Ninzya {
    public static function ninzya_select($params) {
        self::gamer_set_move($params, 'ninzya_select','ninzya_select_itog',100,false,'shot');
    }
    public static function ninzya_select_itog($game) { 
        /*
        1 уровень - такой же убийца как Дон.
        2 уровень - может пробить защиту/дока. 
        3 уровень - может пробить и защиту и дока.
        4 уровень - может убить уголовника/тень 
        */
        $ninzya = GameUser::where('game_id', $game->id)->where('role_id', 30)->first();
        if (!$ninzya || !self::isCanMove($ninzya)) return null; //обездвижен
        $gameParams = GamerParam::gameParams($game);     
        if(!isset($gameParams['ninzya_select'])) return null;
        $paramsBeforeNights = GamerParam::gameBeforeNightsParams($game);        
        $ninzyaLevel = $paramsBeforeNights['ninzya_level'] ?? 1;
      //  Log::channel('daily')->info("ninzyaLevel = ".$ninzyaLevel); //d
        $bot = AppBot::appBot();
        $victim = GameUser::where('id',$gameParams['ninzya_select'])->first();
        switch($ninzyaLevel) {
            case 1: //обычный убийца
                if($victim && $victim->role_id == 23) {
                    self::user_kill($ninzya->id, $gameParams['ninzya_select']);
                } else if(!self::isTreated($gameParams['ninzya_select'], $game, $victim->role_id)) { 
                    $mess = ['text'=>"Этой ночью 🥷🏻Ниндзя кинул свой сюрикен в тебя!"];
                    $bot->sendAnswer([$mess],$victim->user_id);                  
                    self::user_kill($ninzya->id,$gameParams['ninzya_select']);
                   // Log::channel('daily')->info("не спасли, убил"); //d
                    //сообщение для цыганки
                    self::ciganka_message($victim, $mess['text']);
                }
                else {
                    if($victim->role_id == 23) {
                        $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
                        $bot = AppBot::appBot();
                        $bot->sendAnswer([['text'=>$text]],$ninzya->user_id);
                    } 
                    else {
                        //кто вылечил. Добавим сообщения
                        if(self::isVedmaTreat($game,$gameParams['ninzya_select'])) {                        
                            $vicMess = "<b>🧝‍♀️ Ведьма</b> сварила для тебя лечебное зелье! Она исцелила тебя от <b>🥷🏻Ниндзя</b>";
                            $vedmaMess = "Вы спасли <b>".Game::userUrlName($victim->user)."</b> от <b>🥷🏻Ниндзя</b>";  
                            self::setVedmaTreatMessage($gameParams['ninzya_select'],$victim->user_id,$vicMess,$vedmaMess,1);
                            //сообщение для цыганки
                            self::ciganka_message($victim, $vicMess);                      
                        }
                        if(self::isDoctorTreate($game, $gameParams['ninzya_select'])) {
                            $vicMess = "<b>👨🏼‍⚕Доктор</b> спас вас этой ночью от <b>🥷🏻Ниндзя</b>";
                            $gmMess = "Вы спасли <b>" . Game::userUrlName($victim->user) . "</b> от <b>🥷🏻Ниндзя</b>";
                            $unionMess = "<b>👨🏼‍⚕Доктор</b> спас <b>" . Game::userUrlName($victim->user) . "</b>  от <b>🥷🏻Ниндзя</b>";
                            self::setDoctorTreatMessage($gameParams['ninzya_select'],$victim->user_id, $vicMess, $unionMess, $gmMess, 1);
                            //сообщение для цыганки
                            self::ciganka_message($victim, $vicMess);
                        }
                        // if(self::isMafDoctorTreate($game, $gameParams['ninzya_select'],$victim->role_id)) {
                        //     $vicMess = "Вас вылечил 👩🏻‍⚕Подпольный врач!";
                        //     $gmMess = "Вы вылечили <b>".Game::userUrlName($victim->user)."</b> от <b>🥷🏻Ниндзя</b>";
                        //     self::setMafdocTreatMessage($victim->id, $victim->user_id,$vicMess,$gmMess,1);
                        //     //сообщение для цыганки
                        //     self::ciganka_message($victim, $vicMess);
                        // }
                    }
                }
                break;
            case 2: 
                if(self::isTreated($gameParams['ninzya_select'], $game, $gameParams)) {                    
                    $mess = ['text'=>"Этой ночью 🥷🏻Ниндзя кинул свой сюрикен в тебя!
                                \nТебя пытались вылечить, но 🥷🏻Ниндзя оказался сильнее"];
                    $bot->sendAnswer([$mess],$victim->user_id);
                    self::user_kill($ninzya->id,$gameParams['ninzya_select']);
                    //сообщение для цыганки
                    self::ciganka_message($victim, $mess['text']);
                }
                else {
                    $mess = ['text'=>"Этой ночью 🥷🏻Ниндзя кинул свой сюрикен в тебя!"];
                    $bot->sendAnswer([$mess],$victim->user_id);
                    self::user_deactivate(['killer_id'=>$ninzya->id, 'cmd_param'=>$gameParams['ninzya_select'], 'ninzya'=>2]);
                    //сообщение для цыганки
                    self::ciganka_message($victim, $mess['text']);
                }
                break;
            case 3:  
            case 4:    
                if(self::isTreated($gameParams['ninzya_select'], $game, $gameParams)) {
                    $victim = GameUser::where('id',$gameParams['ninzya_select'])->first();
                    $mess = ['text'=>"Этой ночью 🥷🏻Ниндзя кинул свой сюрикен в тебя!
                                \nТебя пытались вылечить, но 🥷🏻Ниндзя оказался сильнее"];
                } 
                else {
                    $mess = ['text'=>"Этой ночью 🥷🏻Ниндзя кинул свой сюрикен в тебя!"]; 
                } 
                $bot->sendAnswer([$mess],$victim->user_id); 
                self::user_deactivate(['killer_id'=>$ninzya->id, 'cmd_param'=>$gameParams['ninzya_select'], 'ninzya'=>$ninzyaLevel]);
                //сообщение для цыганки
                self::ciganka_message($victim, $mess['text']);
                break;
        }         
    }
}