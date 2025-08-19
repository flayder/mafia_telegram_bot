<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

trait Ciganka {
    protected static $ciganka = null;
    protected static $ciganka_mess_text = null;

    public static function getCiganka($game_id) {
        if(!self::$ciganka) {
            self::$ciganka = GameUser::where('game_id', $game_id)->where(['role_id' => 12, 'is_active' => 1])->first();
        }
        return self::$ciganka;
    }
    public static function ciganka_select($params) {
        self::gamer_set_move($params, 'ciganka_select', 'ciganka_itog',101,false,'ciganka_view');       
    }
    public static function ciganka_itog($game) {
        $ciganka = self::getCiganka($game->id);
        if(!$ciganka || !isset($gameParams['ciganka_select'])) return null; //ошибочный запуск
        $gameParams = GamerParam::gameParams($game);
        $victim = GameUser::where('id', $gameParams['ciganka_select'])->first();
        if(!$victim) return null;
        $bot = AppBot::appBot();              
        if(!self::isCanMove($ciganka)) {//анулируем выбор
            GamerParam::deleteAction($game, 'ciganka_select');           
        }
        else if(isset($gameParams['prezident_cigankaview']) || isset($gameParams['oko_exec'])) {  //видим прошлую ночб вместо этой)
            if($game->current_night > 1) {
                $beforeNight = $game->current_night - 1;
                $param = GamerParam::where(['game_id'=>$game->id,'night'=>$beforeNight,
                'gamer_id'=>$victim->id,'param_name'=>'ciganka_message'])->first();
                if($param) {
                    $bot->sendAnswer([['text'=>"К вам заходила цыганка, кажется, она узнала что-то интересное за прошлую ночь..."]],
                        $victim->user_id);                             
                    $bot->sendAnswer([['text'=>"Вы посетили ".Game::userUrlName($victim->user).
                    ". Вы увидели интересную информацию за прошлую ночь:\n".$param->param_value]],$ciganka->user_id);                                    
                }
                else {
                    $bot->sendAnswer([['text'=>"Вы посетили ".Game::userUrlName($victim->user).
                    ". Вы не увидели ничего интересного за прошлую ночь"]],$ciganka->user_id);
                }
            }
        } else if(!isset($gameParams['ciganka_message_user'.$victim->id])) {
            $bot->sendAnswer([['text'=>"Вы посетили ".Game::userUrlName($victim->user).". Вы не увидели там ничего интересного"]],$ciganka->user_id);
        }   
    }
    public static function ciganka_message($victim, $text) { 
        $ciganka = self::getCiganka($victim->game_id);
        if(!$ciganka || $ciganka && !self::isCanMove($ciganka)) return null;
        $gameParams = GamerParam::gameParams($victim->game);
        $fullText = $victim->id . '|' . md5(trim(preg_replace("/[^a-zа-я\s]/iu", "", $text)));
        if(!isset($gameParams['ciganka_select'])) return null;
        //защита от повторных смс при повторном посещении к жертве цыганки если код срабатывает больше 1 раза за ночной цикл
        if(isset($gameParams['ciganka_message_user'.$fullText])) return null;
        if(isset($gameParams['prezident_cigankaview']) || isset($gameParams['oko_exec'])) return null;
        
        if($gameParams['ciganka_select']==$victim->id && $text) {
            $bot = AppBot::appBot();
            GamerParam::saveParam($victim, 'ciganka_message_user'.$fullText, $text);

            Log::info('Ciganka params', [
                '$gameParams' => print_r($gameParams, true)
            ]);

            if(!isset($gameParams['ciganka_message_user'.$victim->id])) {
                GamerParam::saveParam($victim, 'ciganka_message_user'.$victim->id, 'message');
                $bot->sendAnswer([['text'=>"К вам заходила цыганка, кажется, она узнала что-то интересное..."]], $victim->user_id);
            }

            $bot->sendAnswer([['text'=> "Вы посетили " . Game::userUrlName($victim->user) . " Вы увидели интересную информацию:\n" . $text]], $ciganka->user_id);
        }  
    }
}
