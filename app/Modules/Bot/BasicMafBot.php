<?php
namespace App\Modules\Bot;

use Exception;
use App\Models\Union;
use App\Models\BotUser;
use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\LimitSelect;
use App\Models\ProhibitKill;
use App\Models\UnionParticipant;
use App\Models\Game as GameModel;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\Log;
use App\Modules\Game\GamerFunctions;

class BasicMafBot extends Bot {
   // abstract public function descr_actions();

    public function prepareAnswer($command,$chat_id,$callback_id=false,$hookUpdate=[]) {
        
        // Log::info('PrepareAnswerBasicMafBot', [
        //     'command'       => print_r($command, true),
        //     'group_id'      => $chat_id,
        //     '$callback_id'  => print_r($callback_id, true),
        //     'hookUpdate'    => print_r($hookUpdate, true)
        // ]);

        if($this->clearCommand($command) === 'Exchange') {
            $command = '💰 Обменник';
        }

        if($this->clearCommand($command) === 'Play') {
            $command = '🎲 Играть';
        }

        if($this->clearCommand($command) === 'Shop') {
            $command = '🛒 Магазин';
        }

        $command = $this->getCmd($command,$chat_id);     
        $shortCommand = null;
        $answ_arr = $this->findCommand($command);               
        $delim = '_';
        $com_arr = explode($delim,$command);
        while(!$answ_arr) { //попробуем разбить по разделителю и проверить на наличие обрезанных команд
            array_pop($com_arr);
            if($com_arr) {
                $shortCommand = implode($delim,$com_arr).$delim;
                $answ_arr = $this->findCommand($shortCommand); 
            }
            else {
                break;
            }
        }
        if(!$answ_arr) return null;
        $param = null;
        $replaceParams = [];

        // Log::info('$answ_arr', [
        //     '$answ_arr'       => print_r($answ_arr, true),
        // ]);

        $gamer = GameUser::where('user_id', $chat_id)->where('is_active',1)->first();
        $limitSelect = null;

        if($gamer ) {
            if($gamer->message_id && $callback_id && $gamer->message_id > $callback_id) {
                //это попытка обращения к прошлой ночи. не обрабатываем
                return [];
            }
            $limitSelect = LimitSelect::gamersLimits([$gamer->id]);
        }
        if($shortCommand) {
            $param = str_replace($shortCommand,'',$command);
            
            $cmdGamer = GameUser::where('id',$param)->first(); // GameUser
            if($cmdGamer) {
                $replaceParams['username'] = ''.$cmdGamer->user;                
            }
            
            $botUser = BotUser::where('id',$param)->first(); //может это пользователь BotUser
            if($botUser) $replaceParams['username'] = ''.$botUser;

            $gameRole = GameRole::where('id',$param)->first(); //а может быть и роль
            if($gameRole) $replaceParams['gamerole'] = ''.$gameRole;
            //выбор параметра будет сделан внутри самого сообщения
            
            if($gamer) { //подтягиваем игровые параметры
                $replaceParams = array_merge($replaceParams, GamerParam::gameParams($gamer->game));
            }
        }
        for ($i = 0; $i < count($answ_arr); $i++) { //лента
            //доп работа с объектом ответа
            $answObj = $answ_arr[$i];            
            //запретить днем ночное, а ночью дневное
            if($gamer && $gamer->game->times_of_day !== GameModel::NIGHT) {
                return [];  //все игровые действия - ночные
            }
            if(isset($answObj['caption'])) { //задано id роли. Ограничение доступности                
                if(!$gamer) return [];  //не игрок. 
                if($gamer->role_id.'' !== ''.$answObj['caption']) return [];  //роль не соответствует
            }
            $action = null;
            if(isset($answObj['description'])) { //особое действие и сообщение пользователю-жертве
                if(strpos($answObj['description'],'action:')!==false) {
                    $descRows = explode("\n",$answObj['description']);
                    $action = str_replace('action:','', $descRows[0]); 
                    $replaceParams['func_result'] = GamerFunctions::$action(['user_id'=>$chat_id, 'cmd_param'=>$param]);
                    if(count($descRows) > 1) {
                        array_shift($descRows);
                        $descr = implode("\n",$descRows);
                        if(!empty(trim($descr)) && $param && is_numeric($param)) {
                            try {
                                $this->sendAnswer([['text'=>$descr]],$param);
                            }
                            catch(Exception $e) {}
                        }
                    }                    
                }
                else {
                    if(!empty(trim($answObj['description'])) && $param && is_numeric($param)) {
                        try {
                            $this->sendAnswer([['text'=>$answObj['description']]],$param);
                        }
                        catch(Exception $e) {}
                    }
                }
            }
            if(!empty($answObj['title']) && $gamer) {  //сообщение в общую группу (в чат, где запущена игра)
                $this->sendAnswer([['text'=>$answObj['title']]],$gamer->game->group_id);           
            }
            if(isset($answObj['inline'])) {  //перебираем кнопки и ищем кнопку с патерном. Переопределяем inline
                $deactivations = [];
                if($gamer) {
                    $deactivations = DeactivatedCommand::all_deactivated($gamer->game_id);
                }                
                $newInline = [];
                for($ki=0;$ki<count($answObj['inline']);$ki++) {
                    $row = [];
                    for($kj=0;$kj<count($answObj['inline'][$ki]);$kj++) {
                        $inlBtn = $answObj['inline'][$ki][$kj];
                        if(in_array($inlBtn['callback_data'], $deactivations)) continue;
                        if(isset($inlBtn['pattern'])) {
                            if($gamer) {
                                $securedOfKill = ProhibitKill::where('expire_time','>',date('Y-m-d H:i'))->
                                where('group_id',$gamer->game->group_id)->
                                where('night_count','>=',$gamer->game->current_night)->get()->all();
                                $securedOfKillUserIds = array_column($securedOfKill,'user_id');
                            }
                            else $securedOfKillUserIds = [];
                            $pattern = $inlBtn['pattern'];
                            $model = "\\App\\Models\\".$pattern['model'];                               
                            if(isset($pattern['where'])) {
                                //Log::info("pattern : ".print_r($pattern,1));
                                $keys = []; 
                                $values = [];
                                foreach($pattern['params'] as $ck=>$cv) {                                    
                                    $keys[] = '#'.$ck;
                                    if($gamer && $gamer->$cv) $values[] = (string) $gamer->$cv;
                                    else $values[] = "";
                                }
                                if(strpos($pattern['where'],'#active_role_ids')) {
                                    $arr = GameUser::select('role_id')->where('is_active',1)->where('game_id',$gamer->game_id)->get()->all();
                                    $role_ids = array_column($arr,'role_id');
                                    $keys[] = '#active_role_ids';
                                    $values[] = implode(',',$role_ids);
                                }
                                $pattern['where'] = str_replace($keys,$values,$pattern['where']);
                                //Log::info("sql: model = $model , where = {$pattern['where']} ");
                                try {
                                    $itemList = $model::whereRaw($pattern['where'])->get();
                                }
                                catch(Exception $e) {
                                    Log::error("некорректный SQL: cmd = ".$command);
                                    die('OK');
                                }
                            } 
                            else $itemList = $model::all();
                         //   Log::channel('daily')->info('command = '.$command);
                            if($gamer->role_id==27 && $command == 'zelje_lechebn') {
                                //не исключаем себя из списка
                               // Log::channel('daily')->info(' не исключаем себя из списка ');
                                $unionGamerIds = [];
                            }
                            else $unionGamerIds = [$gamer->id];
                            $unionUserIds = [$gamer->user_id];
                            $participants = UnionParticipant::unionParticipantsByGamer($gamer);
                            
                            if($participants) {
                                $unionGamerIds = [];
                                $unionUserIds = [];
                                foreach($participants as $participant) { //не здесь ли перемешивание между играми?
                                    if(!$participant->gamer) continue;
                                    $unionGamerIds[] = $participant->gamer_id;
                                    $unionUserIds[] = $participant->gamer->user_id;
                                }                                
                            }
                            
                            /*
                            Log::channel('daily')->info("unionGamerIds: ".implode(',',$unionGamerIds));
                            Log::channel('daily')->info("gamer id = ".$gamer->id);
                            */
                            foreach($itemList as $item) {
                                
                                if(!in_array($gamer->role_id,Game::WHO_MAY_SELF) && $pattern['model'] == 'GameUser') {
                                    if(in_array($item->id, $unionGamerIds)) continue;
                                }
                                if(isset($inlBtn['is_kill']) && $pattern['model']=='GameUser' && in_array($item->user_id,$securedOfKillUserIds)) {
                                    continue;
                                }

                                if($pattern['model']=='GameUser' && isset($limitSelect[$gamer->id]) &&
                                    in_array($item->id, $limitSelect[$gamer->id])) continue;
                            
                                if($pattern['model'] == 'BotUser') {
                                    if(in_array($item->id, $unionUserIds)) continue;
                                }
                                
                                
                                $keys = [];
                                $values = [];
                                foreach($pattern['params'] as $ck=>$cv) {
                                    $keys[] = '#'.$ck;
                                    $value = (string) $item->$cv;
                                    if($pattern['model']=='GameUser' && $ck == 'username' && $item->team) {
                                        $value = Game::COMMAND_COLORS[$item->team].$value;
                                    }
                                    $values[] = $value;                                    
                                }
                                $newInline[] = [                                    
                                    ["text"=>str_replace($keys,$values,$inlBtn['text']),
                                    "callback_data"=>str_replace($keys,$values,$inlBtn['callback_data'])]
                                ];  
                            }
                        }
                        else {
                            $row[] = $inlBtn;
                        }
                    }
                    $newInline[] = $row;
                }
                if($newInline) $answ_arr[$i]['inline'] = $newInline;                
                else if(isset($answ_arr[$i]['inline'])) unset($answ_arr[$i]['inline']);
            }            

            //дальше стандартное поведение
			$res = $this->getResObj($answ_arr,$i,$chat_id,$shortCommand ?? $command,$replaceParams);
            if($gamer) $res['saver'] = new GamerSaver($gamer);
			//if(isset($deleteprev)) $res['deleteprev']=$deleteprev;
            if(isset($res['innercmd'])) $this->addCmd($res['innercmd'], $chat_id);
			$fres[] = $res;

		}
        return $fres;        
    }
}