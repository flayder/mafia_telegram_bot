<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Models\LimitSelect;
use App\Modules\Game\Game;
use App\Models\NightFunction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Krasotka {
    public static function krasotka_select($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if(!$gamer) return '';
        GamerParam::saveParam($gamer,'krasotka_select',$params['cmd_param']);
        NightFunction::push_func($gamer->game, 'krasotka_itog',15);
    }
    public static function getKrasotka($game_id) {
        return GameUser::where('game_id',$game_id)->where('role_id',6)->first();
    }
    public static function krasotka_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['krasotka_select'])) return null; //ошибочный запуск функции
        $krasotka = self::getKrasotka($game->id);
        if(!$krasotka) return null; //ошибочный запуск функции

        if(self::jokerNotSelectKrasotka($game)) {
            if(!self::isCanMove($krasotka) || self::isSutenerSelect($game, $krasotka->id)) {
                self::saved_clear_action(GamerParam::deleteAction($game, 'krasotka_select'));    
                GamerParam::saveParam($krasotka,'nightactionempty',1);
                return null;
            }
        } else {
            self::saved_clear_action(GamerParam::deleteAction($game, 'shutnik_select'));
            return null;
        }

        $victim = GameUser::where('id',$gameParams['krasotka_select'])->first(); //потерпевший
        if(!$victim || !$victim->isActive()) return null; //ошибочный запуск функции

        DB::table('limit_select')->where(['gamer_id'=>$krasotka->id])->delete();
        LimitSelect::create(['gamer_id'=>$krasotka->id,'limit_select'=>$gameParams['krasotka_select']]);

        $vicimMessage = null;
        $krasotkaMessage = ['text'=>"Сегодня вы очаровали своей красотой ".Game::userUrlName($victim->user)];

        if(in_array($victim->role_id,[13,23])) {  //крестный отец или уголовник
            if($victim->role_id == 13) self::victim_message($victim->id,"Сегодня 💃Красотка пыталась вас очаровать, однако у нее ничего не вышло."); 
            self::saved_clear_action(GamerParam::deleteAction($game, 'krasotka_select'));    
            GamerParam::saveParam($krasotka,'nightactionempty',1);           
        }
        else {            
            self::victim_message($victim->id,"Сегодня вы под чарами 💃Красотки!");
            $check = GamerParam::where(['game_id'=>$game->id,'param_name'=>'lubovnik_find','param_value'=>$krasotka->id])->first();
            if($check && $check->gamer->isActive()) {
                $lubovnikMessage = ['text'=>"💃Красотка очаровала этой ночью ".Game::userUrlName($victim->user)];
                if($check->night == $game->current_night) $lubovnikMessage['text'] .= ' - '.$victim->role;
                Game::message(['message'=>$lubovnikMessage,'chat_id'=>$check->gamer->user_id]);        
            }            
        }               
        Game::message(['message'=>$krasotkaMessage,'chat_id'=>$krasotka->user_id]);        
    }
    public static function isKrasotkaSelect($game, $gamer_id) {
        $gameParams = GamerParam::gameParams($game);
        return isset($gameParams['krasotka_select']) && $gameParams['krasotka_select'] == $gamer_id;
    }


}