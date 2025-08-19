<?php

namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\Game as GameModel;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Models\UnionParticipant;

trait Puaro
{
    public static function puaro_check($params)
    { //puaro проверяет документы
        //Ник - роль 
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if (!$gamer) return '';
        $checked = GameUser::where('id', $params['cmd_param'])->first();
        //отправим союзу, если он есть
        if($checked)  $result = '<b>🕵🏻‍♀Комиссар Пуаро</b> проверяет документы ' . $checked->user;
        else $result = '🕵🏻‍♀Комиссар Пуаро проверяет документы у несуществующего игрока. Вероятно игрока кикнули или игрок ливнул';
        $message = ['text' => $result];
        GamerParam::saveParam($gamer, 'puaro_check', $params['cmd_param']);

        $uniPartic = UnionParticipant::where('gamer_id', $gamer->id)->first();
        if ($uniPartic) {
            $participants = UnionParticipant::with('gamer')->where('union_id', $uniPartic->union_id)->get();
            foreach ($participants as $particip) {
                if ($particip->id == $uniPartic->id) continue;
                if (!$particip->gamer->isActive()) continue;
                Game::message(['message' => $message, 'chat_id' => $particip->gamer->user_id]);
            }
        }
        NightFunction::push_func($gamer->game, 'puaro_check_itog',99);
        return '';
    }
    public static function getKomisarPuaro(GameModel $game, string $param) {
        /*
        $puaro = GameUser::where('game_id', $game_id)->where('role_id', 4)->where('is_active',1)->first();
        if(!$puaro) {
            $game = GameModel::where('id',$game_id)->first();
            if($game) {
                $puaro = GameUser::where('game_id', $game_id)->where('role_id', 4)->where('kill_night_number',$game->current_night)->first();
            }
        } */
        $puaro = GamerParam::gamerFromParam($game, $param);
        return $puaro;
    }
    public static function getLiveKomisarPuaro(GameModel $game) {
        return GameUser::where('game_id', $game->id)->where('role_id', 4)->where('first_role_id', '!=', 8)->where('is_active',1)->first();
    }
    public static function puaro_check_itog($game)
    { //puaro проверяет документы
        //puaro_check
        $puaro = self::getKomisarPuaro($game, 'puaro_check');
        if (!$puaro) return null;
        $gameParams = GamerParam::gameParams($game);
        if (!self::isCanMove($puaro)) {
            // $message = ['text'=>"Сегодня ты под чарами 💃Красотки."];
            $check = GamerParam::where('game_id', $game->id)->where('param_name', 'puaro_check')->where('night', $game->current_night)->first();
            if ($check) {
                $check->delete(); //не узнаем результатов под чарами красотки
                GamerParam::saveParam($puaro,'nightactionempty',1);
            }
            // Game::message(['message'=>$message,'chat_id'=>$puaro->user_id]);
        } else {            
            $curCheckGamer = isset($gameParams['puaro_check']) ? GameUser::where('id',$gameParams['puaro_check'])->first() : null;
            if($curCheckGamer) {
                //проверим активные бафы
                $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$game->id,'user_id'=>$curCheckGamer->user_id,'is_active'=>1])->get();
                foreach($activeBafs as $activeBaf) {
                    $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
                    $actbaf = new $class($activeBaf);
                    $result = $actbaf->puaro_check($curCheckGamer);
                    if($result) {
                        if(isset($result['puaro_view'])) GamerParam::saveParam($curCheckGamer,'puaro_view',$result['puaro_view']);
                        if(isset($result['sleep'])) { //отложить выполнение
                            sleep($result['sleep']);
                        }
                        break;
                    }
                }
            }
            $checks = GamerParam::where('game_id', $game->id)->where('param_name', 'puaro_check')->get()->all();
            $gamerIds = array_column($checks, 'param_value');
            $nightOfCheck = [];
            foreach ($checks as $cCheck) {
                $nightOfCheck[$cCheck->param_value] = $cCheck->night;
            }
            $checkGamers = GameUser::with('user')->whereIn('id', $gamerIds)->where('is_active', 1)->get();
            $textArr = [];
            foreach ($checkGamers as $cGamer) {
                $nightParams = GamerParam::gameParams($game, $nightOfCheck[$cGamer->id]);
                if(isset($nightParams['puaro_view'])) { //применен баф
                    $textArr[] = Game::userUrlName($cGamer->user) . ' - '.$nightParams['puaro_view'];
                    continue;
                }
                if ($cGamer->role_id == 9) $textArr[] = Game::userUrlName($cGamer->user) . ' -	🤵🏻 Дон Корлеоне';
                else if($cGamer->role_id == 23) $textArr[] = Game::userUrlName($cGamer->user) . ' -	🌑 Житель ночи';
                else {                    
                    if (isset($nightParams['advokat_select']) && $nightParams['advokat_select'] == $cGamer->id) {
                        $textArr[] = Game::userUrlName($cGamer->user) . ' - 🌑 Житель ночи';
                    } 
                    else if(isset($nightParams['shutnik_puaro'])) {
                        $textArr[] = Game::userUrlName($cGamer->user) . ' - 🤵🏻 Дон Корлеоне';
                    }
                    else {
                        $textArr[] = Game::userUrlName($cGamer->user) . ' - ' . $cGamer->role;
                    }
                }
            }
            if($curCheckGamer && !$curCheckGamer->isActive()) {
                $textArr[] = Game::userUrlName($curCheckGamer->user) . ' - ' . $curCheckGamer->role;
            }

