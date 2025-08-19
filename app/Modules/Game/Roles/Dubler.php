<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\DeactivatedCommand;
use Illuminate\Support\Facades\Log;

trait Dubler {
    public static function dubler_select($params) {
        self::gamer_set_move($params, 'dubler_select', 'dubler_select_itog',100,true);        
    }
    public static function dubler_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $dubler = GameUser::where('game_id',$game->id)->where('role_id',8)->first();
        if(!isset($gameParams['dubler_select'])) return null; //ошибочный запуск функции
        $victim = GameUser::where('id',$gameParams['dubler_select'])->first(); //потерпевший
        if(!$victim) return null; //ошибочный запуск функции
        
        if(!$dubler || !self::isCanMove($dubler)) {  //обездвижен       
            GamerParam::deleteAction($game, 'dubler_select'); 
            GamerParam::saveParam($dubler,'nightactionempty',1);
            return;
        } 
        DeactivatedCommand::create(['game_id'=>$game->id,'command'=>"dubler_select"]);
    }

    public static function ifDublerChange($game) {
        $param = GamerParam::where(['game_id' => $game->id, 'night' => $game->current_night, 'param_name' => 'is_dubler_change'])->first(); 
        // Log::info('ifDublerChange', [
        //     '$param' => print_r($param, true),
        // ]);       
        if($param) {
            $dubler = GameUser::where('id', $param->gamer->id)->first();
            // Log::info('ifDublerChange 1', ['$dubler' => print_r($dubler, true)]);
            if(!$dubler || !$dubler->isActive()) return;
            // Log::info('ifDublerChange 2', ['night' => $game->current_night]);
            $gamer = GameUser::where('id', $param->param_value)->first();
            $dublMes = ['text' => "Вот так дела! Ваш выбор был верный, вы перевоплотились в " . Game::userUrlName($gamer->user) . " — " . $gamer->role . "."];
            $groupMess = ['text' => "<b>👀 Дублер</b> занял место <b>" . Game::userUrlName($gamer->user) . "</b>..."];
            Game::message(['message' => $dublMes, 'chat_id' => $dubler->user_id]);
            Game::message(['message' => $groupMess, 'chat_id' => $gamer->game->group_id]);
            GamerParam::deleteAction($game, 'is_dubler_change');
        }
    }

    //проверить не стал ли дублер комиссаром чтобы не выводить сообщения оп повышении в случае когда комиссар умер
    public static function checkIfDublerIsNotChanged($game, $role_id) {
        $dubler = GameUser::where('game_id', $game->id)->where('first_role_id', 8)->where('is_active', 1)->first();
        // Log::info('checkIfDublerIsNotChangedToKomisar #1', [
        //     '$dubler' => print_r($dubler, true)
        // ]);
        if($dubler) {
            $komisar = GameUser::where('game_id', $game->id)->where('role_id', $role_id)->where('is_active', '!=', 1)->first();
            // Log::info('checkIfDublerIsNotChangedToKomisar #2', [
            //     '$komisar' => print_r(GameUser::select(['role_id', 'first_role_id', 'is_active'])->where('game_id', $game->id)->get()->toArray(), true)
            // ]);
            if($komisar) {
                foreach(GameUser::where('game_id', $game->id)->where('role_id', $role_id)->where('first_role_id', '!=', 8)->where('is_active', 1)->get() as $kom) {
                    $update = [];
                    if($kom->first_role_id) {
                        $update['role_id'] = $kom->first_role_id;
                    } else {
                        $update['is_active'] = 0;
                    }
                    $kom->update($update);
                }
                $is_kommisar_selected = false;

                $gParams = GamerParam::where('game_id', $game->id)->get();
                foreach($gParams as $param) {
                    if($param->param_name == 'dubler_select' && $param->param_value == $komisar->id) {
                        $is_kommisar_selected = true;
                    }
                }
                // Log::info('checkIfDublerIsNotChangedToKomisar #3', [
                //     '$gParams' => print_r($gParams, true),
                //     '$is_kommisar_selected' => $is_kommisar_selected
                // ]);
                if($is_kommisar_selected)
                    return true;
            }
        }

        return false;
    }
}