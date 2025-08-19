<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Models\NightFunction;
use App\Models\UnionParticipant;
use Illuminate\Support\Facades\DB;

trait Advokat {
    public static function advokat_select($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'advokat_select',$params['cmd_param']);  
            NightFunction::push_func($gamer->game, 'advokat_itog',99); //раньше тех, у кого приоритет 100   
            
            $victim = GameUser::where('id',$params['cmd_param'])->first();
            if($victim) {
                $message = ['text'=>"👨🏼‍💼Адвокат готовит поддельные документы для ".Game::userUrlName($victim->user)];
                UnionParticipant::unionGamerMessage($gamer,$message);               
            }            
        }  
    }
    public static function advokat_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $gameParam = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night, 'param_name' => 'advokat_select'])->first();

        if(!$gameParam) return null; //ошибочный запуск функции
        $advokat = $gameParam->gamer;
        if(!$advokat) return null;
        if(!self::isCanMove($advokat)) {
            GamerParam::deleteAction($game,'advokat_select');            
        }
        else {
           if(isset($gameParams['advokat_select'])) {
                $victim = GameUser::where('id',$gameParams['advokat_select'])->first();
                if(!$victim) return;
                if($victim->role->role_type_id!=2) return;
                DB::table('limit_select')->where('gamer_id',$advokat->id)->where('limit_select','!=',$advokat->id)->delete();
                LimitSelect::create(['gamer_id'=>$advokat->id,'limit_select'=>$gameParams['advokat_select']]);
                if(isset($gameParams['puaro_check']) && $gameParams['puaro_check'] == $gameParams['advokat_select']) {
                    self::victim_message($gameParams['advokat_select'],'👨🏼‍💼Адвокат защитил тебя от проверки комиссара!');        
                }
                else {
                    self::victim_message($gameParams['advokat_select'],'👨🏼‍💼Адвокат взял тебя под свою защиту.');
                }
           }
        }
    }
}