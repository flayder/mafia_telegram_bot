<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\Log;

trait Prezident {
    public static function change_mirs($params)  //президент меняет миров
    {     
        $params['cmd_param'] = 1;      
        self::gamer_set_move($params, 'change_mirs', 'change_mirs_itog',99);
    }
    public static function change_mirs_itog($game) {
        Log::info('change_mirs_itog');    
        $gameParams = GamerParam::gameParams($game);        
        $prezident = GameUser::where('game_id', $game->id)->where('role_id', 1)->first();
        if(!isset($gameParams['change_mirs'])) return null; //ошибочный запуск функции        
        if(!$prezident || !self::isCanMove($prezident)) return null;                
        $resul = DeactivatedCommand::create(['game_id'=>$prezident->game_id, 'command'=>'prezident_changemir']);                
        $deactivateList = DeactivatedCommand::all_deactivated($prezident->game_id);
        if(in_array('prezident_cigankanight',$deactivateList)) {
            //если обе команды деактивированы, то деактивируем ночное сообщение
            DeactivatedCommand::create(['game_id'=>$prezident->game_id, 'command'=>'prezident_ukaz']);
        }
        //получим всех миров
        $gamers = GameUser::with('role')->where('is_active', 1)->where('game_id',$prezident->game_id)->get();
        $mirs = [];
        $nightLivers = [];
        foreach($gamers as $gamer) {
            if($gamer->role_id == 2) {  
                $nightLivers[] = $gamer;
            }
            else if($gamer->role->role_type_id == 1) {
                $mirs[] = $gamer;
            }
        }
        if(count($nightLivers) < 1) {
            $message = ['text' => "☠️Житель ночи умер, так и не дождавшись своего звёздного часа…"];
            Game::message(['message' => $message, 'chat_id' => $prezident->user_id]);
            return 0;
        }

        $curMir = random_int(0, count($mirs)-1);
        // $curMir = 0;
        // foreach($mirs as $k => $m) {
        //     if($m->user_id == 7702326048) {
        //         $curMir = $k;
        //         break;
        //     }
        // }
        $curMirUserId = $mirs[$curMir]->user_id;
        $curMirSortId = $mirs[$curMir]->sort_id;
        $curMirTeam = $mirs[$curMir]->team;
        // Log::info('Prezident change before #1', [
        //     '$mirs[$curMir]' => print_r($mirs[$curMir], true)
        // ]);
        // Log::info('Prezident change before #2', [
        //     '$nightLivers[0]' => print_r($nightLivers[0], true)
        // ]);
        $mirs[$curMir]->user_id = $nightLivers[0]->user_id;
        $mirs[$curMir]->sort_id = $nightLivers[0]->sort_id;
        $mirs[$curMir]->team = $nightLivers[0]->team;
        $mirs[$curMir]->save();
        $mirs[$curMir]->refresh();
        $nightLivers[0]->user_id = $curMirUserId;
        $nightLivers[0]->sort_id = $curMirSortId;
        $nightLivers[0]->team = $curMirTeam;
        $nightLivers[0]->save();
        $nightLivers[0]->refresh();

        // Log::info('Prezident change before #3', [
        //     '$mirs[$curMir]' => print_r($mirs[$curMir], true)
        // ]);
        // Log::info('Prezident change before #4', [
        //     '$nightLivers[0]' => print_r($nightLivers[0], true)
        // ]);

        //уведомления о новых ролях        
        $message = ['text' => "<b>Твоя новая роль {$mirs[$curMir]->role}</b>\n\n{$mirs[$curMir]->role->first_message}"];
        Game::message(['message' => $message, 'chat_id' => $mirs[$curMir]->user_id]);
                
        $message = ['text' => "<b>Твоя новая роль {$nightLivers[0]->role}</b>\n\n{$nightLivers[0]->role->first_message}"];
        Game::message(['message' => $message, 'chat_id' => $nightLivers[0]->user_id]);
    }
    public static function prezident_cigankaview($params) {
        $params['cmd_param'] = 1;
        self::gamer_set_move($params, 'prezident_cigankaview', 'prezident_cigankaview_itog',99);
    }
    public static function prezident_cigankaview_itog($game) {
        $prezident = GameUser::where('game_id', $game->id)->where('role_id', 1)->first();
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['prezident_cigankaview'])) return null; //ошибочный запуск функции        
        if(!$prezident || !self::isCanMove($prezident)) {
            GamerParam::deleteAction($game, 'prezident_cigankaview');
            return null;
        }
        $ciganka = self::getCiganka($game->id);
        if(!$ciganka || !$ciganka->isActive()) {
            $bot = AppBot::appBot();
            $bot->sendAnswer([['text'=>"☠️Цыганка умерла, так и не дождавшись своего звёздного часа…"]],
                $prezident->user_id);
        }

        DeactivatedCommand::create(['game_id'=>$prezident->game_id, 'command'=>'prezident_cigankanight']);
        $deactivateList = DeactivatedCommand::all_deactivated($prezident->game_id);
        if(in_array('prezident_changemir',$deactivateList)) { 
            //если обе команды деактивированы, то деактивируем ночное сообщение
            DeactivatedCommand::create(['game_id'=>$prezident->game_id, 'command'=>'prezident_ukaz']);
        }
    }
}