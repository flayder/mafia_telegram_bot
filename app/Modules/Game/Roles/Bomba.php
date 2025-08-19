<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

trait Bomba {
    public static function bomba_kill($gamer, $killer) {
        //Дон Корлеоне, Маньяк, Двуликий, Ниндзя, Одержимый или Ведьма убийственным зельем
        $winIfKillerRole = [17, 25, 28, 20, 30, 10, 27]; //Дон Корлеоне, Маньяк, Двуликий, Ниндзя, Одержимый или Ведьма
        //Log::channel('daily')->info('bomba_kill: '.$gamer->id. ' : '.$killer->id);
        self::user_deactivate(['killer_id'=>$gamer->id, 'cmd_param'=>$killer->id],false); //убивает убийцу, 2-й параметр false = щит не спасет
        if(in_array($killer->role_id, $winIfKillerRole)) { //победила, если убил нужный игрок            
            $gamer->is_active = 2;
            $gamer->save();
        }
    }
    public static function bomba_gallow($gamer) {        
        //заберет с собой члена группировки мафии (кроме Уголовника, Журналиста и Адвоката), Маньяка, Ниндзю и Одержимого (если стал нейтралом)
        $message['text'] = "Вы были повешены. Кого хотите забрать с собой?";
        $bot = AppBot::appBot();
        $actives = GameUser::where('game_id',$gamer->game_id)->where('is_active',1)->get();   
        $tm = time();
        $message['inline_keyboard'] = $bot->inlineKeyboard($actives, 1, "bombaget_{$tm}_",false, 'id','user',[$gamer->id]);
        $bot->sendAnswer([$message],$gamer->user_id);
    }
}