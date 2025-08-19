<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\Log;

trait Dvulikiy {
    public static function dvulikiy_select($params) {
        self::gamer_set_move($params, 'dvulikiy_select', 'dvulikiy_select_itog');
    }
    public static function dvulikiy_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $dvulikiy = GameUser::where('game_id', $game->id)->where('role_id', 20)->first();
        if(!isset($gameParams['dvulikiy_select'])) return null; //ошибочный запуск функции        
        if(!$dvulikiy || !self::isCanMove($dvulikiy)) return null;

        //проверим нашел он или нет.
        $check = GameUser::where('id', $gameParams['dvulikiy_select'])->first();
        $text = null;
        
        if($check && in_array($check->role_id,[17,18,19,25])) { //нашел одного из мафии
            $text = "Вот и он, твой выбор был верный ".Game::userUrlName($check->user)." — ".$check->role."! Теперь в твои руки попал нож...";
            DeactivatedCommand::firstOrCreate(['game_id'=>$game->id,'command'=>'dvulikiy_find']);
        }
        else {
            $text = "Твой выбор не верный!";
        }
        $bot = AppBot::appBot();
        $bot->sendAnswer([['text'=>$text]], $dvulikiy->user_id);
    }
    public static function dvulikiy_kill($params) {
        self::gamer_set_move($params, 'dvulikiy_kill', 'dvulikiy_kill_itog',100,false,'shot');        
    }
    public static function dvulikiy_kill_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $dvulikiy = GameUser::where('game_id', $game->id)->where('role_id', 20)->first();
        if(!isset($gameParams['dvulikiy_kill'])) return null; //ошибочный запуск функции
        if(!$dvulikiy || !self::isCanMove($dvulikiy)) return null; 
        $gamer = GameUser::where('id', $gameParams['dvulikiy_kill'])->first();
        if($gamer && $gamer->role_id == 23) {
            $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
            $bot = AppBot::appBot();
            $bot->sendAnswer([['text'=>$text]], $dvulikiy->user_id);
        }
        else if(!self::isTreated($gameParams['dvulikiy_kill'],$game)) {
            self::user_kill($dvulikiy->id,$gameParams['dvulikiy_kill']);
        }
    }
    public static function ifDvulikiyShouldHaveKnife($game) {
        $activeMafia = $game->gamers()->with('role')->where('is_active', 1)->whereIn('role_id', [17,25])->first();
        $dvulikiy = $game->gamers()->with('role')->where('is_active', 1)->where('role_id', 20)->first();

        if($dvulikiy && !$activeMafia) {
            //если за всю игру двуликий так и не нашел мафию
            $dvParams = GamerParam::with('gamer')->whereIn('param_name', ['dvulikiy_select', 'dvulikiy_select_auto'])->where('game_id', $game->id)->get();
            //$gameParams = GamerParam::gameParams($game);  
            $selectAuto = false;
            $foundMafia = false;


            foreach($dvParams as $param) {
                if($param->param_name == 'dvulikiy_select' && in_array($param->gamer->role_id,[17,18,19,25])) {
                    $foundMafia = true;
                    continue;
                }

                if($param->param_name == 'dvulikiy_select_auto') {
                    $selectAuto = true;
                    continue;
                }
            }

            // Log::info('ifDvulikiyShouldHaveKnife', [
            //     '$dvParams' => print_r($dvParams, true),
            //     '$selectAuto' => print_r($selectAuto, true),
            //     '$foundMafia' => print_r($foundMafia, true)
            // ]);

            //получение сообщения и вывод ссобщения в общий чат если двуликий получает нож автоматически после выбывания убивающей мафии из игры
            if(!$foundMafia && !$selectAuto) {
                GamerParam::saveParam($dvulikiy, 'dvulikiy_select_auto', 1);
                DeactivatedCommand::firstOrCreate(['game_id'=>$game->id,'command'=>'dvulikiy_find']);
                $bot = AppBot::appBot();
                $bot->sendAnswer([['text'=>'Все лидеры мафии выбыли из игры! Теперь в твои руки попал нож...']], $dvulikiy->user_id);
                $bot->sendAnswer([['text'=>'Все лидеры мафии выбыли из игры! 🎭Двуликий получает нож...']], $game->group_id);
            }

            return true;
        }

        return false;
    }
}