<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\DeactivatedCommand;

trait MainNinzya {
    public static function mainninz_select($params) {
        self::gamer_set_move($params,'mainninz_select','mainninz_select_itog');        
    }    
    public static function mainninz_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $mninz = GameUser::where('game_id', $game->id)->where('role_id', 31)->first();
        if(!isset($gameParams['mainninz_select'])) return null; //ошибочный запуск функции        
        if(!$mninz || !self::isCanMove($mninz)) return null; 

        $victim = GameUser::where('id',$gameParams['mainninz_select'])->first();
        if($victim && $victim->role_id == 30) {
            $message = ['text'=>"Ваш выбор верный, ".Game::userUrlName($victim->user)." — 🥷🏻Ниндзя! Обучайте его, чтобы повышать его уровень"];
            $victimMes = ['text'=>"🔍Главный ниндзя посетил тебя и убедился, что ты его ученик!"];
            GamerParam::saveParam($mninz,'main_ninz_pupil',$gameParams['mainninz_select'],false);
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'main_ninzya_find']);  
        }
        else {
            $message = ['text'=>"Ваш выбор неверный"];
            $victimMes = ['text'=>"🔍Главный ниндзя проверил, его ли ты ученик.."];
        }
        $bot = AppBot::appBot();
        $bot->sendAnswer([$message],$mninz->user_id);
        $bot->sendAnswer([$victimMes],$victim->user_id);
        //сообщение для цыганки
        self::ciganka_message($victim, $victimMes['text']);

    }
    public static function mainninz_teach($params) {
        $params['cmd_param'] = 1;
        self::gamer_set_move($params,'mainninz_teach','mainninz_teach_itog'); 
    }
    public static function mainninz_teach_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $mninz = GameUser::where('game_id', $game->id)->where('role_id', 31)->first();
        if(!isset($gameParams['mainninz_teach'])) return null; //ошибочный запуск функции        
        if(!$mninz || !self::isCanMove($mninz)) return null; 

        $beforeNightParams = GamerParam::gameBeforeNightsParams($game);
        $victim = GameUser::where('id',$beforeNightParams['main_ninz_pupil'])->first();
        if(!$victim) return;  //если пытаются несанкционированно использовать команду обучения, а ученик реально не найден
        $bot = AppBot::appBot();
        if(!$victim->isActive()) {
            $message = ['text'=>"🥷🏻Ниндзя умер, так и не пройдя обучение полностью"];    
            $bot->sendAnswer([$message],$mninz->user_id);
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'main_ninzya_teach']); 
            return;
        }
        $ninzyaLevel = $beforeNightParams['ninzya_level'] ?? 1;
        $ninzyaLevel = min($ninzyaLevel+1, 4);
        GamerParam::saveParam($mninz,'ninzya_level',$ninzyaLevel,false);
        $victimMes = ['text'=>"🔍Главный ниндзя обучил тебя новым навыкам! Теперь твой уровень: $ninzyaLevel"];
        $message = ['text'=>"Ты обучил 🥷🏻Ниндзю новым навыкам! Теперь его уровень: $ninzyaLevel"];
        if($ninzyaLevel > 3) {
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'main_ninzya_teach']); 
            GamerParam::saveParam($mninz,'afk',$mninz->id);
        }
        
        $bot->sendAnswer([$message],$mninz->user_id);
        $bot->sendAnswer([$victimMes],$victim->user_id);
    }
}