            $gameParams = GamerParam::gameParams($game);

            $message = ['text' => "<b>Результаты проверки:</b>\n\n" . implode("\n", $textArr)];
            $uniParticips = UnionParticipant::unionParticipantsByGamer($puaro);
            if ($uniParticips) {
                foreach ($uniParticips as $uParticip) {
                    if ($uParticip->gamer->isActive() || $uParticip->gamer->kill_night_number == $game->current_night) {
                        Game::message(['message' => $message, 'chat_id' => $uParticip->gamer->user_id]);
                        usleep(35000);
                    }
                }
            } else {
                Game::message(['message' => $message, 'chat_id' => $puaro->user_id]);
            }
            if(isset($gameParams['puaro_check'])){
                self::victim_message($gameParams['puaro_check'], 
                $gameParams['puaro_check_result'] ??  '🕵🏻‍♀Комиссар Пуаро проверил ваши документы...');            
            }
        }
    }
    public static function puaro_kill($params)
    {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if (!$gamer) return '';

        $uniPartic = UnionParticipant::where('gamer_id', $gamer->id)->first();
        $checked = GameUser::where('id', $params['cmd_param'])->first();
        if ($checked && $checked->isActive()) {
            GamerParam::saveParam($gamer, 'puaro_kill', $params['cmd_param']);
            NightFunction::push_func($gamer->game, 'puaro_kill_itog');
        }
        if ($uniPartic) {
            if ($checked && $checked->isActive()) {
                $result = '🕵🏻‍♀Комиссар Пуаро решил, что ' . $checked->user . " не проснётся этим утром...";
                $message = ['text' => $result];
                $participants = UnionParticipant::with('gamer')->where('union_id', $uniPartic->union_id)->get();
                foreach ($participants as $particip) {
                    if ($particip->id == $uniPartic->id) continue;
                    if (!$particip->gamer->isActive()) continue;
                    Game::message(['message' => $message, 'chat_id' => $particip->gamer->user_id]);
                }
            }
        }
        self::execBafMethod($gamer, 'shot');
    }
    public static function puaro_kill_itog($game)
    {
        $puaro = self::getKomisarPuaro($game, 'puaro_kill');
        if (!$puaro || !self::isCanMove($puaro)) return null; //обездвижен
        $gameParams = GamerParam::gameParams($game);
        if(!isset($gameParams['puaro_kill'])) return null;
        
        $victim = GameUser::where('id', $gameParams['puaro_kill'])->first();
        
        if($victim && $victim->role_id == 23) {
            self::user_kill($puaro->id, $gameParams['puaro_kill']);
        } elseif(!self::isTreated($gameParams['puaro_kill'], $game)) {
            //если не спас док
            self::user_deactivate(['user_id' => $puaro->user_id, 'cmd_param' => $gameParams['puaro_kill']]);
        }  
        else {
            if($gameParams['puaro_kill'] == 23) {
                $text = "Сегодня вы пытались совершить покушение на <b>🤵🏻‍♂Уголовника</b>, однако попытка не увенчалась успехом";
                $bot = AppBot::appBot();
                $bot->sendAnswer([['text'=>$text]],$puaro->user_id);
            } 
        }      
    }
    public static function ifSergantTop($game) {
        $gameParams = GamerParam::gameParams($game);
        if(isset($gameParams['sergant_top']) && !self::checkIfDublerIsNotChanged($game, 4)) {
            GamerParam::deleteAction($game,'sergant_top');
            $puaro = self::getLiveKomisarPuaro($game);  //сержант уже ком
            if(!$puaro) return null;
            $message = ['text'=>"Ты повышен до роли <b>🕵️Комиссар Пуаро</b>"];
            Game::message(['message'=>$message,'chat_id'=>$puaro->user_id]);
            $group_mes = ['text' => "<b>👮🏼Сержант Гастингс</b> был повышен до <b>🕵🏻‍♀Комиссар Пуаро</b>"];
            Game::message(['message'=>$group_mes,'chat_id'=>$puaro->game->group_id]);
        }
    }
}
