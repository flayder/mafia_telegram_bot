<?php
namespace App\Modules\Game\Roles;

use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Poet {
    public static function poetfind($params) {  //поэт. ночное путешествие
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if(!$gamer) return '';
        GamerParam::saveParam($gamer,'poetfind',$params['cmd_param']);
        NightFunction::push_func($gamer->game, 'poetfind_itog');
    }
    public static function poetfind_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['poetfind'])) return null; //ошибочный запуск функции
        $victim = GameUser::where('id',$gameParams['poetfind'])->first(); //потерпевший
        if(!$victim) return null; //ошибочный запуск функции
        $poet = GameUser::where('game_id',$game->id)->where('role_id',3)->first();
        if(!$poet || !self::isCanMove($poet)) {  //обездвижен           
            return;
        }
        
        //кого видит
        DB::table('limit_select')->where(['gamer_id'=>$poet->id])->delete();
        LimitSelect::create(['gamer_id'=>$poet->id,'limit_select'=>$gameParams['poetfind']]);
        
        $viewActions = ['karleone_select','puaro_kill','manjak_select','dvulikiy_kill','oderjim_kill','ninzya_select','vedma_kill'];
        $poetTxtArr = [];
        foreach($viewActions as $action) {
            if(isset($gameParams[$action]) && $gameParams[$action] == $gameParams['poetfind']) {                
                //$kogoUvidel = GameUser::where(['game_id'=>$game->id,'role_id'=>$role->id])->first();
                $uvidelParam = GamerParam::where(['param_name'=>$action, 'game_id'=>$game->id, 'night'=>$game->current_night])->first();
                $kogoUvidel = null;
                if($uvidelParam) $kogoUvidel = $uvidelParam->gamer;
                if($kogoUvidel) {
                    $curTxt = "(". Game::userUrlName($kogoUvidel->user).' - '.$kogoUvidel->role.")";
                    //если найдется баф, подменим текст
                    $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$game->id,'user_id'=>$kogoUvidel->user_id,'is_active'=>1])->get();
                    foreach($activeBafs as $activeBaf) {
                      //  Log::channel('daily')->info('ищем баф...');
                        $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
                        $actbaf = new $class($activeBaf);
                        $result = $actbaf->visit_role($kogoUvidel);
                        if($result) {
                          //  Log::channel('daily')->info('нашли...');
                            $curTxt = "Ты не смог рассмотреть лица убийцы...";
                            break;
                        }
                    }
                    $poetTxtArr[] = $curTxt;
                }
                //break;
            }
        }
        if($poetTxtArr) {
            $poetTxt = "В поисках вдохновения ты пришёл к ".Game::userUrlName($victim->user).". Ты заметил кого-то интересного\n".
            implode("\n",$poetTxtArr)."\n\n Эта информация может понадобиться тебе в будущем...";
        }
        else $poetTxt = "В поисках вдохновения ты пришёл к ".Game::userUrlName($victim->user).". Никого интересного ты не заметил.. ";
        $message = ['text'=>$poetTxt];
       // Log::channel('daily')->info('сообщение поэту: '.print_r($message,true));
        Game::message(['message'=>$message,'chat_id'=>$poet->user_id]);
        //но дополнительно поэт еще убьет жертву, если к нему зашел шутник
        if(isset($gameParams['shutnik_poet']) && !self::isTreated($victim->id, $game) ) {
            self::user_kill($poet->id, $victim->id); //убивает вдохновением...
        }
    }
    public static function poet_butylka($params) {        
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            $isDeactivated = DeactivatedCommand::where(['game_id'=>$gamer->game_id,'command'=>'poet_butylka'])->first();
            if($isDeactivated) {
                //ничего не делаем. функция уже отработала
                return;
            }
            DeactivatedCommand::create(['game_id'=>$gamer->game_id,'command'=>'poet_butylka']); //сразу деактивируем
            GamerParam::saveParam($gamer,'poet_butylka',$params['cmd_param']);
            NightFunction::push_func($gamer->game, 'poet_butylka_itog');
            self::execBafMethod($gamer, 'shot');
        }        
    }
    public static function poet_butylka_itog($game) {
        //Log::channel('daily')->info('poet_butylka_itog ... game = '.$game->id." night = ".$game->current_night);
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['poet_butylka'])) {
           // Log::channel('daily')->info('не найден параметр poet_butylka');
            return null; //случайный запуск
        }
        $poet = GameUser::where('role_id',3)->where('game_id',$game->id)->first();    
        if(!$poet || !self::isCanMove($poet)) {
         //   Log::channel('daily')->info('поэт не найден или обездвижен');
            return null; //обездвижен
        }
        $victim = GameUser::where('id', $gameParams['poet_butylka'])->first();
        
        
        // if(self::isTreated($gameParams['poet_butylka'], $game)) {
        //    // Log::channel('daily')->info('док спас жертву');
        //    if($gameParams['poet_butylka'] == 23) {
        //         $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
        //         $bot = AppBot::appBot();
        //         $bot->sendAnswer([['text'=>$text]],$poet->user_id);
        //     } 
        //     return null; //доктор спас
        // }
        
        if(isset($gameParams['shutnik_poet'])) {
            $gameParams['poet_butylka'] = $poet->id;
          //  Log::channel('daily')->info('пришел шутник. убьет себя');
        }
        if($victim && $victim->role_id == 23) {
            self::user_kill($poet->id, $gameParams['poet_butylka']);
        } elseif(!self::isTreated($gameParams['poet_butylka'], $game)) {                   
            self::user_kill($poet->id, $gameParams['poet_butylka']);             
        }
    }
}