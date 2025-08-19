<?php
namespace App\Modules\Game;

use App\Models\Union;
use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\GamerParam;
use App\Models\RoleAction;
use App\Modules\Bot\AppBot;
use App\Models\NightFunction;
use App\Modules\Game\Roles\Ono;
use App\Models\UnionParticipant;
use App\Modules\Game\Roles\Poet;
use App\Models\RolesNeedFromSave;
use App\Modules\Game\Roles\Bomba;
use App\Modules\Game\Roles\Joker;
use App\Modules\Game\Roles\Lover;
use App\Modules\Game\Roles\Puaro;
use App\Modules\Game\Roles\Vedma;
use App\Models\DeactivatedCommand;
use App\Modules\Game\Roles\Doctor;
use App\Modules\Game\Roles\Dubler;
use App\Modules\Game\Roles\Mafiya;
use App\Modules\Game\Roles\Manjak;
use App\Modules\Game\Roles\Ninzya;
use App\Modules\Game\Roles\Advokat;
use App\Modules\Game\Roles\Ciganka;
use App\Modules\Game\Roles\Lunatik;
use App\Modules\Game\Roles\Student;
use App\Modules\Game\Roles\Sutener;
use App\Modules\Game\Roles\Dvulikiy;
use App\Modules\Game\Roles\Krasotka;
use App\Modules\Game\Roles\Oboroten;
use App\Modules\Game\Roles\Obsessed;
use App\Modules\Game\Roles\BlackLady;
use App\Modules\Game\Roles\Godfather;
use App\Modules\Game\Roles\Gurnalist;
use App\Modules\Game\Roles\MafDoctor;
use App\Modules\Game\Roles\Prezident;
use App\Modules\Game\Roles\Professor;
use App\Modules\Game\Roles\Ugolovnik;
use App\Modules\Game\Roles\MainNinzya;
use App\Modules\Game\Roles\Muzh;
use App\Modules\Game\Roles\Teni;
use App\Modules\Game\Roles\TvPresenter;
use Illuminate\Support\Facades\Log;

class GamerFunctions {
    use Doctor;
    use Puaro;
    use Poet;
    use Krasotka;
    use Mafiya;
    use BlackLady;
    use Advokat;
    use Manjak;
    use Ono;
    use Oboroten;
    use Bomba;
    use Lover;
    use Ciganka;
    use Vedma;
    use Sutener;
    use Obsessed;
    use MafDoctor;
    use Prezident;
    use Dvulikiy;
    use Joker;
    use Gurnalist;
    use Dubler;
    use Lunatik;
    use Student;
    use Professor;
    use Ninzya;
    use Ugolovnik;
    use Godfather;
    use MainNinzya;
    use TvPresenter;
    use Teni;
    use Muzh;

    protected static $savedClearACtions = [];    
    public static function saved_clear_action($action) {
        self::$savedClearACtions[] = $action;
    }
    public static function get_clear_actions() {
        return self::$savedClearACtions;
    }   
    
