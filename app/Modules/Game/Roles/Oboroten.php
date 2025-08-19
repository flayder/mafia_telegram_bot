<?php
namespace App\Modules\Game\Roles;

use App\Models\Union;
use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\NightFunction;
use App\Models\UnionParticipant;
use App\Models\DeactivatedCommand;

trait Oboroten {
    public static function oboroten_sel_maf($params) {                
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'oboroten_sel',2);           
        }  
        NightFunction::push_func($gamer->game,'oboroten_itog',50);
        return '';        
    }
    public static function oboroten_sel_mir($params) {        
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'oboroten_sel',1);           
        }  
        NightFunction::push_func($gamer->game,'oboroten_itog',50);
        return '';        
    }
    public static function oboroten_itog($game) {
        //если накрыла красотка, выбор этой ночи отменяем
        $gameParams = GamerParam::gameParams($game);
        $oboroten = GameUser::where('game_id',$game->id)->where('role_id',37)->first();
        if(!$oboroten) return null; //ошибочный вызов
        if(!self::isCanMove($oboroten)) {
            //выбор анулируется
            GamerParam::deleteAction($game, 'oboroten_sel');             
        }
        else {
            DeactivatedCommand::create(['game_id'=>$oboroten->game_id, 'command'=>'oboroten_sel']);
        }
    }
    public static function ifOborotenReincarnated($game) {
        $param = GamerParam::where(['game_id'=>$game->id,'night'=>$game->current_night,'param_name'=>'obor_reincarnate'])->first();
        if($param) {            
            $gamer = $param->gamer;
            if(!$gamer) return null;
            

            if(!$gamer->killers) {
                $valueArr = explode(',',$param->param_value);
                $message = ['text'=>"Тебя убил  {$valueArr[0]}. Ты перевоплотился в {$valueArr[1]}"];
                Game::message(['message'=>$message, 'chat_id'=>$gamer->user_id]);
                //сообщение для цыганки
                self::ciganka_message($gamer, $message['text']);

                $message = ['text'=>"<i>🐾Оборотень сменил свой облик, перевоплотившись в {$valueArr[1]}...</i>"];
                Game::message(['message'=>$message, 'chat_id'=>$gamer->game->group_id]);
                //добавим в союз
                $isParticipant = UnionParticipant::where('gamer_id',$valueArr[2])->first();                        
                $union_id = 0;
                if($isParticipant) $union_id = $isParticipant->union_id;                            
                else {
                    $union = Union::create(['game_id'=>$gamer->game_id]);
                    $union_id = $union->id;
                    UnionParticipant::create(['union_id'=>$union_id,'gamer_id'=>$valueArr[2],'game_id'=>$gamer->game_id]);
                }
                $pos_in_unoin = $gamer->role_id == 25 ? 2 : 0;
                UnionParticipant::create(['union_id'=>$union_id,'gamer_id'=>$gamer->id,'game_id'=>$gamer->game_id,'pos_in_union'=>$pos_in_unoin]);

                $text_arr = ["<b>Команда :</b>\n"];     
                $unionGamers = UnionParticipant::with('gamer')->where('union_id',$union_id)->orderBy('pos_in_union')->orderBy('id')->get();
                foreach($unionGamers as $uGamer) {      
                    if($uGamer->gamer->isActive()) {
                        $text_arr[] = Game::userUrlName($uGamer->gamer->user).' - '.$uGamer->gamer->role;
                    }
                }                 
                $message = ['text'=>implode("\n",$text_arr)];
                Game::message(['message'=>$message, 'chat_id'=>$gamer->user_id]);
                
                if(!$gamer->isActive()) {
                    $gamer->update(['is_active' => 1]);
                }
            }
            
            $param->delete();
        }
    }
}