<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\NightFunction;
use App\Models\UnionParticipant;
use Illuminate\Support\Facades\Log;
use App\Modules\Bot\AppBot;


trait Mafiya {
    public static $isMafiyaItog = false;
    public static function karleone_select($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'karleone_select',$params['cmd_param']);
            //послать союзу выбор
            $whoSelect = GameUser::where('id',$params['cmd_param'])->first();            
            $message = ['text'=>Game::userUrlName($gamer->user).' '.$gamer->role.' выбор: '.$whoSelect->user];
            
            $particip = UnionParticipant::where('gamer_id',$gamer->id)->first();
            if($particip) {
                $allParticips = UnionParticipant::with('gamer')->where('union_id', $particip->union_id)->get();
                foreach($allParticips as $messReciever) {
                    if(!$messReciever->gamer || !$messReciever->gamer->isActive()) continue;
                    if($messReciever->gamer_id == $gamer->id) continue;                    
                    Game::message(['message'=>$message, 'chat_id'=>$messReciever->gamer->user_id]);
                }
            }
            NightFunction::push_func($gamer->game, 'mafiya_select_itog');

            //предлагаем использовать пистолет, если есть
            self::execBafMethod($gamer, 'shot');
        }  
    }
    public static function mafiya_select($params) {        
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'mafiya_select',$params['cmd_param']);
            //послать союзу выбор
            $whoSelect = GameUser::where('id',$params['cmd_param'])->first();            
            $message = ['text'=>Game::userUrlName($gamer->user).' выбор: '.$whoSelect->user];

            $particip = UnionParticipant::where('gamer_id',$gamer->id)->first();
            if($particip) {
                $allParticips = UnionParticipant::with('gamer')->where('union_id', $particip->union_id)->get();
                foreach($allParticips as $messReciever) {
                    if($messReciever->gamer_id == $gamer->id) continue;
                    if(!$messReciever->gamer || !$messReciever->gamer->isActive()) continue;
                    Game::message(['message'=>$message, 'chat_id'=>$messReciever->gamer->user_id]);
                }
            }

            NightFunction::push_func($gamer->game, 'mafiya_select_itog');
        }  
    }
    public static function isCanMoveDon($game, $gamer_id, $don_select) {
        $krasotka = self::getKrasotka($game->id);
        $noCan = false;
        if($krasotka && $don_select == $krasotka->id && !self::isTreated($krasotka->id, $game)) { //Дон выбрал красотку и Док ее не лечит
            $noCan = self::isVedmaFrost($game,$gamer_id); //тогда учитываем только выбор Ведьмы
            $gameParams = GamerParam::gameParams($game);
            if(isset($gameParams['krasotka_select']) && $gameParams['krasotka_select'] == $gamer_id) { //а если красотка выбрала Дона, то анулируем ее выбор
                GamerParam::deleteAction($game, 'krasotka_select');
            }
        }
        else {
            $noCan = self::isVedmaFrost($game,$gamer_id) || self::isKrasotkaSelect($game,$gamer_id);        
        }
        return !$noCan || self::isTreated($gamer_id, $game);
    }
    public static function mafiya_select_itog($game) {
        if(self::$isMafiyaItog) return null; //уже итог подведен
        self::$isMafiyaItog = true;
        //Log::channel('daily')->info("mafiya_select_itog ...");       
        $don = null;
        $gameParams = GamerParam::gameParams($game);        
        //проверить каждого из мафов и дона на возможность хода
        $params = GamerParam::where(['game_id'=>$game->id,'night'=>$game->current_night])->whereIn('param_name',['karleone_select','mafiya_select'])->get();       
        $results1 = [];
        $karleone_select = null;
        foreach($params as $param) {
            /*
            if(!self::isCanMove2($game, $param->gamer_id)) {
                if($param->param_name == 'karleone_select') return null;  //дон сделал ход и красотка его накрыла - убийства не будет
                continue;  //пропускаем неспособных сделать ход
            } */
            if($param->param_name == 'karleone_select') {
                $karleone_select = $param->param_value;
                $don = $param->gamer;  //Дона нужно получать только так. Потому что они меняются в процессе игры
                if(!self::isCanMoveDon($game, $param->gamer_id, $karleone_select)) return null; //убийства не будет                
            }
            else if(!self::isCanMove2($game, $param->gamer_id)) { //обездвиженный, но не Дон
                continue;  //не засчитываем выбор
            }
            else $results1[$param->param_value] = ($results1[$param->param_value] ?? 0) + 1;
        }        
        if($karleone_select) {
            $results1[$karleone_select] = 1000; 
        } else {
            $a_don = GameUser::where('game_id',$game->id)->where('role_id',17)->where('is_active',1)->first();
            $donhod = null;
            if($a_don)
                $donhod = GamerParam::where(['night'=>$game->current_night,'gamer_id'=>$a_don->id])->first();
            
            if($a_don && $donhod && $donhod->param_name == 'nightactionempty') { //дон пропустил ход. мафия тоже не может убить
                $text = "Мафия не определилась. Никто не будет убит";
                goto maf_mes;
            }
        }
        arsort($results1, SORT_NUMERIC);
        $results = [];
        foreach($results1 as $victim_id => $r_count) {
            $results[] = (object) ['gamer_id'=>$victim_id, 'r_count'=>$r_count];
        }               
        
        if(isset($results[1]) && $results[1]->r_count == $results[0]->r_count) {
            $text = "Мафия не определилась. Никто не будет убит";
        }
        else if(!isset($results[0])) {
            $text = "Мафия не определилась. Никто не будет убит";
        }
        else {       
            $killer = null;     
            if($don) $killer = $don; //Don            
            if(!$killer) {
                $killer = GameUser::where('game_id',$game->id)->where('role_id',25)->where('is_active',1)->first();  //Маф                
            }
            if(!$killer) {
               $param = GamerParam::where('game_id', $game->id)->whereIn('param_name',['mafiya_select', 'karleone_select'])->first();
               $killer = $param->gamer;
            }           

            $gamer = GameUser::where('id',$results[0]->gamer_id)->first();
            if($gamer) {
                $text = "Жертва выбрана, сегодня умрет ".$gamer->user;
                if($gamer->role_id == 23) {
                    $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
                }                        
                if(!self::isTreated($gamer->id, $game)) {  //доктор или ведьма или маф-док спасает                    
                    //не нужно сообщать мафам что кого-то спас доктор
                    //$text = "Мафы: ".$gamer->user."\nДоктор спас ".$gamer->user;          
                    //проверим, не уголовник ли это
                    if($gamer->role_id != 23) {
                        self::user_kill($killer->id, $gamer->id); 
                    }
                }

                
            }
        }      
        maf_mes: 
        $mafunParam = GamerParam::where(['game_id'=>$game->id,'param_name'=>'maf_union'])->first();
        $message = ['text' => $text]; 
        if(!$don) $don = $a_don;
        if($mafunParam) { //разослать всем, кто в союзе
            $participants = UnionParticipant::with('gamer')->where('union_id',$mafunParam->param_value)->get();
            foreach($participants as $particip) {
                if($particip->gamer && $particip->gamer->isActive()) {
                    Game::message(['message' => $message, 'chat_id' => $particip->gamer->user_id]);                
                }
            }
        }
        else if($don) { //надо карлеону отправить            
            Game::message(['message' => $message, 'chat_id' => $don->user_id]);
        }       
    }
    /*
    public static function mafiyaIsTop($game) {
        $param = GamerParam::where(['game_id'=>$game->id,'night'=>$game->current_night,'param_name'=>'obor_reincarnate'])->first();
        if($param) {
            $gamer = $param->gamer;
            if($gamer->isActive()) {
                $message = ['text' => "Ты повышен до роли 🤵🏻 Дон Корлеоне"];
                Game::message(['message' => $message, 'chat_id' => $gamer->user_id]);
            }
            else {

            }
        }
    }*/
}