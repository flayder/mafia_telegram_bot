<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\DB;


trait Lunatik {
    public static function lunatik_select($params) {
        self::gamer_set_move($params, 'lunatik_select', 'lunatik_select_itog');
    }
    public static function lunatik_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['lunatik_select'])) return null; //ошибочный запуск функции
        $victim = GameUser::where('id',$gameParams['lunatik_select'])->first(); //потерпевший
        if(!$victim) return null; //ошибочный запуск функции
        $lunatik = GameUser::where('game_id',$game->id)->where('role_id',33)->first();
        if(!$lunatik || !self::isCanMove($lunatik)) {  //обездвижен           
            return;
        } 

        DB::table('limit_select')->where('gamer_id',$lunatik->id)->delete();
        LimitSelect::create(['gamer_id'=>$lunatik->id,'limit_select'=>$gameParams['lunatik_select']]);

        //получить все роли игроков
        $index = -1;
        $iters = 0;
        $arr = GameUser::where(['game_id'=>$game->id,'is_active'=>1])->get()->all();
        if(!$arr) { //не будет инфы так как нет пользователей кроме лунатикапше 
            return null;
        }
        do {
            $index = random_int(0, count($arr)-1);            
            $iters++;            
            if($iters > 10) {
                $index = -1;
                break;
            }
        }
        while($arr[$index]->role_id == $victim->role_id || $arr[$index]->id == $lunatik->id);
        if($index > -1) {
            $lunMessage = ['text'=>"Ночью ты пришел к ".Game::userUrlName($victim->user)." и узнал, что он не  ".
                            $arr[$index]->role ];
        }
        else {
            $lunMessage = ['text'=>"Ночью ты пришел к ".Game::userUrlName($victim->user)." и узнал, что он не  ".$lunatik->role];
        }
        $mess = "😴Лунатик решил узнать больше информации о тебе..";
        $bot = AppBot::appBot();
        $bot->sendAnswer([$lunMessage],$lunatik->user_id);
        $victimMess = ['text'=>$mess];
        $bot->sendAnswer([$victimMess],$victim->user_id);

        //сообщение для цыганки
        self::ciganka_message($victim, $mess);
    }
}