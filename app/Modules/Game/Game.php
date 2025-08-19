<?php

namespace App\Modules\Game;

use Exception;
use App\Models\Baf;
use App\Models\Vote;
use App\Models\Union;
use App\Models\BotUser;
use App\Models\Setting;
use App\Models\UserBaf;
use App\Models\Voiting;
use App\Models\BotGroup;
use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\YesnoVote;
use App\Models\GamerParam;
use App\Modules\Functions;
use App\Models\Achievement;
use App\Models\LimitSelect;
use App\Models\UserBuyRole;
use App\Models\UserProduct;
use App\Modules\Bot\AppBot;
use App\Models\ProhibitKill;
use App\Models\NightFunction;
use App\Models\SleepKillRole;
use Faker\Provider\UserAgent;
use App\Models\GameRolesOrder;
use App\Models\UserAchievement;
use App\Models\UnionParticipant;
use App\Models\Game as GameModel;
use App\Models\Task as TaskModel;
use App\Models\DeactivatedCommand;
use App\Modules\Bot\GamerSaver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Bot\MessageResultSaver;

class Game
{
    protected static $savedItemLists = [];
    const WHO_MAY_SELF = [3, 15, 16, 19];
    const COMMAND_COLORS = ['1' => '🟢', '2' => '🟣'];
    public static function getNewRole($gameId)
    {
        //добавить учет порядка выдачи ролей        
        $activeRoles = GameUser::selectRaw('role_id, count(*) as amount')->where('game_id', $gameId)->groupBy('role_id')->get();
        $selectRoles = [];
        foreach ($activeRoles as $aRole) {
            $selectRoles[$aRole->role_id] = $aRole->amount;
        }
        //---какую сейчас надо выдать согласно порядку выдачи
        $roleOrder = GameRolesOrder::all()->all();
        $allRoles = [];
        if (isset($roleOrder[$activeRoles->count()])) {
            $allRoles = GameRole::where('role_type_id', $roleOrder[$activeRoles->count()]->role_type_id)->get()->all();
        } else { // количество игроков уже набрано
            return ['error' => 'game_limit'];
        }
        do {
            $roleIndex = random_int(0, count($allRoles) - 1);
            $role = $allRoles[$roleIndex];
        } while (isset($selectRoles[$role->id]) && $role->max_amount_in_game <= $selectRoles[$role->id]);
        return $role;
    }
    public static function comandaMessage(Union $union, $addText = '')
    {
        $text_arr = ["<b>Команда:</b>\n"];
        $participants = UnionParticipant::with('gamer')->where('union_id', $union->id)->orderBy('pos_in_union')->orderBy('id')->get();
        $userIds = [];
        foreach ($participants as $participant) {
            if (!$participant->gamer || !$participant->gamer->isActive()) continue;
            $text_arr[] = self::userUrlName($participant->gamer->user) . ' - ' . $participant->gamer->role;
            $userIds[] = $participant->gamer->user_id;
        }
        $message = ['text' => implode("\n", $text_arr) . $addText];
        foreach ($userIds as $userId) {
            self::message(['message' => $message, 'chat_id' => $userId]);
            usleep(35000);
        }
    }
    protected static function createUnionRoles($game_id, array $role_ids)
    {
        $unionGamers = GameUser::where('game_id', $game_id)->whereIn('role_id', $role_ids)->where('is_active', 1)->get();

        $union = null;
        if ($unionGamers->count() > 1) {
            $union = Union::create(['game_id' => $game_id]);
            $text_arr = ["<b>Команда :</b>\n"];
            foreach ($unionGamers as $uGamer) {
                $pos_in_union = 0;
                switch ($uGamer->role_id) {
                    case 17:
                        $pos_in_union = 1;
                        break;
                    case 25:
                        $pos_in_union = 2;
                        break;
                    case 19:
                        $pos_in_union = 3;
                        break;
                    case 18:
                        $pos_in_union = 4;
                        break;
                    case 22:
                        $pos_in_union = 5;
                        break;
                }
                UnionParticipant::create(['union_id' => $union->id, 'gamer_id' => $uGamer->id, 'game_id' => $game_id, 'pos_in_union' => $pos_in_union]);
            }
            $unionParticips = UnionParticipant::with('gamer')->where('union_id', $union->id)->orderBy('pos_in_union')->orderBy('id')->get();
            foreach ($unionParticips as $up) {
                $text_arr[] = self::userUrlName($up->gamer->user) . ' - ' . $up->gamer->role;
            }

            $message = ['text' => implode("\n", $text_arr)];
            //рассылка всем, кто в союзе
            foreach ($unionGamers as $uGamer) {
                self::message(['message' => $message, 'chat_id' => $uGamer->user_id]);
                usleep(35000);
            }
        }
        return $union;
    }
    public static function stopGameRegistration($game)
    {
        $bot = AppBot::appBot();
        if(!$game->options) return null;
        $options = json_decode($game->options, true);  // {"message_id":"2909"}
        if(!$options || !isset($options['message_id'])) return null;
        $bot->deleteMessage($game->group_id, $options['message_id']);
        if (isset($options['task'])) {
            $task = TaskModel::where('id', $options['task'])->first();
            if ($task) {
                $task->is_active = 0;
                $task->save();
            }
        }
        $game->options = null;
        $game->status = 2;
        $game->save();
        DB::table('user_game_roles')->where(['is_active' => 1, 'game_id' => $game->id])->update(['is_active' => 0]);
    }
    protected static function mixGamersRoles(array $gamers) {        
        $b = array_column($gamers,'role_id');
        for($i=0;$i<count($gamers);$i++) {
            $rind = random_int(0, count($b)-1);
            $gamers[$i]->role_id = $b[$rind];
            $gamers[$i]->save();
            unset($b[$rind]);
            $b = array_values($b);
        }
        return $gamers;
    }    
    public static function assignRolesToGamers($game_id)
    {        
        $game = GameModel::where('id', $game_id)->first();
        $game->start_at = date('Y-m-d H:i:s'); //для расчета длительности игры
        $game->save();

        $buyRoleInTeam = Setting::groupSettingValue($game->group_id,'brole_in_team');
        $bot = AppBot::appBot();
        $gamers = GameUser::where('game_id', $game_id)->where('is_active', 1)->get()->all();
        $gCount = count($gamers);
        $gamerByUserId = [];
        foreach ($gamers as $gamer) $gamerByUserId[$gamer->user_id] = $gamer;

        $userIds = array_column($gamers, 'user_id');
        $buyRolesX = UserBuyRole::with('role')->whereIn('user_id', $userIds)->whereNull('game_id')->orderBy('id')->get();
        //сформируем по последней купленной роли для игрока, а остальные скинем
        $buyRoles = [];
        if(!$game->is_team || $buyRoleInTeam==='yes') {
            foreach($buyRolesX as $brx) {
                if(isset($buyRoles[$brx->user_id])) { //уже есть роль у игрока, скидываем ее
                    $buyRoles[$brx->user_id]->game_id = -1;
                    $buyRoles[$brx->user_id]->save();
                }
                $buyRoles[$brx->user_id] = $brx;                
            }
            //теперь получим роли заново. Они уже чистые, без нескольких ролей на одного игрока
            $buyRoles = UserBuyRole::with('role')->with('buyRole')->whereIn('user_id', $userIds)->whereNull('game_id')->orderBy('id')->get();
        }        
        //получим отключенные роли        
        $jsonRoles = Setting::groupSettingValue($game->group_id, 'roles');
        $isklRoles = [];
        if ($jsonRoles !== 'all') {
            $jObjRoles = json_decode($jsonRoles, true);
            foreach ($jObjRoles as $role_id => $roleSet) {
                if ($roleSet == 0) $isklRoles[] = $role_id;
            }
        }
        $rolesQuery = GameRolesOrder::with('role')->where('gamers_min', '<=', $gCount)->where('gamers_max', '>=', $gCount);
        if ($isklRoles) $rolesQuery = $rolesQuery->whereNotIn('role_id', $isklRoles);
        $roles = $rolesQuery->orderBy('position')->limit($gCount)->get()->all();

        if ($game->is_team && $buyRoleInTeam==='no') {
            $gamers =  GameUser::where('game_id', $game_id)->where('is_active', 1)->orderBy('team')->orderBy('id')->get()->all();
            //раскинем роли по килтайпу
            $rolesByKilltypes = [];
            for($i = 0;$i < count($gamers); $i++) {
                $comment = json_decode($roles[$i]->role->comment,1);
                $rolesByKilltypes[$comment['kill_type']][] = $roles[$i];
            }
            foreach($rolesByKilltypes as $kt=>$v) {
                shuffle($rolesByKilltypes[$kt]);
            }
            $gamersInTeamCnt =count($gamers)/2;             
            for ($i = 0; $i < $gamersInTeamCnt; $i++) {
                $killTypes = array_keys($rolesByKilltypes);
                do {
                    $kt = $killTypes[random_int(0, count($killTypes)-1)];
                }
                while(!isset($rolesByKilltypes[$kt][0]));
                $gamers[$i]->role_id = $rolesByKilltypes[$kt][0]->role_id;   //cmd1
                if(isset($rolesByKilltypes[$kt][1])) {
                    $gamers[$gamersInTeamCnt+$i]->role_id = $rolesByKilltypes[$kt][1]->role_id;  //cmd2
                    $rolesByKilltypes[$kt][1];
                    unset($rolesByKilltypes[$kt][1]);
                }
                else {
                    do {
                        $kt2 = $killTypes[random_int(0, count($killTypes)-1)];
                    }
                    while(!isset($rolesByKilltypes[$kt2][0]) || $kt2==$kt);
                    $gamers[$gamersInTeamCnt+$i]->role_id = $rolesByKilltypes[$kt2][0]->role_id;  //cmd2
                    unset($rolesByKilltypes[$kt2][0]);
                    $rolesByKilltypes[$kt2] = array_values($rolesByKilltypes[$kt2]);
                }
                unset($rolesByKilltypes[$kt][0]);
                $rolesByKilltypes[$kt] = array_values($rolesByKilltypes[$kt]);

                $gamers[$i]->save();
                $gamers[$gamersInTeamCnt+$i]->save();
            }
            /* старый метод перемешивание внутри команд
            for ($i = 0; $i < count($gamers); $i++) {
                $gamers[$i]->role_id = $roles[$i]->role_id;
                $gamers[$i]->save();
            }
            //перемешаем роли внутри каждой команды
            $t1gamers = GameUser::where('game_id', $game_id)->where('is_active', 1)->where('team', 1)->get()->all();
            $t2gamers = GameUser::where('game_id', $game_id)->where('is_active', 1)->where('team', 2)->get()->all();
            self::mixGamersRoles($t1gamers);
            self::mixGamersRoles($t2gamers);
            */
        } else {
            $accessRoleIds = array_column($roles, 'role_id');
            $ggroup = $game->group;
            $roleCounts = [];
            foreach ($roles as $role) {
                $roleCounts[$role->role_id] = ($roleCounts[$role->role_id] ?? 0) + 1;
            }
            $giveRolesCounts = [];
            //перебираем купленные роли и проверяем возможность дать роль
            //Log::channel('daily')->info("перебираем купленные роли и проверяем возможность дать роль ...");
            $whoGiveRole = [];
            foreach ($buyRoles as $buyRole) {
                if (in_array($buyRole->role_id, $accessRoleIds)) {
                    $giveCount = $giveRolesCounts[$buyRole->role_id] ?? 0;
                    if ($giveCount < $roleCounts[$buyRole->role_id]) {
                        $giveRolesCounts[$buyRole->role_id] = $giveCount + 1;
                        $buyRole->game_id = $game_id;
                        $buyRole->save();
                        //начислим награду группе
                        if($buyRole->buyRole->cur_code === Currency::R_WINDCOIN) {                            
                            $descr = "Покупка роли {$buyRole->role}, игра $game_id";
                            $ggroup->addReward($buyRole->buyRole->price, $descr, $game_id);
                        }
                        //----------------------------
                        $gamerByUserId[$buyRole->user_id]->role_id = $buyRole->role_id;
                        $gamerByUserId[$buyRole->user_id]->save();
                        $whoGiveRole[] = $buyRole->user_id;
                    }
                }
            }
            //Log::channel('daily')->info("кто получил роли: ".implode(', ',$whoGiveRole));
            //теперь раздать роли тем, кто еще не получил
            $gamers =  GameUser::where('game_id', $game_id)->whereNotIn('user_id', $whoGiveRole)->where('is_active', 1)->get()->all();
            shuffle($gamers);
            $gCount = count($gamers);
            //Log::channel('daily')->info("кому еще нужно раздать: ".implode(', ',array_column($gamers,'user_id')));
            foreach ($gamers as $gamer) {
                foreach ($roleCounts as $role_id => $rMaxCnt) {
                    $giveCount = $giveRolesCounts[$role_id] ?? 0;
                    if ($giveCount < $rMaxCnt) {
                        $giveRolesCounts[$role_id] = $giveCount + 1;
                        $gamer->role_id = $role_id;
                        $gamer->save();
                        break;
                    }
                }
            }
        }

        //роли распределены. Даем бафы
        $jsonBafs = Setting::groupSettingValue($game->group_id, 'bafs');
        $isklBafs = [];
        if ($jsonBafs !== 'all') {
            $jsonBafs = json_decode($jsonBafs, true);
            foreach ($jsonBafs as $baf_id => $bafSet) {
                if ($bafSet == 0) $isklBafs[] = $baf_id;
            }
        }

        $gamers2 = GameUser::with('role')->where('game_id', $game_id)->where('is_active', 1)->get()->all();
        $gUserIds = array_column($gamers2, 'user_id');
        $bafQuery = UserBaf::whereIn('user_id', $gUserIds)->where('amount', '>', 0)->where('is_activate', 1);
        if ($isklBafs) $bafQuery = $bafQuery->whereNotIn('baf_id', $isklBafs);
        $ubafs = $bafQuery->get();
        $activeBafs = [];
        foreach ($ubafs as $ubaf) {
            $activeBafs[$ubaf->user_id][] = $ubaf->baf_id;
        }
        foreach ($gamers2 as $gamer) {
            if (isset($activeBafs[$gamer->user_id])) {
                $actUserBafs = Baf::whereIn('id', $activeBafs[$gamer->user_id])->get();
                foreach ($actUserBafs as $actBaf) {
                    $assign_role_ids = empty($actBaf->assign_role_ids) ? [$gamer->role_id] : explode(',', $actBaf->assign_role_ids);
                    if (in_array($gamer->role_id, $assign_role_ids)) {
                        $need_decrement = 1;
                        if (in_array($actBaf->id, [1, 2])) {
                            $need_decrement = 0;
                            $ubaf = UserBaf::where(['baf_id' => $actBaf->id, 'user_id' => $gamer->user_id])->first();
                            if ($ubaf) {
                                $ubaf->decrement('amount');
                                //----и награда группе
                                if($ubaf->baf->cur_code === Currency::R_WINDCOIN) {
                                    $ggroup->addReward($ubaf->baf->price,"Использование бафа {$ubaf->baf} в игре $game_id",$game_id);
                                }                                
                            }
                        }
                        ActiveBaf::create(['game_id' => $game_id, 'user_id' => $gamer->user_id, 'baf_id' => $actBaf->id, 'need_decrement' => $need_decrement]);
                    }
                }
            }

            //сообщим о назначении роли
            usleep(35000);
            if(!$gamer->role) Log::error("Не назначена роль!!! пользователю {$gamer->user_id} в игре {$gamer->game_id}");
            else $bot->sendAnswer([['text' => $gamer->role->first_message]], $gamer->user_id);
        }

        //365373627
        //1022125755
        // foreach($gamers2 as $gamer) {
        //     $bot->sendAnswer([['text' => $gamer->user_id . '=' . $gamer->role->name]], 365373627);
        // }


        // Создадим союзы        
        self::createUnionRoles($game_id, [4, 5]); //1) коммисар и сержант
        self::createUnionRoles($game_id, [14, 15]); //2) ассистент и доктор        
        $mafUnion = self::createUnionRoles($game_id, [17, 18, 19, 22, 25]); //3) мафия  //убрали 16 - подпольного врача
        if ($mafUnion) {
            //Log::channel('daily')->info("Есть mafUnion. Сохраняем... ");
            GamerParam::saveParam($gamers2[0], 'maf_union', $mafUnion->id);
        } else {
            //Log::channel('daily')->error("Нет mafUnion");
        }

        //запускаем игровой процесс
        self::createGameProcess($game_id);
    }
    public static function assignRolesToGamersOld($game_id)
    {
        $opredeleno = []; //'376753094' => 17,'1074132476'=>6
        $bot = AppBot::appBot();
        $gamers = GameUser::where('game_id', $game_id)->get()->all();
        shuffle($gamers);
        $gCount = count($gamers);
        $roles = GameRolesOrder::with('role')->where('gamers_min', '<=', $gCount)->where('gamers_max', '>=', $gCount)
            ->orderBy('position')->get()->all();

        $oprGamers = [];
        for ($i = 0; $i < $gCount; $i++) {
            $gamers[$i]->role_id = $roles[$i]->role_id;
            $gamers[$i]->save();
            if (isset($opredeleno[$gamers[$i]->user_id])) {
                $oprGamers[] = $gamers[$i];
            }
        }

        foreach ($oprGamers as $oprGamer) {
            if ($oprGamer->role_id != $opredeleno[$oprGamer->user_id]) {
                $gm2 = GameUser::where('game_id', $oprGamer->game_id)->where('role_id', $opredeleno[$oprGamer->user_id])->first();
                if ($gm2) {
                    $gm2->role_id = $oprGamer->role_id;
                    $gm2->save();
                }
                $oprGamer->role_id = $opredeleno[$oprGamer->user_id];
                $oprGamer->save();
            }
        }
        $gamers2 = GameUser::with('role')->where('game_id', $game_id)->get()->all();
        $gUserIds = array_column($gamers2, 'user_id');
        $ubafs = UserBaf::whereIn('user_id', $gUserIds)->where('amount', '>', 0)->where('is_activate', 1)->get();
        $activeBafs = [];
        foreach ($ubafs as $ubaf) {
            $activeBafs[$ubaf->user_id][] = $ubaf->baf_id;
        }
        foreach ($gamers2 as $gamer) {
            if (isset($activeBafs[$gamer->user_id])) {
                $actUserBafs = Baf::whereIn('id', $activeBafs[$gamer->user_id])->get();
                foreach ($actUserBafs as $actBaf) {
                    $assign_role_ids = empty($actBaf->assign_role_ids) ? [$gamer->role_id] : explode(',', $actBaf->assign_role_ids);
                    if (in_array($gamer->role_id, $assign_role_ids)) {
                        $need_decrement = 1;
                        if (in_array($actBaf->id, [1, 2])) {
                            $need_decrement = 0;
                            $ubaf = UserBaf::where(['baf_id' => $actBaf->id, 'user_id' => $gamer->user_id])->first();
                            if ($ubaf) $ubaf->decrement('amount');
                        }
                        ActiveBaf::create(['game_id' => $game_id, 'user_id' => $gamer->user_id, 'baf_id' => $actBaf->id, 'need_decrement' => $need_decrement]);
                    }
                }
            }
            //сообщим о назначении роли
            usleep(35000);
            $bot->sendAnswer([['text' => $gamer->role->first_message]], $gamer->user_id);
        }


        //роли назначены. Создадим союзы        
        self::createUnionRoles($game_id, [4, 5]); //1) коммисар и сержант
        self::createUnionRoles($game_id, [14, 15]); //2) ассистент и доктор        
        $mafUnion = self::createUnionRoles($game_id, [16, 17, 18, 19, 22, 25]); //3) мафия
        if ($mafUnion) GamerParam::saveParam($gamers[0], 'maf_union', $mafUnion->id);

        //запускаем игровой процесс
        self::createGameProcess($game_id);
    }