    public static function gamer_set_move($params, $action, $night_func = null, $priority = 100, $onlyOne = false, $bafmethod = false) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if(!$gamer) return '';
        if($bafmethod) { 
            self::execBafMethod($gamer,$bafmethod);
        }
        if($onlyOne) {
            GamerParam::firstOrCreate(['gamer_id'=>$gamer->id,'param_name'=>$action],
            ['night'=>$gamer->game->current_night, 'game_id'=>$gamer->game_id, 'param_value'=>$params['cmd_param']]);
        }
        else {
            GamerParam::saveParam($gamer,$action,$params['cmd_param']);
        }
        if($night_func) NightFunction::push_func($gamer->game, $night_func,$priority);
        return '';
    }

    // public static function victim_kill_killer($killer_id, $victim_id) {
    //     $killer = GameUser::where('id', $killer_id)->first();

    //     // if($killer->game && GamerParam::where('')) {

    //     // }
    // }

    public static function get_last_victim_killer(GameUser $victim) {
        if($param = GamerParam::where('param_name', 'like', '%kill%')
                ->orWhere('param_name', 'vedma_morozit')
                ->where('game_id', $victim->game->id)
                ->where('param_value', $victim->id)
                ->latest()->first()) {
            $gamer = $param->gamer;
            $param->update(['param_value', 1]);
            return $gamer;
        }

        return false;
    }
    
    public static function user_kill($killer_id, $victim_id) {
        self::user_deactivate(['killer_id'=>$killer_id, 'cmd_param'=>$victim_id]);
    }
    public static function user_deactivate($params, $bafActives = true) //убийство, но не повешенье
    {  //отключить пользователя
        //user_id,  cmd_param
        //$gamer = GameUser::where('user_id', $params['cmd_param'])->where('is_active', 1)->first();
        // Log::info('user_deactivate', [
        //     '$params' => print_r($params, true),
        //     '$bafActives' => $bafActives
        // ]);
        $gamer = GameUser::where('id', $params['cmd_param'])->first(); //->where('is_active', 1)  убрали проверку активности, чтоб мог убить не один
        $killer = null;
        
        if ($gamer) {  
            $gameParams = GamerParam::gameParams($gamer->game);
            $gamerParams = GamerParam::gamerParams($gamer);

            // Log::info('Game params baff_shield_checking', [
            //     '$gameParams' => print_r($gameParams, true),
            // ]);
            if(isset($params['killer_id'])) {
                $killer = GameUser::where('id', $params['killer_id'])->first();                               
            }  
            else $killer = GameUser::where('user_id', $params['user_id'])->where('game_id', $gamer->game->id)->first();    //->where('is_active', 1) проверка на активность убрана из-за бага с убийством друг друга
            if(!$killer) return; //убийство без убийцы невозможно

            // Log::info('Gamer baff_shield_checking', [
            //     '$gamer' => $gamer->user_id,
            //     '$killer' => $killer->user_id
            // ]);

            //если оборотень. Убийство оборотня
            if($gamer->role_id == 37) {
                $select = GamerParam::where(['game_id'=>$gamer->game_id,'gamer_id'=>$gamer->id,'param_name'=>'oboroten_sel'])->first();
                if($select && $killer) { //выбор есть. в зависимости от киллера должен перевоплотиться
                    $reincarnated = false;
                    if(in_array($killer->role_id,[17,25]) && $select->param_value == 2) { //в мафию
                        $gamer->first_role_id = $gamer->role_id;
                        $gamer->role_id = 25;
                        $gamer->save();
                        GamerParam::saveParam($gamer,'obor_reincarnate',"🤵🏻 Дон Корлеоне,🤵🏻Мафию,".$killer->id);                                              
                        $reincarnated = true;                        
                    }
                    if($killer->role_id == 4 && $select->param_value == 1) { //убил ком, в Сержанта
                        $gamer->first_role_id = $gamer->role_id;
                        $gamer->role_id = 5;
                        $gamer->save();
                        GamerParam::saveParam($gamer,'obor_reincarnate',"🕵️Комиссар Пуаро,👮🏼Сержанта,".$killer->id);                        
                        $reincarnated = true;                        
                    }

                    if($reincarnated) {
                        GamerParam::saveParam($gamer,'nightactionempty',1); //делаем фиктивный ход, чтоб не убил сон
                    }
                }
            }

            // Log::info('Gamer $gamer->isActive()', [
            //     '$gamer->isActive()' => $gamer->isActive()
            // ]);

            if(!$gamer->isActive()) {  //уже убит
                $gamer->addKiller($killer->id);
                if($gamer->role_id == 11) { //бомба
                    self::bomba_kill($gamer,$killer);
                }
                return;
            }
            if($killer->role_id != 11 && !$killer->isActive() && ($killer->kill_night_number < $gamer->game->current_night)) return;

            if(!(isset($params['ninzya']) && $params['ninzya'] == 4)) {
                if($gamer->role_id == 23) { //уголовник
                    $message['text'] = "Вашим выбором стал 🤵‍♂Уголовник! Он не так прост, убить его нельзя, вам придется линчевать его на дневном собрании!";
                    if($killer->role_id != 11) { //не бомба
                        Game::message(['message'=>$message,'chat_id'=>$killer->user_id]);
                    }
                    return;
                }
                if($gamer->role_id == 26) { //бьют тень                    
                    if(isset($gameParams['teni_select'])) {
                        $newGamer = GameUser::where('id',$gameParams['teni_select'])->first();
                        if($newGamer) {
                            $victimMessage = ['text'=>'Этой ночью вас хотели убить, но вы притворились тенью '.Game::userUrlName($newGamer->user).' и он погибает!'];
                            Game::message(['message'=>$victimMessage,'chat_id'=>$gamer->user_id]);
                            if($newGamer->role_id != 11) { //если не бомба
                                $killer = $gamer;  //переопределяем киллера (им становится Тень)
                            }
                            $gamer = $newGamer;  //переопределяем жертву  
                            if(self::isVedmaTreat($gamer->game,$gamer->id)) {
                                //убийства не будет. ведьма спасла
                                $vicMess = "🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Она исцелила тебя от ".$killer->role;
                                $vedmaMess = "Вы спасли ".$gamer->user." от ".$killer->role;  
                                self::setVedmaTreatMessage($gamer->id,$gamer->user_id,$vicMess,$vedmaMess,1);
                                return null;
                            }                         
                        }
                    }
                }
            }

            if(isset($gamerParams['shield_result']))
                return null;

            if($bafActives) {
                //сначала проверим бафы
                $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$gamer->game_id,
                'user_id'=>$gamer->user_id,'is_active'=>1])->get();
                foreach($activeBafs as $activeBaf) {
                    $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
                    $actbaf = new $class($activeBaf);
                    $result = $actbaf->kill($gamer);
                    // Log::info('baff_shield_checking', [
                    //     'result' => print_r($result, true),
                    // ]);
                    if($result) {
                        GamerParam::saveParam($gamer, 'shield_result', 1);
                        //проверим, использовал ли убийца пистолет
                        $isGun = GamerParam::where(['game_id'=>$killer->game_id,'night'=>$killer->game->current_night,
                        'gamer_id'=>$killer->id,'param_name'=>'gunyes'])->first();
                        if($isGun) {
                            $message = ['text'=>"Убийца использовал пистолет и пробил вашу защиту"];
                            Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]);
                            $message = ['text'=>"...Кто-то воспользовался пистолетом и пробил щит..."];
                            Game::message(['message'=>$message,'chat_id'=>$gamer->game->group_id]);                        
                        }
                        else if(isset($params['ninzya']) && $params['ninzya'] > 1) {
                            $message = ['text'=>"🥷🏻Ниндзя оказался сильнее и пробил вашу защиту"];
                            Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]);
                        }
                        else {
                            if(is_array($result)) {
                                $message = ['text'=>$result['user_mess']];
                                Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]);
                                $message = ['text'=>$result['group_mess']];
                                Game::message(['message'=>$message,'chat_id'=>$gamer->game->group_id]);
                            }
                            return false;  //прерываем функцию. убийства не будет
                        }
                    }
                }
            }            
            //если психотерапевт. переопределяем убийцу и жертву, при условии что это 1-й выстел в Психа
            if($gamer->role_id == 35 && $killer->role_id != 23) {                
                $beforeParams =  GamerParam::gameBeforeNightsParams($gamer->game);
                if(!isset($beforeParams['psyhoterapevt'])) {                    
                    GamerParam::saveParam($killer,'psyhoterapevt',1);
                    self::victim_message($gamer->id,"На тебя было совершенно покушение, один из игроков поплатился жизнью..");
                    $liveGamers = GameUser::where('game_id',$gamer->game_id)->where('is_active',1)->get()->all();                    
                    $rikoshetIndex = random_int(0, count($liveGamers)-1);                   
                    while($liveGamers[$rikoshetIndex]->id == $gamer->id) $rikoshetIndex = random_int(0, count($liveGamers)-1);
                    $rikoshetGamer = $liveGamers[$rikoshetIndex];

                    $killer = $gamer;
                    $gamer = $rikoshetGamer;

                    GamerParam::saveParam($killer,'rikoshet',$rikoshetGamer->id);
                    //не лечила ли ведьма того, на кого пришелся рикошет
                    if(self::isVedmaTreat($gamer->game,$rikoshetGamer->id)) {
                        self::setVedmaTreatMessage($gamer->id,$gamer->user_id,"🧝‍♀️ Ведьма сварила для тебя лечебное зелье! Она исцелила тебя от ✨Психотерапевта",
                            "Вы спасли ".Game::userUrlName($rikoshetGamer->user)." от ✨Психотерапевта",1);
                        NightFunction::push_func($gamer->game, 'sendVedmaTreatMessage');                                                
                        return null;
                    }
                }
            }
            
            if ($killer) $gamer->killer_id = $killer->id;
            $gamer->is_active = 0;
            $gamer->kill_night_number = $gamer->game->current_night; // фиксируем номер ночи, в которую убит
            $gamer->save();

            Game::actionsAfterDie($gamer);
            
            if($gamer->role->kill_message && !empty(trim($gamer->role->kill_message))) {
                $killmess = json_decode($gamer->role->kill_message,true);
                if(isset($killmess['kill'])) {
                    $func = $killmess['kill'];
                    self::$func($gamer, $killer);
                    //return null;
                }
            }
            /*
            $gameOver = Game::isGameOver($gamer->game_id);
            if ($gameOver) {
                Game::stopGame($gamer->game, $gameOver);
            }
            else {  */
                 //последнее слово
                $bot = AppBot::appBot();
                $bot->addCmd('lastword_'.$gamer->id."_",$gamer->user_id);
                $message = ['text'=>"Вы можете сказать последнее слово. Оно будет отправлено в чат"];
                Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]);
                //----------------------------------------------------------------
          //  }
        }
    }
    
   
    public static function victim_message($victim_id, $text) {
        $victim = GameUser::where('id',$victim_id)->first();
        if($victim) {
            $bot = AppBot::appBot();
            $bot->sendAnswer([['text'=>$text]], $victim->user_id);    
            self::ciganka_message($victim, $text);  //ф-я сама проверит есть ли циганка, и не погашена ли она красоткой. и отправит ей сообщение
        }
    }
    public static function isTreated($gamer_id, $game, $role_id=null) {
        $isTreated = self::isDoctorTreate($game, $gamer_id) || self::isVedmaTreat($game,$gamer_id) || self::isMafDoctorTreate($game, $gamer_id, $role_id);
        return $isTreated;
    }
    
    public static function isCanMove(GameUser $gamer) {
        $noCan = self::isVedmaFrost($gamer->game,$gamer->id) || self::isKrasotkaSelect($gamer->game,$gamer->id);        
        return !$noCan || self::isTreated($gamer->id, $gamer->game, $gamer->role_id);
    }
    public static function isCanMove2($game, $gamer_id) {
        $noCan = self::isVedmaFrost($game,$gamer_id) || self::isKrasotkaSelect($game,$gamer_id);        
        return !$noCan || self::isTreated($gamer_id, $game);
    }
    public static function isCanMoveWithoutKrasotka(GameUser $gamer) {
        $noCan = self::isVedmaFrost($gamer->game,$gamer->id);        
        return !$noCan || self::isTreated($gamer->id, $gamer->game, $gamer->role_id);
    }
    public static function execBafMethod(GameUser $gamer, string $method) {
        $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$gamer->game_id,'user_id'=>$gamer->user_id,'is_active'=>1])->get();
        foreach($activeBafs as $activeBaf) {
            $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
            $actbaf = new $class($activeBaf);
            if(method_exists($actbaf, $method)) {
                $result = $actbaf->$method($gamer);
                if($result) {               
                   return $result;
                }
            }            
        }
        return null;
    } 
    public static function messagesAfterKills($game) {
        self::ifOderjimAngryMess($game);
        self::ifSergantTop($game);
        self::ifAssistentTop($game);
        self::ifOborotenReincarnated($game);
        self::ifDublerChange($game);
    }
    public static function topGamersIfNeed($game) {
        $gamers = GameUser::where('game_id',$game->id)->where('is_active',1)->whereIn('role_id',[4,5,8,14,15,17,25])->get()->all();
        $roleIds = array_column($gamers, 'role_id');
        $gamerByRole = [];
        foreach($gamers as $gamer) {
            $gamerByRole[$gamer->role_id] = $gamer;
        }
        $needTest = false;
        if(isset($gamerByRole[5]) && !isset($gamerByRole[4]) && !self::checkIfDublerIsNotChanged($game, 4)) { //есть сержант но нет Коммисара
            $gamerByRole[5]->role_id = 4;
            $gamerByRole[5]->save();
            GamerParam::saveParam($gamerByRole[5],'sergant_top',1);
            $needTest = true;
        }
        if(isset($gamerByRole[14]) && !isset($gamerByRole[15])) { //есть ассистент но нет Дока
            $gamerByRole[14]->role_id = 15;
            $gamerByRole[14]->save();
            GamerParam::saveParam($gamerByRole[14],'assistent_top',1);
            $needTest = true;
        }
        if(isset($gamerByRole[25]) && !isset($gamerByRole[17])) { //есть маф но нет Дона
            $gamerByRole[25]->role_id = 17;
            $gamerByRole[25]->save();           
            $bot = AppBot::appBot();
            $res['text'] = "Ты повышен до <b>🤵🏻 Дона Корлеоне</b>";

            $bot->sendAnswer([$res],$gamerByRole[25]->user_id);
            $upar = UnionParticipant::where('gamer_id',$gamerByRole[25]->id)->first();
            if($upar) {
                $upar->pos_in_union = 1;
                $upar->save();

                //сообщение в группу
                $groupMess = ['text'=>"<b>🤵🏻 Мафия</b> повышен до <b>🤵🏻 Дона Корлеоне</b>"];
                $bot->sendAnswer([$groupMess],$gamerByRole[25]->group_id);

                //сообщим союзу
                $participants = UnionParticipant::with('gamer')->where('union_id',$upar->union_id)->get();
                $partMess = ['text'=>"<b>".Game::userUrlName($gamerByRole[25]->user)."</b> повышен до <b>🤵🏻 Дона Корлеоне</b>"];
                foreach($participants as $particip) {
                    if(!$particip->gamer) continue;
                    if($particip->id != $upar->id && $particip->gamer->isActive()) {
                        usleep(35000);
                        $bot->sendAnswer([$partMess],$gamerByRole[25]->user_id);
                    }
                }

            }
        }
        if($needTest) {
            self::ifAssistentTop($game);
            self::ifSergantTop($game);
        }
    }
}