<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\NightFunction;
use App\Models\DeactivatedCommand;

trait Lover {
    public static function lubovnik_find($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if(!$gamer) return '';
        GamerParam::saveParam($gamer,'lubovnik_select',$params['cmd_param']);
        $victim = GameUser::where('id',$params['cmd_param'])->first(); //потерпевший
        if(!$victim || !$victim->isActive()) return null; //ошибочный запуск функции
        $message['text'] = "Вы выбрали ".Game::userUrlName($victim->user);
        /*
        if($victim->role_id == 6) { //нашел красотку
            $message['text'] = "Вы выбрали ".Game::userUrlName($victim->user).". ".
            Game::userUrlName($victim->user)." является красоткой.";            
        }
        else {
            $message['text'] = "Вы выбрали ".Game::userUrlName($victim->user).". ".
            Game::userUrlName($victim->user)." не является красоткой.";
        } */
        Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]); 
        NightFunction::push_func($gamer->game, 'lubovnik_itog',10); //делает ход перед красоткой
    }
    public static function lubovnik_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['lubovnik_select'])) return null; //ошибочный запуск функции
        $lubovnik = GameUser::where('game_id',$game->id)->where('role_id',7)->first();
        if(!$lubovnik || !self::isCanMoveWithoutKrasotka($lubovnik)) return null; //не может сделать ход        
        $victim = GameUser::where('id',$gameParams['lubovnik_select'])->first(); //потерпевший
        if(!$victim || !$victim->isActive()) return null; //ошибочный запуск функции
        if($victim->role_id == 6) { //нашел красотку
            GamerParam::saveParam($lubovnik,'lubovnik_find',$gameParams['lubovnik_select']);
            $message['text'] = "Поздравляем, ".Game::userUrlName($victim->user).' - '.$victim->role;
            Game::message(['message'=>$message,'chat_id'=>$lubovnik->user_id]); 
            DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'lubovnik_select']);  
            self::victim_message($victim->id,"🕺Любовник нашёл вас, теперь он видит ваш выбор и его роль... ");            
        }
        else {
            $message['text'] = Game::userUrlName($victim->user)." не является красоткой.";
            Game::message(['message'=>$message,'chat_id'=>$lubovnik->user_id]); 
        }  
    }
}