    public static function message(array $params)
    {
        $bot = AppBot::appBot();
        try {
            $bot->sendAnswer([$params['message']], $params['chat_id']);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
    public static function itemlistFromModel($model, $where)
    {
        if (!isset(self::$savedItemLists[$model][$where])) {
            self::$savedItemLists[$model][$where] = $model::whereRaw($where)->get();
        }
        return self::$savedItemLists[$model][$where];
    }
    public static function night_rassilka($game_id)
    {
        $game = GameModel::where('id', $game_id)->first();
        $bot = AppBot::appBot();
        $gamers = GameUser::with('role')->where('game_id', $game_id)->where('is_active', 1)->get()->all();
        $gamersIds = array_column($gamers, 'id');
        $deactivateList = DeactivatedCommand::all_deactivated($game_id);
        $participGroups = UnionParticipant::gamerIdsOfUnions($game_id);
        $limitSelect = LimitSelect::gamersLimits($gamersIds);
        $securedOfKill = ProhibitKill::where('expire_time', '>', date('Y-m-d H:i'))->where('group_id',$game->group_id)->where('night_count', '>=', $game->current_night)->get()->all();
        $securedOfKillUserIds = array_column($securedOfKill, 'user_id');
        foreach ($gamers as $gamer) {
            if ($gamer->role->night_message_priv && !empty(trim($gamer->role->night_message_priv))) {
                $mess = json_decode($gamer->role->night_message_priv, true);

                if (isset($mess['name']) && in_array($mess['name'], $deactivateList)) continue;
                if (isset($mess['variants'])) {
                    $selmes = null;
                    foreach ($mess['variants'] as $var1) {
                        if (isset($var1['name']) && in_array($var1['name'], $deactivateList)) continue;
                        $selmes = $var1;
                        break;
                    }
                    if (!$selmes) continue;
                    $mess = $selmes;
                }
                try {
                    $res = ['text' => $mess['text']];
                    $res['saver'] = new GamerSaver($gamer);
                } catch (Exception $e) {
                    Log::error("gamer id: " . $gamer->id . " : " . $e->getMessage());
                }

                // не выводим кнопок для мужа если красотки уже нет вживых
                if($gamer->role_id == 34 && !GameUser::where(['game_id' => $game->id, 'role_id' => 6, 'is_active' => 1])->first()) {
                    DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'muzh_select']);
                    GamerParam::saveParam($gamer, 'afk', $gamer->id);
                    continue;
                }

                // если студент умер до того как профессор выбрал или обучил студента, то делаем профессора афк
                if($gamer->role_id == 22) {
                    if(!GameUser::where(['game_id' => $game->id, 'role_id' => 21, 'is_active' => 1])->first()) {
                        DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'professor_select']);
                        DeactivatedCommand::create(['game_id'=>$game->id,'command'=>'professor_teach']);
                        GamerParam::saveParam($gamer, 'afk', $gamer->id);
                        continue;
                    }
                }

                if (isset($mess['buttons'])) {
                    foreach ($mess['buttons'] as $button) {
                        if (isset($button['pattern'])) {
                            $pattern = $button['pattern'];
                            $model = "\\App\\Models\\" . $pattern['model'];
                            if (isset($pattern['where'])) {
                                $keys = [];
                                $values = [];
                                foreach ($pattern['params'] as $ck => $cv) {
                                    $keys[] = '#' . $ck;
                                    $values[] = (string) $gamer->$cv;
                                }
                                $pattern['where'] = str_replace($keys, $values, $pattern['where']);
                                $itemList = self::itemlistFromModel($model, $pattern['where']); //экономим запросы к БД
                            } else $itemList = $model::all();
                            $tGrp = [$gamer->id];
                            foreach ($participGroups as $pGrp) {
                                if (in_array($gamer->id, $pGrp)) {
                                    $tGrp = $pGrp;
                                    break;
                                }
                            }
                            foreach ($itemList as $item) {
                                if (!in_array($gamer->role_id, self::WHO_MAY_SELF) && $pattern['model'] == 'GameUser' && in_array($item->id, $tGrp)) continue;
                                if (
                                    $pattern['model'] == 'GameUser' && isset($limitSelect[$gamer->id]) &&
                                    in_array($item->id, $limitSelect[$gamer->id])
                                ) continue;
                                //кого нельзя убить 2 или 5 ночей------------------------------------------
                                if (( isset($button['is_kill']) ) && $pattern['model'] == 'GameUser'   //|| isset($button['is_stop'])
                                    && in_array($item->user_id, $securedOfKillUserIds)
                                ) continue;
                                //-------------------------------------------------------------------------    
                                $keys = [];
                                $values = [];
                                foreach ($pattern['params'] as $ck => $cv) {
                                    $keys[] = '#' . $ck;
                                    $value = (string) $item->$cv;
                                    if ($pattern['model'] == 'GameUser' && $ck == 'username' && $item->team) {
                                        $value = self::COMMAND_COLORS[$item->team] . $value;
                                    }
                                    $values[] = $value;
                                }
                                $res['inline_keyboard']['inline_keyboard'][] = [
                                    [
                                        "text" => str_replace($keys, $values, $button['text']),
                                        "callback_data" => str_replace($keys, $values, $button['callback'])
                                    ]
                                ];
                            }
                        } else {
                            if (!in_array($button['callback'], $deactivateList)) {
                                $res['inline_keyboard']['inline_keyboard'][] = [['text' => $button['text'], 'callback_data' => $button['callback']]];
                            }
                        }
                    }
                    if (isset($res['inline_keyboard']['inline_keyboard']) && $res['inline_keyboard']['inline_keyboard']) {
                        $skip_move_night = Setting::groupSettingValue($game->group_id, 'skip_move_night');
                        if ($skip_move_night === 'yes') {
                            $res['inline_keyboard']['inline_keyboard'][] = [['text' => "🚷 Пропустить", 'callback_data' => "nightactionempty"]];
                        }
                    }
                }
                usleep(50000);
               // Log::channel('daily')->info('ночное сообщение: ' . print_r($res, true));
                $bot->sendAnswer([$res], $gamer->user_id);
            }
        }
    }
    public static function userUrlName(BotUser $user)
    {
        return "<a href='tg://user?id={$user->id}'>" . $user . '</a>';
    }
    protected static function messRolesArrayToStr($messRoles)
    {
        $strMessRoles = [];
        foreach ($messRoles as $k => $v) {
            if ($v > 1) $strMessRoles[] = "$k - $v";
            else $strMessRoles[] = $k;
        }
        return implode(', ', $strMessRoles);
    }
    public static function live_gamers_mess(GameModel $game)
    {
        $mirs = GamerParam::where('game_id',$game->id)->where('param_name','mir')->get()->all();
        $mirIds = array_column($mirs,'gamer_id');
        $messNicks = [];
        $messRoles = [];
        $mirRoles = [];
        $mafRoles = [];
        $neitrals = [];
        $iter = 0;
        $gamers = GameUser::with('role')->with('user')->where('game_id', $game->id)->orderBy('team')->orderBy('sort_id')->orderBy('id')->get(); //спеуильно не фильтр по активности чтоб сохранить номера
        foreach ($gamers as $gamer) {
            $iter++;
            if ($gamer->is_active != 1) continue;
            $messNicks[] = "$iter. " . ($gamer->team ? self::COMMAND_COLORS[$gamer->team] : '') . "<a href='tg://user?id={$gamer->user_id}'>" . $gamer->user . '</a>';
            $messRoles['' . $gamer->role] = ($messRoles['' . $gamer->role] ?? 0) + 1;
            if(in_array($gamer->id,$mirIds)) {
                $mirRoles['' . $gamer->role] = ($mirRoles['' . $gamer->role] ?? 0) + 1;
                continue;
            }
            if(!$gamer || !$gamer->role) continue;

            switch ($gamer->role->view_role_type_id ?? $gamer->role->role_type_id) {                
                case 1:
                    if ($gamer->role_id == 10 && GamerFunctions::oderjimIsNeytral($game)) {
                        $neitrals['' . $gamer->role] = 1;
                    } else $mirRoles['' . $gamer->role] = ($mirRoles['' . $gamer->role] ?? 0) + 1;
                    break;
                case 2:
                    $mafRoles['' . $gamer->role] = ($mafRoles['' . $gamer->role] ?? 0) + 1;
                    break;
                case 3:
                    $neitrals['' . $gamer->role] = ($neitrals['' . $gamer->role] ?? 0) + 1;
                    break;
            }
        }
        ksort($messRoles);
        $message = ['text' => "<b>Живые игроки:</b>\n" . implode("\n", $messNicks) .
            "\n\n<b>Из них:</b>\n"];
        $view_role_commands = Setting::groupSettingValue($game->group_id, 'view_role_commands');
        if ($view_role_commands === 'yes') {
            if ($mirRoles) {
                ksort($mirRoles);
                $message['text'] .= "\n<b>Команда мирных: " . array_sum($mirRoles) . "</b>\n" . self::messRolesArrayToStr($mirRoles) . "\n";
            }
            if ($mafRoles) {
                ksort($mafRoles);
                $message['text'] .= "\n<b>Команда мафии: " . array_sum($mafRoles) . "</b>\n" . self::messRolesArrayToStr($mafRoles) . "\n";
            }
            if ($neitrals) {
                ksort($neitrals);
                $message['text'] .= "\n<b>Команда нейтралов: " . array_sum($neitrals) . "</b>\n" . self::messRolesArrayToStr($neitrals) . "\n";
            }
        } else {
            $message['text'] .= self::messRolesArrayToStr($messRoles) . "\n";
        }
        $message['text'] .= "\n<b>Всего: " . array_sum($messRoles) . "</b>";
        return $message;
    }
    public static function night($game_id)
    { //ночь! создает отложенное событие день 
        $game = GameModel::where('id', $game_id)->first();
        $game->times_of_day = GameModel::NIGHT;
        $game->current_night++;
        $game->save();

        //self::sleep_kill($game);  //кого убил сон

        if ($gameOver = self::isGameOver($game_id)) {
            $game = GameModel::where('id', $game_id)->first();
            self::stopGame($game, $gameOver);
            return null;
        }
        $chat_id = $game->group_id;
        //рассылаем ночные сообщения
        //2-е сообщение
        $message = ['text' => "<b>🌉 Ночь опустилась на город</b>\n<i>Под покровом ночи прячутся страхи и кошмары, затаившиеся в уголках темного сознания.\nНа улицы выходят жители города, но все ли вернутся живыми...</i>"];
        $message['video'] = 'public/theme/img/night.mp4';
        $message['inline_keyboard']['inline_keyboard'] = [[[
            'text' => 'Перейти к боту',
            'url' => "http://t.me/".config('app.bot_nick')
        ]]];
        $params = ['chat_id' => $chat_id, 'message' => $message];
        $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
        //отправим инфу о команде, если это не 1-я ночь
        if ($game->current_night > 1) {
            $mafunParam = GamerParam::where(['game_id' => $game->id, 'param_name' => 'maf_union'])->first();
            $unions = Union::where('game_id', $game_id)->get();
            foreach ($unions as $union) {
                if (isset($mafunParam) && $mafunParam->param_value == $union->id && GamerFunctions::isBlacklady($game)) {
                    $addText = GamerFunctions::allBlackLadyChecks($game);
                    self::comandaMessage($union, $addText);
                } else self::comandaMessage($union);
            }
        }

        TaskModel::create(['game_id' => $game_id, 'name' => '1-e сообщение ночь. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 2]);
        //рассылка ночных сообщений
        $options = ['class' => Game::class, 'method' => 'night_rassilka', 'param' => $game_id];
        TaskModel::create(['game_id' => $game_id, 'name' => 'Ночная рассылка в ЛС. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 3]);
        //3-е сообщение
        $message = self::live_gamers_mess($game);
        $params = ['chat_id' => $chat_id, 'message' => $message];
        $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
        TaskModel::create(['game_id' => $game_id, 'name' => '2-e сообщение ночь. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 6]);

        //и день
        $night_duration = Setting::groupSettingValue($game->group_id, 'night_duration');
        $options = ['class' => Game::class, 'method' => 'day', 'param' => $game_id];
        TaskModel::create(['game_id' => $game_id, 'name' => 'День. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => $night_duration + 3]);
    }

    public static function finish_night_actions($game)
    {
        NightFunction::load_funcs($game);
        while ($func = NightFunction::shift_func()) {
            $sfunc = $func->func_name;
            GamerFunctions::$sfunc($game);
        }
    }
    public static function sleep_kill($game)
    {
        // if($game->current_night < 3) return null;  //начинает работать только с 3-й ночи. вызывается в начале ночи перед ночной рассылкой
        $sleepKillroles = SleepKillRole::where('test_nights_count', '<', $game->current_night + 1)->get();
        $afkList = GamerParam::afkList($game->id);
        foreach ($sleepKillroles as $skRole) {
            $roleGamer = GameUser::where('game_id', $game->id)->where('is_active', 1)->where('role_id', $skRole->role_id)->first();
            if (!$roleGamer) continue;
            if(in_array($roleGamer->id,$afkList)) continue; //успел стать АФК. Не убиваем
            $need_kill = true;
            $commands = explode(',', $skRole->need_commands);
            $commands[] = 'nightactionempty';
            $query = GamerParam::with('gamer')->where('game_id', $game->id)->whereIn('param_name', $commands);
            if (!$skRole->is_one) {
                $nights = [];
                for ($i = 1; $i <= $skRole->test_nights_count; $i++) {
                    $nights[] = $game->current_night + 1 - $i;
                }
                $query = $query->whereIn('night', $nights);
            }
            $params = $query->get();
            foreach ($params as $param) {
                if ($param->gamer && $param->gamer->role_id == $skRole->role_id) {  //ура, нашли, не убиваем
                    $need_kill = false;
                    break;
                }
            }
            if ($need_kill) {
                $roleGamer->is_active  = 0;
                $roleGamer->save();
                $message = ['text' => "Тебя убил сон"];
                self::message(['message' => $message, "chat_id" => $roleGamer->user_id]);
                $groupMessage = ['text' => "😴Сегодня трагически погиб <b>" . self::userUrlName($roleGamer->user) . " - {$roleGamer->role},</b> сон забрал его в свои объятия навеки..."];
                self::message(['message' => $groupMessage, "chat_id" => $game->group_id]);
            }
        }
    }
    public static function autostart($game_id)
    {
        $bot = AppBot::appBot();
        $game = GameModel::where('id', $game_id)->first();
        //проверим количество игроков
        $gamers = GameUser::where('game_id', $game_id)->get();
        if ($gamers->count() < 5) {
            self::stopGameRegistration($game);
            $res['text'] = '<b>Недостаточно игроков для начала игры...</b>';
            $bot->sendAnswer([$res], $game->group_id);
            return;
        }
        if ($game->is_team) {
            $team1 = GameUser::where('game_id', $game->id)->where('team', 1)->get();
            $team2 = GameUser::where('game_id', $game->id)->where('team', 2)->get();
            if ($team1->count() !== $team2->count()) {
                self::stopGameRegistration($game);
                $res['text'] = '<b>Количество игроков в командах должно быть одинаковым...</b>';
                $bot->sendAnswer([$res], $game->group_id);
                return;
            }
        }
        //-----------------------------
        $res['text'] = '<b>Игра начинается. Игроки получают свои роли...</b>';
        if($game->group_id == '-1002082482712') $res['text'] = "<b>Игра #{$game->id} начинается. Игроки получают свои роли...</b>"; //Мафия тест
        if ($game->status > 0) return;

        $options = json_decode($game->options, true);  // {"message_id":"2909"}
        try {
            $bot->getApi()->deleteMessage(['chat_id' => $game->group_id, 'message_id' => $options['message_id']]);
        } catch (Exception $e) {
        }

        $game->options = null;
        $game->status = 1;
        $game->save();

        $bot->sendAnswer([$res], $game->group_id);
        $options = ['class' => self::class, 'method' => 'assignRolesToGamers', 'param' => $game_id];
        TaskModel::create(['game_id' => $game_id, 'name' => 'Назначение ролей. Игра ' . $game_id, 'options' => json_encode($options)]);
    }
    public static function correctRoleKill($gamer)
    {
        if($gamer->role_id == 4) {
            if($gamer->first_role_id == 5) {
                if(GameUser::where('game_id', $gamer->game_id)->where('role_id', 8)->orWhere('first_role_id', 8)->count() > 0) {
                    $checkRole = 0;

                    foreach(GameUser::where('game_id', $gamer->game_id)->where('role_id', 4)->get() as $us) {
                        $checkRole++;
                        $us->update(['is_active' => 0]);
                    }

                    if($checkRole > 1) {
                        $gamer->update(['role_id' => 5]);
                        return '👮‍♂Сержант Гастингс';
                    }
                }
            }
        }

        return $gamer->role;
    }
    public static function day($game_id)
    { //день!          
        $game = GameModel::where('id', $game_id)->first();
        $chat_id = $game->group_id;
        //конец ночи
        self::finish_night_actions($game);

        //начало дня ...
        $message = ['text' => "🌅<b>Утро: {$game->current_night}</b>\n<i>Город просыпается. Утренний ветер несет запах крови и тайн...</i>"];
        if ($game->current_night > 1) $message['video'] = 'public/theme/img/morning2.mp4';
        else {
            $message['video'] = 'public/theme/img/morning1.mp4';
            DeactivatedCommand::create(['game_id' => $game_id, 'command' => 'first_night']); //деактивируем команды первой ночи
        }
        self::message(['message' => $message, 'chat_id' => $chat_id]);

        self::sleep_kill($game);  //кого убил сон
        //кто был убит
        $who_killed = GameUser::with('killer')->where('game_id', $game_id)->where('kill_night_number', $game->current_night)->get();
        $killedInfoArr = [];
        $set_view_killers = Setting::groupSettingValue($game->group_id, 'view_killers');
        foreach ($who_killed as $killedGamer) {
            if($killedGamer->first_role_id != 37)
                $mess = "Сегодня ночью был убит <b>" . $killedGamer->role . "</b> " . self::userUrlName($killedGamer->user);
            else
                $mess = "Сегодня ночью был убит <b>🐾Оборотень</b> " . self::userUrlName($killedGamer->user);

            if ($killedGamer->killer_id == -1) $mess .= "\nГоворят, умер он от отчаяния";
            if ($killedGamer->killer_id > 0 && $set_view_killers === 'yes') {
                $mess .= "\nГоворят, что его убил <b>{$killedGamer->killer->role}</b>";
                if ($killedGamer->killers) {
                    $killersArrIds = explode(',', $killedGamer->killers);
                    $killerGamers = GameUser::with('role')->whereIn('id', $killersArrIds)->get();
                    foreach ($killerGamers as $kgm) {
                        $mess .= ", <b>{$kgm->role}</b>";
                    }
                }
            }
            $killedInfoArr[] = $mess;
        }
        sleep(1);
        if ($killedInfoArr) {
            $message = ['text' => implode("\n\n", $killedInfoArr)];
            self::message(['message' => $message, 'chat_id' => $chat_id]);
        } else {
            $message = ['text' => "<i>Жители города вздохнули с облегчением – обошлось без жертв...</i>"];
            self::message(['message' => $message, 'chat_id' => $chat_id]);
        }

        if ($gameOver = self::isGameOver($game_id)) {
            $game = GameModel::where('id', $game_id)->first();
            self::stopGame($game, $gameOver);
            return null;
        }
        GamerFunctions::messagesAfterKills($game);
        GamerFunctions::topGamersIfNeed($game);
        $game->times_of_day = GameModel::DAY;
        $game->save();

        //живые игроки
        $message = self::live_gamers_mess($game);
        $message['text'] .= "\n\n<i>Настало время начать расследование и раскрыть убийц...</i>";
        $params = ['chat_id' => $chat_id, 'message' => $message];
        $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
        TaskModel::create(['game_id' => $game_id, 'name' => '2-e сообщение день. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 2]);
        //голосование
        $delay = Setting::groupSettingValue($game->group_id, 'golos_delay');
        $options = ['class' => Game::class, 'method' => 'voting', 'param' => $game_id];
        TaskModel::create(['game_id' => $game_id, 'name' => 'Голосование. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => $delay]);
    }
    public static function voting($game_id)
    { //голосование
        $game = GameModel::where('id', $game_id)->first();
        $chat_id = $game->group_id;
        $golos_long = Setting::groupSettingValue($game->group_id, 'golos_long_time');
        $message = ['text' => "<b>Жители города решают наказать виновников.</b>\nГолосование продлится $golos_long секунд."];
        $message['inline_keyboard']['inline_keyboard'] = [[[
            'text' => 'Перейти к боту',
            'url' => "http://t.me/".config('app.bot_nick')
        ]]];
        $params = ['chat_id' => $chat_id, 'message' => $message];
        self::message($params);
        //создадим голосование
        $voiting = Voiting::create(['game_id' => $game_id, 'long_in_seconds' => $golos_long]);
        //а теперь рассылка в ЛС
        $message = ['text' => "<b>Пришло время наказать виновных.</b>\nКого ты хочешь линчевать?"];
        $lives = GameUser::with('user')->where('game_id', $game_id)->where('is_active', 1)->orderBy('sort_id')->get();
        $bot = AppBot::appBot();

        $upGroups = UnionParticipant::gamerIdsOfUnions($game_id);

        $gameParams = GamerParam::gameParams($game);
        $skip_move_day = Setting::groupSettingValue($game->group_id, 'skip_move_day');
        foreach ($lives as $gamer) {
            if (!GamerFunctions::isCanMove($gamer)) continue;  //не голосует под красоткой 
            $selGroup = null;
            foreach ($upGroups as $grp) {
                if (in_array($gamer->id, $grp)) {
                    $selGroup = $grp;
                    break;
                }
            }
            //днем исключаем только себя. других не надо
            if ($game->is_team) {
                $message['inline_keyboard'] = $bot->inlineKeyboard($lives, 1, "voitprot_{$voiting->id}_", false, 'id', 'comandaname', [$gamer->id]);
                if ($skip_move_day === 'yes') {
                    $message['inline_keyboard']['inline_keyboard'][] = [['text' => "🚷 Пропустить", 'callback_data' => "voitprot_{$voiting->id}_empty"]];
                }
            } else {
                $prefTemplate = '';
                if (in_array($gamer->role_id, [17, 18, 19, 25])) $prefTemplate = '🤵🏻';
                if (in_array($gamer->role_id, [4, 5]))  $prefTemplate = '👮‍♂️';
                if (in_array($gamer->role_id, [14, 15]))  $prefTemplate = '🧑🏼‍⚕';
                $prefix = $selGroup ? $prefTemplate : '';
                $message['inline_keyboard'] = $bot->inlineKeyboard($lives, 1, "voitprot_{$voiting->id}_", false, 'id', 'user', [$gamer->id], $prefix, $selGroup);
                if ($skip_move_day === 'yes') {
                    $message['inline_keyboard']['inline_keyboard'][] = [['text' => "🚷 Пропустить", 'callback_data' => "voitprot_{$voiting->id}_empty"]];
                }
            }

            usleep(35000);
            self::message(['message' => $message, 'chat_id' => $gamer->user_id]);
        }
        //подведение итогов голосования/ задача
        $options = ['class' => self::class, 'method' => 'voting_itogs', 'param' => $voiting->id];
        TaskModel::create(['game_id' => $game_id, 'name' => 'Голосование Итоги. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => $golos_long]);
    }
    public static function  yes_no_buttons($gamer_id, $voiting_id, $yes_count = 0, $no_count = 0)
    {
        $yes_cnt = $yes_count ? $yes_count : '';
        $no_cnt = $no_count ? $no_count : '';
        $result['inline_keyboard'] = [[
            ["text" => "{$yes_cnt}👍", "callback_data" => "gallow_" . $gamer_id . "_" . $voiting_id . "_yes"],
            ["text" => "{$no_cnt}👎", "callback_data" => "gallow_" . $gamer_id . "_" . $voiting_id . "_no"]
        ]];
        return $result;
    }
    public static function voting_itogs($voiting_id)
    {
        $voiting = Voiting::where('id', $voiting_id)->first();
        if ($voiting) {
            $voiting->is_active = 0;
            $voiting->save();
            //посчитаем рейтинг, за кого больше голосов
            $voteWinners = Vote::selectRaw("gamer_id,sum(if(vote_role_id = 1, 2, 1)) as votes_amount")->where('voiting_id', $voiting_id)->groupBy('gamer_id')
                ->orderByDesc('votes_amount')->limit(2)->get();
            $voteWinner = null;
            foreach ($voteWinners as $kandWinner) { //сравниваем 1-х двух в рейтинге по кол-ву голосов
                if (!$voteWinner) $voteWinner = $kandWinner;
                else if ($voteWinner->votes_amount == $kandWinner->votes_amount) {
                    $voteWinner = null;
                }
            }
            if (!$voteWinner) {
                $message = ['text' => "<b>Голосование окончено</b>\n<i>Жители города не смогли прийти к единому мнению... Они разошлись по своим делам, так никого и не повесив...</i>"];
                self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
                //запускаем следующую ночь
                $options = ['class' => Game::class, 'method' => 'night', 'param' => $voiting->game_id];
                TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'ночь. Игра ' . $voiting->game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 4]);
            } else {
                $gameParams = GamerParam::gameParams($voteWinner->gamer->game);
                // Log::info('Game itogs', [
                //     'GamerFunctions::isGodfatherSave($voteWinner->gamer)' => GamerFunctions::isGodfatherSave($voteWinner->gamer),
                //     'user' => print_r($voteWinner->gamer, true)
                // ]);
                if (GamerFunctions::isGodfatherSave($voteWinner->gamer)) {
                    $message = ['text' => "Крестный отец взял " . self::userUrlName($voteWinner->gamer->user) . " под свою защиту."];
                    self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
                    $options = ['class' => Game::class, 'method' => 'night', 'param' => $voiting->game_id];
                    TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'ночь. Игра ' . $voiting->game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 6]);
                    return null;
                }
                if (isset($gameParams['advokat_select']) && $gameParams['advokat_select'] == $voteWinner->gamer->id) {
                    $text = "Адвокат защитил <a href='tg://user?id={$voteWinner->gamer->user_id}'>{$voteWinner->gamer->user}</a> от повешенья";
                    $message = ['text' => $text];
                    self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
                    $options = ['class' => Game::class, 'method' => 'night', 'param' => $voiting->game_id];
                    TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'ночь. Игра ' . $voiting->game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 6]);
                    return null;
                }
                //баффы                
                $activeBafs = ActiveBaf::with('baf')->where(['game_id' => $voiting->game_id, 'user_id' => $voteWinner->gamer->user_id, 'is_active' => 1])->get();
                foreach ($activeBafs as $activeBaf) {
                    $class = "\\App\\Modules\\Game\\Bafs\\" . $activeBaf->baf->baf_class;
                    $actbaf = new $class($activeBaf);
                    $result = $actbaf->gallow($voteWinner->gamer);
                    if ($result) {
                        $text = "Вам не удалось повесить <a href='tg://user?id={$voteWinner->gamer->user_id}'>{$voteWinner->gamer->user}</a>. Он был хитрее и использовал защиту от повешения";
                        $message = ['text' => $text];
                        self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
                        $options = ['class' => Game::class, 'method' => 'night', 'param' => $voiting->game_id];
                        TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'ночь. Игра ' . $voiting->game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 6]);
                        return null;
                    }
                }


                $message = ['text' => "Вы точно хотите повесить {$voteWinner->gamer->user} ?"];
                $message['inline_keyboard'] = self::yes_no_buttons($voteWinner->gamer->id, $voiting_id);
                $bot = AppBot::appBot();
                $message['saver'] = new MessageResultSaver($voiting->game);
                $bot->sendAnswer([$message], $voiting->game->group_id);
                //self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
                $options = ['class' => self::class, 'method' => 'voting_yesno_itogs', 'param' => $voiting->id];
                $delay = Setting::groupSettingValue($voiting->game->group_id, 'yesno_votes_long');
                TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'Голосование Итоги2', 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => $delay]);
            }
        }
    }
    public static function voting_yesno_results($voiting_id)
    {
        $results = YesnoVote::selectRaw("answer, gamer_id, sum(if(vote_role_id = 1, 2, 1)) as votes_amount")->where('voiting_id', $voiting_id)->groupBy('answer')->groupBy('gamer_id')->get();
        $answers = ['yes' => 0, 'no' => 0, 'gamer_id' => 0];
        foreach ($results as $result) {
            $answers[$result->answer] = $result->votes_amount;
            $answers['gamer_id'] = $result->gamer_id;
        }
        return $answers;
    }

    public static function anyDangerous($gamer)
    {
        $dangs = GamerParam::where([
            'game_id'       => $gamer->game_id,
            'param_value'   => $gamer->id,
            'night'         => $gamer->game->current_night
        ])
            ->whereIn('param_name', ['manjak_select', 'karleone_select', 'mafiya_select'])
            ->orWhere('param_name', 'like', '%kill%')
            ->first();
            
        if($dangs)
            return true;

        return false;
    }

    public static function actionsAfterDie(GameUser $gamer)
    {
        //если его выбрал Дублер
        $paramDubler = GamerParam::where(['game_id' => $gamer->game_id, 'param_name' => 'dubler_select'])->orderByDesc('id')->first();
        if ($paramDubler && $paramDubler->param_value == $gamer->id) {
            $nightDiff = $gamer->game->current_night - $paramDubler->night;
            if ($nightDiff > 0 && $nightDiff < 3) {
                $dubler = GameUser::where('game_id', $gamer->game_id)->where('role_id', 8)->first();
                if ($dubler->isActive()) { //надо как-то передать роль  
                    GamerParam::saveParam($dubler, 'is_dubler_change',$gamer->id);
                    
                    //копируем роль донора                    
                    if (!$dubler->first_role_id) $dubler->first_role_id = $dubler->role_id;
                    $dubler->role_id = $gamer->role_id;
                    $dubler->save();
                    //был ли Union. Если да -- обновим
                    $participant = UnionParticipant::where(['gamer_id' => $gamer->id])->first();
                    if ($participant) {
                        $participant->gamer_id = $dubler->id;
                        $participant->save();
                    }
                    sleep(1);
                    // $gameP = GamerParam::gameParams($gamer->game); 
                    // Log::info('is_dubler_change', [
                    //     'is_dubler_change' => print_r($gameP, true)
                    // ]);
                }
            }
        }

        if ($gamer->role_id == 6) { //красотка
            DeactivatedCommand::firstOrCreate(['game_id' => $gamer->game_id, 'command' => 'lubovnik_select']); //деактивировать действие любовника  
            DeactivatedCommand::firstOrCreate(['game_id' => $gamer->game_id, 'command' => 'sutener_select']); //деактивировать действие сутенера  
            DeactivatedCommand::firstOrCreate(['game_id' => $gamer->game_id, 'command' => 'muzh_select']); //деактивировать Мужа
        }
        GamerFunctions::ifIdolOderjimActivate($gamer); //начнет играть одержимый
        //если пуаро
        if ($gamer->role_id == 4) {
            //ищем сержанта
            $sergant = GameUser::where('game_id', $gamer->game_id)->where('role_id', 5)->where('is_active', 1)->first();
            if ($sergant && !self::anyDangerous($sergant)) {
                if (!$sergant->first_role_id) $sergant->first_role_id = $sergant->role_id;
                $sergant->role_id = 4;
                $sergant->save(); //назначили приемником
                GamerParam::saveParam($sergant, 'sergant_top', 1);
                GamerParam::saveParam($sergant, 'nightactionempty', 1); //чтоб не умер от афк
            }
        }

        //если Доктор
        if ($gamer->role_id == 15) {
            //ищем асистента
            $asist = GameUser::where('game_id', $gamer->game_id)->where('role_id', 14)->where('is_active', 1)->first();
            if ($asist) {
                if (!$asist->first_role_id) $asist->first_role_id = $asist->role_id;
                $asist->role_id = 15;
                $asist->save(); //назначили приемником
                GamerParam::saveParam($asist, 'assistent_top', 1);
                GamerParam::saveParam($asist, 'nightactionempty', 1); //чтоб не умер от афк             
            }
        }

        //если дон
        if ($gamer->role_id == 17) { //ищем мафию чтоб передать роль
           // Log::channel('daily')->info("ищем мафию чтоб передать роль...");
            $maf = GameUser::where('game_id', $gamer->game_id)->where('role_id', 25)->where('is_active', 1)->first();
            if ($maf) {
               // Log::channel('daily')->info("нашли {$maf->id} " . $maf->user);
                /* не будем назначать приемником здесь а обработаем в общем методе после всех смертей
                if (!$maf->first_role_id) $maf->first_role_id = $maf->role_id;
                $maf->role_id = 17;
                $maf->save(); //назначили приемником
                GamerParam::saveParam($maf, 'nightactionempty', 1); //чтоб не умер от афк
                GamerParam::saveParam($maf, 'mafiya_is_top', 1);
                */
                
            } else { //если мафов больше нет. Проверим, есть ли нож у двуликого? и дадим
                $dvulikiy = GameUser::where('game_id', $gamer->game_id)->where('role_id', 20)->first();
                if ($dvulikiy) {
                    $isKnight = DeactivatedCommand::where(['game_id' => $gamer->game_id, 'command' => 'dvulikiy_find'])->first();
                    if (!$isKnight) {
                        DeactivatedCommand::create(['game_id' => $gamer->game_id, 'command' => 'dvulikiy_find']);
                        $text = "Последний Дон умер. Теперь в твои руки попал нож...";
                        $bot = AppBot::appBot();
                        $bot->sendAnswer([['text' => $text]], $dvulikiy->user_id);
                    }
                }
            }
        }
    }

    public static function voting_yesno_itogs($voiting_id)
    {
        $voiting = Voiting::where('id', $voiting_id)->first();
        $bot = AppBot::appBot();
        if ($voiting) {
            $voiting->is_active = 2;
            $voiting->save();
            //удалим сообщение
            if ($voiting->game->options) {
                $options = json_decode($voiting->game->options, true);
                try {
                    $bot->getApi()->deleteMessage(['chat_id' => $voiting->game->group_id, 'message_id' => $options['message_id']]);
                } catch (Exception $e) {
                }
            }
        }
        $answers = self::voting_yesno_results($voiting_id);
        $gamer = GameUser::where('id', $answers['gamer_id'])->first();
        $isKill = false;
        if ($answers['yes'] > $answers['no']) {
            if ($gamer) {
                $text = "<b>Результаты голосования:</b>\n{$answers['yes']}👍 | {$answers['no']}👎
                \n<i>Сегодня был повешен</i> <a href='tg://user?id={$gamer->user_id}'>{$gamer->user}</a>\nРоль его была <b>{$gamer->role}</b>";
                $gamer->kill_night_number = $gamer->game->current_night;
                $gamer->is_active = 0;
                $gamer->save();

                self::actionsAfterDie($gamer);
                $isKill = true;
            }
        } else {
            $text = "<b>Результаты голосования:</b>\n{$answers['yes']}👍 | {$answers['no']}👎
            \n<i>Жители города не смогли прийти к единому мнению... Они разошлись по своим делам, так никого и не повесив...</i>";
        }
        if(isset($text)) {
            $message = ['text' => $text];
            self::message(['message' => $message, 'chat_id' => $voiting->game->group_id]);
        }

        if ($isKill) {
            GamerFunctions::messagesAfterKills($gamer->game);
            GamerFunctions::topGamersIfNeed($gamer->game);
        }
        //запускаем следующую ночь
        $gameOver = self::isGameOver($voiting->game_id);
        if ($gameOver) {
            self::stopGame($voiting->game, $gameOver);
        } else {
            if ($isKill && $gamer && $gamer->role->kill_message && !empty(trim($gamer->role->kill_message))) { //рассылка бомбы после повешения. только если игра не закончилась
                $killmess = json_decode($gamer->role->kill_message, true);
                if (isset($killmess['gallow'])) {
                    $func = $killmess['gallow'];
                    GamerFunctions::$func($gamer);
                }
            }
            $options = ['class' => Game::class, 'method' => 'night', 'param' => $voiting->game_id];
            TaskModel::create(['game_id' => $voiting->game_id, 'name' => 'ночь. Игра ' . $voiting->game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 9]);
        }
    }
    public static function achievementUsers($winners)
    {
        //проверить, не достиг ли игрок нового уровня
        //сколько побед у каждого за его роль
        $bot = AppBot::appBot();
        $winsMatrix = [];
        foreach ($winners as $winner) {
            $winsMatrix[$winner->user_id][$winner->role_id] = $winner;
        }
        $winsCounts = GameUser::selectRaw("user_id, role_id, count(*) as win_amount")->where('is_active', 2)->groupBy('user_id')->groupBy('role_id')->get();
        foreach ($winsCounts as $winCount) {
            if (isset($winsMatrix[$winCount->user_id][$winCount->role_id])) {
                $win = $winsMatrix[$winCount->user_id][$winCount->role_id];
                if ($winCount->win_amount < 10) continue;
                $achiev = Achievement::where('role_id', $win->role_id)->where('win_amount', '<=', $winCount->win_amount)->orderByDesc('win_amount')->first();
                if (!$achiev) continue;
                $uAchiev = UserAchievement::where(['user_id' => $win->user_id, 'achievement_id' => $achiev->id])->first();
                if (!$uAchiev) { //добавим достижение и пришлем уведомление
                    UserAchievement::create(['user_id' => $win->user_id, 'achievement_id' => $achiev->id]);
                    $bot->sendAnswer([['text' => "<b>Новое достижение. </b>\n\n" . $achiev->name]], $win->user_id);
                }
            }
        }
    }
    public static function stopGame($game, $gameOver)
    { //остановить процесс и объявить победителей        
        
        DB::table('tasks')->where('game_id', $game->id)->update(['is_active' => 0]);  //убиваем все задачи
        $game->status = 2;
        $game->save();

        $isWinnerIfLive = [27, 32, 35, 29, 26]; //победил если выжил, не важно с кем
        if ($gameOver['winners'] == 3) $isWinnerIfLive[] = 37;
        if($gameOver['winners'] ==1) {
            $gbnParams = GamerParam::gameBeforeNightsParams($game,$game->current_night+1);
            if(isset($gbnParams['oboroten_sel']) && $gbnParams['oboroten_sel'] == 1) {
                $isWinnerIfLive[] = 37;
            }
        }

        //в командной игре убираем исключение для отдельных ролей
        if(!$game->is_team) {
            //отедльные роли
            $lubovnik = GameUser::where('game_id', $game->id)->where('role_id', 7)->first();
            if ($lubovnik) { //есть ли любовник, и является ли он победителем
                $krasotka = GameUser::where('game_id', $game->id)->where('role_id', 6)->first();
                $lubovnik_check = GamerParam::where(['game_id' => $game->id, 'param_name' => 'lubovnik_find', 'param_value' => $krasotka->id])->first();
                if ($lubovnik_check) {
                    $lubovnik->is_active = 2;
                    $lubovnik->save();
                }
            }
            $muzh = GameUser::where('game_id', $game->id)->where('role_id', 34)->first();
            if ($muzh) {
                $muzh_check = GamerParam::where(['game_id' => $game->id, 'param_name' => 'muzh_win'])->first();
                if ($muzh_check) {
                    $muzh->is_active = 2;
                    $muzh->save();
                }
            }

            $sutener = GameUser::where('game_id', $game->id)->where('role_id', 36)->first();
            if ($sutener) { //есть ли сутенер, и является ли он победителем
                $sutener_check = GamerParam::where(['game_id' => $game->id, 'param_name' => 'sutener_find'])->first();
                if ($sutener_check) {
                    $sutener->is_active = 2;
                    $sutener->save();
                }
            }
        }

        //команды
        $winners = GameUser::with('role')->where('game_id', $game->id)->where('is_active', 1)->get();

        if ($gameOver['winners'] == 1) { //миры                   
            foreach ($winners as $winner) {
                if ($winner->role->role_type_id == 1) { //миров в победители
                    $winner->is_active = 2;
                } else if (in_array($winner->role_id, $isWinnerIfLive)) {
                    $winner->is_active = 2;
                } else {  //остальные проиграли
                    $winner->is_active = 0;
                }
                //if($winner->first_role_id) $winner->role_id = $winner->first_role_id;
                $winner->save();
            }
        }
        if ($gameOver['winners'] == 2) { //мафы                    
            foreach ($winners as $winner) {
                if ($winner->role->role_type_id == 2) { //мафов в победители
                    $winner->is_active = 2;
                } else if (in_array($winner->role_id, $isWinnerIfLive)) {
                    $winner->is_active = 2;
                } else {  //остальных в проигравшие
                    $winner->is_active = 0;
                }
                /*
                if ($winner->role_id == 10 && GamerFunctions::oderjimIsNeytral($game)) {
                    $winner->is_active = 2; //одержимый если выжил и стал нейтралом победил
                }
                    */
                // if($winner->first_role_id) $winner->role_id = $winner->first_role_id;
                $winner->save();
            }
        }
        if ($gameOver['winners'] == 3) {
            foreach ($winners as $winner) {
                if (in_array($winner->id, $gameOver['winner_list'])) $winner->is_active = 2;
                else if (in_array($winner->role_id, $isWinnerIfLive)) {
                    $winner->is_active = 2;
                } else $winner->is_active = 0;

                // if($winner->first_role_id) $winner->role_id = $winner->first_role_id;
                $winner->save();
            }
        }
        //снова получим победителей, после обновления статуса
        $winners = GameUser::with('role')->with('user')->where('game_id', $game->id)->where('is_active', 2)->get()->all();
        $text_winners_arr = [];
        $index = 1;
        $winmess = ['text' => "🌟 Вы победили. Ваш выигрыш 50 💶"];
        foreach ($winners as $winner) {
            $text_winners_arr[] = $index . ". ".($winner->team ? self::COMMAND_COLORS[$winner->team] : '')
             . self::userUrlName($winner->user) . ' - ' . $winner->role;
            $index++;
            //дадим баксы победителям
            if ($winner->user_id > 10001) {
                $user = $winner->user;
                $user->addBalance(Currency::R_WINDBUCKS, 50);
                self::message(['message' => $winmess, 'chat_id' => $winner->user_id]);
            }
        }
        self::achievementUsers($winners);

        $otherGamers = GameUser::with('role')->with('user')->where('game_id', $game->id)->where('is_active', 0)->get()->all();
        $others = [];
        foreach ($otherGamers as $other) {
            $others[] = $index . ". " .($other->team ? self::COMMAND_COLORS[$other->team] : '')
            . self::userUrlName($other->user) . ' - ' . $other->role;
            $index++;
            //дадим баксы если выжил 4 или >=5
            if ($other->kill_night_number < 5) { //выжил 4 ночи
                if ($other->user_id > 10001) {
                    $user = $other->user;
                    $user->addBalance(Currency::R_WINDBUCKS, 20);
                    $omess = ['text' => "Ваш выигрыш 20 💶"];
                    self::message(['message' => $omess, 'chat_id' => $other->user_id]);
                }
            } else {
                if ($other->user_id > 10001) {
                    $user = $other->user;
                    $user->addBalance(Currency::R_WINDBUCKS, 30);
                    $omess = ['text' => "Ваш выигрыш 30 💶"];
                    self::message(['message' => $omess, 'chat_id' => $other->user_id]);
                }
            }
        }
        //обявляем победителей        
        $start = strtotime($game->start_at); //strtotime($game->created_at);
        $end = time();
        $longGame = round(($end - $start) / 60);
        $message = ['text' => "<b>Игра окончена!</b>\n\n<b>Победители:</b>\n" . implode("\n", $text_winners_arr) .
            "\n\n<b>Другие игроки:</b>\n" . implode("\n", $others) . "\n\n<i>Игра длилась $longGame мин.</i>"];
        self::message(['message' => $message, 'chat_id' => $game->group_id]);
    }
    protected static function createGameProcess($game_id)
    {
        $game = GameModel::where('id', $game_id)->first();
        if ($game) {
            $chat_id = $game->group_id;
            //1-e сообщение
            $message = ['text' => "<b>🃏Каждый получил свою роль, но все ли смогут её сохранить?</b>\nТучи над улицами Винда начинают сгущаться."];
            $message['inline_keyboard']['inline_keyboard'] = [[[
                'text' => 'Узнать свою роль',
                'url' => "https://t.me/".config('app.bot_nick')
            ]]];
            $params = ['chat_id' => $chat_id, 'message' => $message];
            $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
            TaskModel::create(['game_id' => $game_id, 'name' => '1-e сообщение. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE)]);
            $options = ['class' => Game::class, 'method' => 'night', 'param' => $game_id];
            TaskModel::create(['game_id' => $game_id, 'name' => '1-я ночь. Игра ' . $game_id, 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 4]);
        }
    }

    public static function vigruzka()
    {
        $roles = GameRole::all();
        $commands = [];
        foreach ($roles as $role) {
            if ($role->night_message_priv && !empty(trim($role->night_message_priv))) {
                $mess = json_decode(trim($role->night_message_priv), true);
                foreach ($mess['buttons'] as $btn) {
                    $commands[$btn['callback']] = [['text' => $btn['text']]];
                }
            }
        }
        return $commands;
    }

    public static function isGameOver($game_id)
    {
        $game = GameModel::where('id', $game_id)->first();
        $gamers = GameUser::with('role')->where('game_id', $game_id)->where('is_active', 1)->get();
        if ($game->is_team) {  //командая игра
            $teams = [];
            foreach ($gamers as $gamer) {
                $teams[$gamer->team] = 1;
                if (isset($teams[1]) && isset($teams[2]))  return [];  //победителя пока нет
            }
            //если цикл открутили и не вышли, значит есть команда-победитель
            $keys = array_keys($teams);
            $luzer_team = $keys[0] == 1 ? 2 : 1;
            $winTeamUsers = GameUser::where('game_id', $game_id)->where('team', $keys[0])->get()->all();
            $result = ['winners' => 3, 'winner_list' => array_column($winTeamUsers, 'id')];
            DB::table('user_game_roles')->where(['game_id' => $game_id, 'team' => $keys[0]])->update(['is_active' => 2]);
            DB::table('user_game_roles')->where(['game_id' => $game_id, 'team' => $luzer_team])->update(['is_active' => 0]);
            return $result;
        }

        $gRtCounts = [];
        $result = [];
        $killNejtrals = [28, 30];
        if(GamerFunctions::oderjimIsNeytral($game)) $killNejtrals[] = 10; //добавляем одержимого, если успел перевоплотиться
        $killMafs = [17, 23, 25];
        if(GamerFunctions::ifDvulikiyShouldHaveKnife($game)) $killMafs[] = 20; //добавляем двуликого, если он получает нож
        $isKillNejtrall = [];
        $isKillMafs = [];
        $mirs = GamerParam::where('game_id',$game->id)->where('param_name','mir')->get()->all();
        $mirIds = array_column($mirs,'gamer_id');
        foreach ($gamers as $gamer) {
            if(in_array($gamer->id,$mirIds)) {
                $gRtCounts[1] = ($gRtCounts[1] ?? 0) + 1;
            } elseif (in_array($gamer->role_id, $killNejtrals)) {
                $isKillNejtrall[] = $gamer->id;
                $gRtCounts[3] = ($gRtCounts[3] ?? 0) + 1;
            } elseif (in_array($gamer->role_id, $killMafs)) {
                $isKillMafs[] = $gamer->id;
                $gRtCounts[2] = ($gRtCounts[2] ?? 0) + 1;
            } else {
                if(!$gamer->role) continue;
                $gRtCounts[$gamer->role->view_role_type_id ?? $gamer->role->role_type_id ?? 0] = ($gRtCounts[$gamer->role->view_role_type_id ?? $gamer->role->role_type_id] ?? 0) + 1;
            }
        }
        if ($isKillNejtrall) {
            if (!isset($gRtCounts[2]) && !isset($gRtCounts[1]))  $result = ['winners' => 3, 'winner_list' => $isKillNejtrall];
        } else {
            if (($gRtCounts[2] ?? 0) >= ($gRtCounts[1] ?? 0)) {
                $result = ['winners' => 2];
            }
            if (($gRtCounts[2] ?? 0) < ($gRtCounts[1] ?? 0) && !$isKillMafs) {
                $result = ['winners' => 1];
            }
        }

        // Log::info('Game over testing', [
        //     '$killMafs' => print_r($killMafs, true),
        //     '$killNejtrals' => print_r($killNejtrals, true),
        //     '$isKillNejtrall' => print_r($isKillNejtrall, true),
        //     '$isKillMafs' => print_r($isKillMafs, true),
        //     '$result' => print_r($result, true),
        //     '$gRtCounts' => print_r($gRtCounts, true),
        //     '$mirIds' => print_r($mirIds, true)
        // ]);

        if ($result) {
            $game = GameModel::where('id', $game_id)->first();
            $game->status = 2;
            $game->save();
            // DB::table('user_game_roles')->where(['game_id'=>$game_id, 'is_active' => 1 ])->update(['is_active' => 2]);  //победили
        }
        return $result;
    }

    public static function hasRightToStart($user_id, $group_id)
    {
        $group = BotGroup::where('id', $group_id)->first();
        if (!$group) return false;
        if ($group->who_add == $user_id) return true;
        $isRight = UserProduct::where(['user_id' => $user_id, 'group_id' => $group_id])->whereIn('product_id', [1, 2, 3, 4])
            ->whereNotNull('was_used')->where('is_deactivate', 0)->where('avail_finish_moment', '>', date('Y-m-d H:i:s'))->first();
        if (!$isRight) {
            $bot = AppBot::appBot();
            try {
                $chatMember = $bot->getApi()->getChatMember(['chat_id' => $group_id, 'user_id' => $user_id]);
                if ($chatMember && $chatMember->status == 'administrator') return true;
            }
            catch(Exception $e) {
                $mess = ['text'=>"Не смог проверить права пользователя в группе. Предоставьте мне в этой группе возможность проверять права пользователей"];
                $bot->sendAnswer([$mess],$group_id);
                return false;
            }
        }
        return ($isRight ? true : false);
    }
    public static function isGroupAdmin($user_id, $group_id)
    {
        $group = BotGroup::where('id', $group_id)->first();
        if (!$group) return false;
        if ($group->who_add == $user_id) return true;
        return false;
    }
    public static function editRegistrationMessageStandart($game)
    {
        $gamers = GameUser::with('user')->where('game_id', $game->id)->get();
        $gmCount = $gamers->count();
        if (!$game->options) return $gamers;
        $options = json_decode($game->options, true);
        $txtUsers = [];
        foreach ($gamers as $gamer) {            
            $txtUsers[] = Game::userUrlName($gamer->user);
        }
        $grpmess['text'] = "Ведётся набор в игру\n\n" . implode("\n", $txtUsers) .
            "\n\nПрисоединилось: $gmCount игроков";
        $grpmess['reply_markup'] = json_encode(['inline_keyboard' => [[['text' => 'Присоединиться к игре', 'url' => "https://t.me/".config('app.bot_nick')."?start=game_" . $game->id]]]]);
        if (isset($options['message_id'])) {
            $grpmess['chat_id'] = $game->group_id;
            $grpmess['message_id'] = $options['message_id'];
            $grpmess['parse_mode'] = 'HTML';

            //Log::info('editRegistrationMessageStandart telegram info', ['error' => print_r($grpmess, true)]);
            $bot = AppBot::appBot();
            try {
                $bot->getApi()->editMessageText($grpmess);
            }
            catch(Exception $e) {
                //Log::info('editRegistrationMessageStandart error', ['error' => print_r($e, true)]);
                $res['text'] = "Я @Windmafia_bot не смог отредактировать сообщение регистрации на игру. Предоставьте мне право редактирования сообщений в группе!";
                $bot->sendAnswer([$res],$grpmess['chat_id']);
            }            
        }
        return $gamers;
    }
    public static function editRegistrationMessageTeam($game)
    {
        $gamers = GameUser::with('user')->where('game_id', $game->id)->where('is_active', 1)->orderBy('team')->orderBy('id')->get();
        if (!$game->options) return $gamers;
        $gmCount = $gamers->count();
        $options = json_decode($game->options, true);
        $txtUsers = [];
        foreach ($gamers as $gamer) {
            $prefTeam = isset(Game::COMMAND_COLORS[$gamer->team]) ? Game::COMMAND_COLORS[$gamer->team] . ' ' : '';
            $txtUsers[] = $prefTeam . Game::userUrlName($gamer->user);
        }
        $grpmess['text'] = "Ведётся набор в игру\n\n" . implode("\n", $txtUsers) .
            "\n\nПрисоединилось: $gmCount игроков";
        $ikb = [];
        foreach (Game::COMMAND_COLORS as $k => $v) {
            $ikb['inline_keyboard'][] = [['text' => $v . ' Присоединиться к игре', 'url' => "https://t.me/".config('app.bot_nick')."?start=teamgm_" . $game->id . "_" . $k]];
        }
        $grpmess['reply_markup'] = json_encode($ikb);
        if (isset($options['message_id'])) {
            $grpmess['chat_id'] = $game->group_id;
            $grpmess['message_id'] = $options['message_id'];
            $grpmess['parse_mode'] = 'HTML';
            $bot = AppBot::appBot();
            try {
                $bot->getApi()->editMessageText($grpmess);
            }
            catch(Exception $e) {
                //Log::info('editRegistrationMessageTeam error', ['error' => print_r($e, true)]);
                $res['text'] = "Я не смог отредактировать сообщение регистрации. Предоставьте мне право редактирования сообщений";
                $bot->sendAnswer([$res],$game->group_id);
            }
        }
        return $gamers;
    }
    public static function editRegistrationMessage($game)
    {
        if ($game->is_team) return self::editRegistrationMessageTeam($game);
        else return self::editRegistrationMessageStandart($game);
    }
}
