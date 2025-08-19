<?php

namespace App\Modules\Bot;

use Exception;
use App\Models\Baf;
use App\Models\MafiaEvent;
use App\Models\Vote;
use App\Models\Offer;
use App\Models\BotUser;
use App\Models\BuyRole;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\UserBaf;
use App\Models\Voiting;
use App\Models\BotGroup;
use App\Models\GameRole;
use App\Models\GameUser;
use App\Modules\Bot\Bot;
use App\Models\YesnoVote;
use App\Models\ChatMember;
use App\Models\GamerParam;
use App\Models\GroupTarif;
use App\Models\Withdrawal;
use App\Modules\Functions;
use App\Modules\Game\Game;
use App\Models\UserBuyRole;
use App\Models\UserProduct;
use App\Models\UserWarning;
use App\Models\WarningType;
use App\Models\WarningWord;
use App\Models\CurrencyRate;
use App\Modules\Game\Currency;
use App\Models\UserAchievement;
use App\Models\Game as GameModel;
use App\Models\Image;
use App\Models\Newsletter;
use App\Models\Roulette;
use App\Models\RoulettesCell;
use App\Models\Task as TaskModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Modules\Game\GamerFunctions;
use App\Modules\Game\Products\Unwarn;
use App\Modules\Game\RouletteFuncs;
use App\Modules\Payments\FreeKassaSBP;
use App\Modules\Payments\TelegramStarsApi;

class AppBot extends BasicMafBot {
    //use Dialog;
    const SUBSCR_CHANNEL_ID = '-1002001676189';
    const SUBSCR_CHANNEL_URL = 'https://t.me/+SJB_XyawEtQ1NWIy';
    const OFIC_GROUP_ID = '-1002046415969';
    protected $userId;
    protected $hpApi = false;
    protected $qiwiApi = false;
    protected $yoomApi = false;
    protected $mainMenu = [];
     
    public function __construct(string $token,$botId=0, string $imgFolder="img/") {
        $this->updateLocale();
        parent::__construct($token,$botId,$imgFolder);
    }
    public function updateLocale() {
        $this->mainMenu = [
            ['👤 '.__('Профиль'),'🛒 '.__('Магазин'),'💰 '.__('Обменник')],
            ['💸 '.__('Пополнение'),'🏆 '.__('Достижения'),'🎲 '.__('Играть')],
            ['👁‍🗨 '.__('Моё инфо'),'⚙️ '.__('Настройки групп'),'💰 '.__('Балансы групп')]
        ];
    }
    public static $objAppBot = null;

    public static function appBot():self {        
        if(!self::$objAppBot) self::$objAppBot = new self(config('app.tg_token'));
        return self::$objAppBot;
    }

    protected function paramReplacer($messText, $params=null) {
        /*
        if(strpos($messText,'reflink')!==false) {
            $params['reflink'] = "http://t.me/garifinbot?start=".$this->userData($this->userId)['referal_link_id'];
            $params['partner_bal'] =floor($this->userData($this->userId)['bal_of_partners']);
            $params['ref_count'] =TelegramUser::where('referer_id',$this->userId)->count();
        }
        */
        return parent::paramReplacer($messText, $params);
    }
    //режим тест-ботов

    public function sendAnswer($ans_arr,$user_id) {
     //   Log::channel('daily')->info("Отправлено: $user_id <= ".json_encode($ans_arr,JSON_UNESCAPED_UNICODE));
        $botUser = BotUser::where('id',$user_id)->first();
        if(!$botUser) {
            if($user_id < 0) return parent::sendAnswer($ans_arr,$user_id);

            if(isset($ans_arr[0]['inline_keyboard'])) {
                $commands = [];
                foreach($ans_arr[0]['inline_keyboard']['inline_keyboard'] as $row) {
                    foreach($row as $btn) {
                        $commands[] = $btn['callback_data'];
                    }
                }
                if($commands) {
                    $sel = random_int(0,count($commands)-1);
                    Log::info("Реакция: ".$commands[$sel]);
                    usleep(50000);
                    $new_ans_arr = $this->prepareAnswer($commands[$sel],$user_id);
                    $this->sendAnswer($new_ans_arr,$user_id);
                }
            }
          //  Log::channel('daily')->info("Получено: $user_id <= ".json_encode($ans_arr,JSON_UNESCAPED_UNICODE));
        }
        else return parent::sendAnswer($ans_arr,$user_id);
    }

    public function test() {
        $res['text'] = "<b>Покупка xxx</b>
        /nСтоимость 123 $ = 123 RUB";
        $back = '💸 Пополнение';
        $back = 'btnseloffer&1';
        /*
        $res['keyboard'] = [[
            ['text'=>"Продолжить", 'web_app'=>["url"=>route("payment.start",['offer'=>1])]]
        ]];
        */
        $res['inline_keyboard']['inline_keyboard'] =[[
            ['text'=>"Продолжить","web_app"=>["url"=>route("payment.start",['offer'=>1])]],
           // ['text'=>"<< Назад","calback_data"=>$back]
        ]];
       // $this->getApi()->sendMessage()
        $this->getApi()->sendRawMessage(['chat_id'=>'376753094', 'text'=>'Привет',
        'reply_markup'=>json_encode($res['inline_keyboard'])
        ]);

        //$this->sendAnswer([$res],'376753094');

    }
    public function myinfoMessage($chat_id) {
        $res['text'] = "<b>🔐 ".__('Купленные опции:')."</b>\n";
        //если есть купленные продукты
        $date = date('Y-m-d H:i:s');
        $pcFirst = UserProduct::with('product')->where('user_id',$chat_id)->whereNotNull('avail_finish_moment')->where('avail_finish_moment','>',$date);
        $productCollection = UserProduct::with('product')->where('user_id',$chat_id)->where('is_deactivate',0)->union($pcFirst)->get(); //->where('avail_finish_moment','>',$date)
        if($productCollection->count()) {
            $iter=1;
            foreach($productCollection as $uproduct) {
                if($uproduct->avail_finish_moment && $uproduct->avail_finish_moment<$date) continue;
                //$res['text'] .= "\n".($iter++).".<b>". $uproduct->product."</b>".($uproduct->avail_finish_moment ? " в группе <i>{$uproduct->group}</i>\n <b>активен до:</b> ".Functions::rusDateTime($uproduct->avail_finish_moment,3)."(МСК)" : "");
                
                
                if(!$uproduct->avail_finish_moment) {
                    $res['text'] .= "\n".__('project.buyed_option',['number'=>$iter++, 'name'=>$uproduct->product]);
                    $res['inline_keyboard']['inline_keyboard'][]=
                    [['text'=>"Активировать {$uproduct->product}","callback_data"=>"activateuprod&".$uproduct->id]];
                }
                else {
                    $res['text'] .= "\n".__('project.buyed_option_active',['number'=>$iter++, 'name'=>$uproduct->product,'finish_moment'=>Functions::rusDateTime($uproduct->avail_finish_moment,3)]);
                }
            }
        }
        else {
            $res['text'] .= "\n".__('У вас нет активированных опций.');
        }
        $res['text'] .= "\n\n<b>⛔️ ".__('Предупреждения:')."</b>\n\n";
        //предупреждения
        $uwarns = UserWarning::selectRaw("group_id, count(*) as cnt")->where('user_id',$chat_id)
        ->groupBy('group_id')->get();
        if($uwarns->count()>0) {
            $vtexts = [];
            foreach($uwarns as $uwarn) {
                $vtexts[] = ''.$uwarn->group.": ".$uwarn->cnt;
            }
            $res['text'] .=implode("\n",$vtexts);
        }
        else {
            $res['text'] .= "У вас нет предупреждений.";
        }
        return $res;
    }
    public function profileMessage($chat_id) {
        $user = BotUser::where('id',$chat_id)->first();
        $res['text'] = "<b>Профиль</b>
        \n📝ID: {$chat_id}\n👤{$user}
        \n💶 Виндбаксы: ".$user->getBalance(Currency::R_WINDBUCKS).
        "\n🪙 Виндкоины: ".$user->getBalance(Currency::R_WINDCOIN)."
        \n<b>Сезонные:</b>\n❄️Снежинки: ".$user->getBalance(Currency::S_WINTER).
        "\n🐰Пасхальный кролик: ".$user->getBalance(Currency::S_SPRING).
        "\n🌞Солнышко: ".$user->getBalance(Currency::S_SUMMER).
        "\n🎃Дух Хэллоуина: ".$user->getBalance(Currency::S_AUTUMN);

        $bafs = Baf::all();
        $userbafs = UserBaf::where('user_id',$chat_id)->get();
        $ubafArr = [];
        foreach($userbafs as $ubaf) {
            $ubafArr[$ubaf->baf_id] = $ubaf;
        }
        $messtextarr = [];
        $bstatuses = ['❌','✅'];
        foreach($bafs as $baf) {
            $ubaf = $ubafArr[$baf->id] ?? null;
            $amount = $ubaf ? $ubaf->amount : 0;
            if(!$amount) $status = "";
            else if($ubaf->is_activate) $status = $bstatuses[1];
            else $status = $bstatuses[0];
            if($amount) {
                $res['inline_keyboard']['inline_keyboard'][]=
                [['text'=>"Изменить {$baf->name}","callback_data"=>"changeubaf&".$ubaf->id]];
            }
            $messtextarr[] = $baf->name.': '.$amount." $status";
        }
        $res['text'] .= "\n\n".implode("\n",$messtextarr);
        $winCollection = GameUser::where('user_id',$chat_id)->where('is_active',2)->get();
        $gameCollection = GameUser::where('user_id',$chat_id)->get();

        $res['text'] .= "\n\nПобед: ".$winCollection->count();
        $res['text'] .= "\nВсего игр: ".$gameCollection->count();


        //роль в следующей игре
        $uRole = UserBuyRole::where('user_id',$chat_id)->whereNull('game_id')->orderByDesc('id')->first();
        if($uRole) {
            $res['text'] .= "\n<b>Роль в следующей игре: </b>".$uRole->role;
        }
        return $res;
    }
    public function settingModelSwitchMessage($messTitle,$group_id, $setkey, $class, $descr, $inline_cmd)  //изменение настроек ролей и бафов
    {
        $bstatuses = ['❌','✅'];
        $res['text'] = "<b>$messTitle</b>\n\n";
        $set_models = Setting::groupSettingValue($group_id,$setkey);
        if($set_models !== 'all') $set_models = json_decode($set_models,true);
        $dbModels = $class::all();
        $index = 1;
        $messArr = [];
        foreach($dbModels as $model) {
            $messArr[] = $index.'. '.$model.': '.$bstatuses[$model->switchStatus($set_models)];
            $index++;
        }
        $res['text'] .= implode("\n",$messArr);
        $res['text'] .= "\n\n<i>$descr</i>";
        $res['inline_keyboard'] = $this->inlineKeyboard($dbModels,2,$inline_cmd."&".$group_id."&",false,'id','name');
        $res['inline_keyboard']['inline_keyboard'][] = [['text'=>"<< Назад", 'callback_data'=>"changegrpset&".$group_id]];
        return $res;
    }
    public function editSettingSwitchBFRLMessage($command,$chat_id,$callback_id,$setkey,$messTitle,$descr,$callback_cmd,$class ) {
        $cmd_arr = explode('&',$command);
        $group_id = $cmd_arr[1];
        $role_id =  $cmd_arr[2];
        $set_models = Setting::groupSettingValue($group_id,$setkey);
        if($set_models === 'all') $set_models = [];
        else $set_models = json_decode($set_models,true);

        $set_models[$role_id] = $set_models[$role_id] ?? 1;
        $set_models[$role_id] = $set_models[$role_id] ? 0 : 1;
        Setting::changeGroupSettingValue($group_id,$setkey,json_encode($set_models));
        $message = $this->settingModelSwitchMessage($messTitle,$group_id,$setkey,$class,
            $descr,$callback_cmd);

        $grpmess['chat_id'] = $chat_id;
        $grpmess['message_id'] = $callback_id;
        $grpmess['parse_mode'] = 'HTML';
        $grpmess['text'] = $message['text'];
        $grpmess['reply_markup'] = json_encode($message['inline_keyboard']);
        try {
            $this->getApi()->editMessageText($grpmess);
        }
        catch(Exception $e) {
            Log::error("Не удалось отредактировать сообщение с269: ".$e->getMessage());
        }
    }
    public function offerKeyboard($parent_id=0, $month=null, $backcmd = null) {
        $offers = Offer::where('parent_id', $parent_id)->get();
        $btnOffers = [];
        $month = $month ?? date('m');
        foreach($offers as $offer) {
         //   if($offer->price > 0 && $offer->price < 5) continue;
            if($offer->where_access === 'always') {
                $of = ['id'=>$offer->id,'btnname'=>$offer->btnname];
                if($backcmd) $of['id'] .= "&".$backcmd;
                $btnOffers[] = $of;
            }
            else {
                $access = explode(',',$offer->where_access);
                if(in_array($month,$access)) {
                    $of = ['id'=>$offer->id,'btnname'=>$offer->btnname];
                    if($backcmd) $of['id'] .= "&".$backcmd;
                    $btnOffers[] = $of;
                }
            }
        }
        return $this->inlineKeyboard($btnOffers,3,"btnseloffer&",true,'id','btnname');
    }
    public function prepareAnswer($command,$chat_id,$callback_id=false,$hookUpdate=[]) {

        // Log::info('PrepareAnswer', [
        //     'command'       => print_r($command, true),
        //     'group_id'      => $chat_id,
        //     '$callback_id'  => print_r($callback_id, true),
        //     'hookUpdate'    => print_r($hookUpdate, true)
        // ]);
        $res = [];
        $prefix = explode('_', $command);
        
        //защита от двойного клика во время игры
        if(MafiaEvent::ifDoubleClick([$prefix[0] == 'prezident' ? $command : $prefix[0],$chat_id])) {
            return [];
        }

        if(isset($hookUpdate['callback_query']) && $callback_id && strpos($command,'changeubaf')===false
        && strpos($command,'changesetr1role')===false && strpos($command,'changesetr1bafs')===false
        && strpos($command,'roultcellsell')===false) {
            try {
                $this->getApi()->deleteMessage(['chat_id'=>$chat_id,'message_id'=>$callback_id]);
            }
            catch(Exception $e) {}
        }
        else {  //это гарантирует, что реакция с кнопки не будет предсмертным сообщением
            $command = $this->getCmd($command,$chat_id);
        }

        if($callback_id && $callback_id > 0)
            $this->setUserMessageIdIfGameIsActive($chat_id, $callback_id);

        /* убрали проверку подписки на канал
        if(strpos($command,'/start')===false) { //если это не старт
            //тест подписки на канал
            try {
                $chm = $this->getApi()->getChatMember(['chat_id'=>self::SUBSCR_CHANNEL_ID,'user_id'=>$chat_id]);
                if($chm->status === 'left') {
                    $res['text'] = "Для продолжения пользования ботом подпишитесь на наш официальный канал ".self::SUBSCR_CHANNEL_URL;
                    return [$res];
                }
            }
            catch(Exception $e) { 
                Log::error("Не удалось проверить участника $chat_id на наличие в канале");
            }
        } */

        if(strpos($command, 'krasotkavisit')!==false) {
            $subcommand = explode('_', $command);
            //красотка может посещать одного и того же игрока за игру один раз
            if(GamerParam::where('param_name', 'krasotka_select')
                ->where('param_value', $subcommand[1])
                ->first()) {
                //$res['text'] = '💃Красотка может приходить только один раз к одному и тому же игроку за игру...';
                return [];
            }
        }

        if($command === '/roulette') {
            $img = Image::where('id',1)->first();
            $res[$img->media_type] = $img->file_id;
            $res['not_file'] = 1;
            //пытаемся списать деньги
            $user = BotUser::where('id',$chat_id)->first();
            if(!$user) return [['text'=>"Ошибка. Пользователь не найден"]];
            $cur = Currency::seasonCurOfMonth();
            $isCur = $user->decBalance($cur, 1);
            if($isCur) {
                $res['text'] = "<b>Вероятность выигрыша</b>
1 коин: 60%

1 сезонная валюта: 30%

200 винд баксов: 90%

Таргет на 2 ночи: 10%

Любой из бафов (1): 70%

Куш - 25 тыкв: 1%

Куш- 50 коинов: 1% \n\nЗа 12-ю сундуками скрывается выигрыш. Найдите эти сундуки";
                $roulette = Roulette::generateRoulette($chat_id);
                $res['inline_keyboard'] = $this->inlineKeyboard($roulette->cells,5,'roultcellsell&'.$roulette->id."&",
            false,'id','caption');                
            }
            else {
                $curNames = Currency::allCurrencies();
                $res['text'] = "Для запуска лотерии на вашем балансе должно быть 1".$curNames[$cur];
                $offer = Offer::where('parent_id', 0)->where('product',$cur)->first();
                if($offer) {
                    $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'Пополнить баланс', 'callback_data'=>'btnseloffer&'.$offer->id."&buyothers"]];
                }
            }
            return [$res];
        }
        if($command === 'closerulete') {
            $res['text'] = "Спасибо за игру.
            \nВы можете начать новую игру в любой момент, отправив команду /roulette";
            return [$res];
        }
        if(strpos($command,'roultcellsell&')!==false) {
            $cmd_arr = explode('&',$command);
            //что мы открыли----------------------------------------
            $cell = RoulettesCell::where('id',$cmd_arr[2])->first();
            if($cell->is_open) { //игнорим нажатие. ячейка уже открыта
                return [];
            }
            $cell->is_open = 1;
            $cell->save();
            //------------------------------------------------------
            $roulette = Roulette::where('id',$cmd_arr[1])->first();
            $arr = array_column($roulette->cells->all(),'is_open');
            $cost = array_sum($arr);
            $newCost = $cost + 1;
            $cur = Currency::seasonCurOfMonth();
            $curNames = Currency::allCurrencies();
            $user = BotUser::where('id',$chat_id)->first();
            if($cost > 1) {                
                if(!$user->decBalance($cur, $cost)) {
                    $res['text'] = "Для продолжения игры на вашем балансе должно быть $cost".$curNames[$cur];
                    $cell->is_open = 0;
                    $cell->save(); //закрываем обратно
                    $roulette = Roulette::where('id',$cmd_arr[1])->first(); //получим объект заново чтоб не засветить ячейку
                    $offer = Offer::where('parent_id', 0)->where('product',$cur)->first();
                    if($offer) {
                        $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'Пополнить баланс', 'callback_data'=>'btnseloffer&'.$offer->id."&buyothers"]];
                    }
                    return [$res];
                }
            }
            
            if($cell->prize_id) {
                $res['caption'] = "🔥Поздравляем! Вы выиграли {$cell->prize}.";
                $func = $cell->prize->add_function;
                RouletteFuncs::$func($user);
            }
            else {
                $res['caption'] = "В этом сундуке к сожалению ничего нет.";
            }
            
            $res['caption'] .= "\n\nВы можете закончить на этом игру, или открыть еще один сундук за $newCost".$curNames[$cur];
            $ikb = $this->inlineKeyboard($roulette->cells,5,'roultcellsell&'.$roulette->id."&",
            false,'id','caption');
            $ikb['inline_keyboard'][]=[$this->inlBtn("Закрыть рулетку","closerulete")];
            $res['reply_markup'] = json_encode($ikb);
            $res['chat_id'] = $chat_id;
            $res['message_id'] = $callback_id;
            try {
                $this->getApi()->editMessageCaption($res);
            }   
            catch(Exception $e) {
                Log::error("roultcellsell ... ".$e->getMessage());
            }
            return [];
        }
        if($command === "create newsletter" && in_array($chat_id,$this->admins)) {
            $res['text'] = "Вы создаете рассылку. Отправьте сообщение в том виде, как оно должно быть разослано пользователям. Соответственно сразу вставьте в него медиа и отформатированный текст";
            $this->addCmd("addnewsletter&",$chat_id);
            return [$res];
        }
        if(strpos($command,"addnewsletter&")!==false) {            
            $mess = [              
                'from_chat_id'=>$chat_id,  
                'message_id' =>$callback_id
            ];
            $nlet = Newsletter::create(['message'=>json_encode($mess)]);
            $mess['chat_id'] = $chat_id;            
            $this->getApi()->copyMessage($mess);
            $res['text'] = "Рассылка добавлена. Вот ☝️ как ее увидет пользователь. Что хотите сделать дальше?";
            $res['inline_keyboard']['inline_keyboard'] = [[$this->inlBtn("Запустить","sendnewl&".$nlet->id)], [$this->inlBtn("Пересоздать","create newsletter")]];
            return [$res];
        }
        if($command === '/addresource' && in_array($chat_id,$this->admins) ) {
            $res['text'] = "Отправьте ресурс...";
            $this->addCmd("sendingres&",$chat_id);
            return [$res];
        }
        if(strpos($command,"sendingres&")!==false) {
            $mess = $hookUpdate['message'];
            $file_id = null;
            $type = null;
            if(isset($mess['photo'])) {
                $photo = array_pop($mess['photo']);
                $file_id = $photo['file_id'];
                $type = 'photo';
            }
            if(isset($mess['video'])) {
                $file_id = $mess['video']['file_id'];
                $type = 'video';
            }
            if($file_id) {
                $img = Image::create(['file_id'=>$file_id,'media_type'=>$type]);
                $res['text'] = "Ресурс добавлен. ID = ".$img->id;                
            }
            else {
                $res['text'] = "Не удалось добавить ресурс";
            }
            return [$res];
        }
        if(strpos($command,"sendnewl&")!==false) {
            $cmd_arr = explode("&",$command);
            $nlet = Newsletter::where('id',$cmd_arr[1])->first();
            if($nlet) {
                $nlet->status = 1;
                $nlet->save();
            }
            $res['text'] = "Рассылка запущена";
            return [$res];
        }
        if($this->clearCommand($command) === 'Профиль' || $this->clearCommand($command) === 'Profile') {
            //обновим имена
            $user = BotUser::where('id',$chat_id)->first();
            $updates = $hookUpdate;
            $params = [
                'nick_name'=>empty($updates["message"]["from"]["username"]) ? null : $updates["message"]["from"]["username"],
                'first_name'=>$updates['message']['from']['first_name'] ?? null,
                'last_name'=>$updates['message']['from']['last_name']  ?? null,
            ];
            $user->fill($params);
            $user->save();

            $res = $this->profileMessage($chat_id);
            return [$res];
        }
        if($this->clearCommand($command) === 'Моё инфо' || $this->clearCommand($command) === 'My info') {
            $res = $this->myinfoMessage($chat_id);
            return [$res];
        }
        if($this->clearCommand($command) === 'Балансы групп' || $this->clearCommand($command) === 'Balance group' || $this->clearCommand($command) === 'Group balance') {
            $groups = BotGroup::where('who_add',$chat_id)->get();            
            if($groups->count()) {                
                $rows = [];
                $sum = 0;
                foreach($groups as $grp) {
                    $rows[] = "$grp : {$grp->balance}🪙";
                    $sum += $grp->balance;
                }
                $res['text'] = "<b>Балансы ваших групп:</b>\n\n".implode("\n",$rows);
                $res['text'] .="\n\n<b>Итого: {$sum}🪙</b>";
                $res['inline_keyboard']['inline_keyboard'] = [
                    [$this->inlBtn('Вывести на баланс бота',"grpbalancetob")],
                    [$this->inlBtn('Вывести на счет',"grpbalanceout")]
                ];
            }
            else {
                $res['text'] = "Вы пока не добавили ни одну группу";
            }
            return [$res];
        }
        if($command === 'grpbalancetob') {
            $balance = BotGroup::userGroupsBalance($chat_id);
            $res['text'] = "<b>Вывод на баланс бота</b>
            \nДоступно для вывода $balance 🪙
            \nСколько вы хотите вывести?";
            $sums = [5,10,15,20,25,50,100,200,500,750,1000];
            $preBtns = [];
            for($si = 0;$si < count($sums); $si++) {
                if($sums[$si] > $balance) break;
                $preBtns[] = [$sums[$si] => $sums[$si]."🪙"];
            }
            $res['inline_keyboard'] = $this->inlineKeyboard($preBtns,3,"seloutblntob&");
            $res['inline_keyboard']['inline_keyboard'][] = [$this->inlBtn("Всю сумму","seloutblntob&all")];
            return [$res];
        }
        if($command === 'grpbalanceout') {
            $balance = BotGroup::userGroupsBalance($chat_id);
            $res['text'] = "<b>Вывод на счет в FKWallet</b>
            \nДоступно для вывода $balance 🪙. Курс вывода 100🪙 = 9$. Минимальная сумма для вывода на внешний счет 200🪙";
            if($balance < 200) $res['text'] .= "\n<b><i>У вас недостаточный баланс для вывода</i></b>";
            else {
                $res['text'] .= "\nСколько вы хотите вывести?";
                $sums = [200,500,750,1000,1500,2000];
                $preBtns = [];
                for($si = 0;$si < count($sums); $si++) {
                    if($sums[$si] > $balance) break;
                    $preBtns[] = [$sums[$si] => $sums[$si]."🪙"];
                }
                $res['inline_keyboard'] = $this->inlineKeyboard($preBtns,3,"seloutblnout&");
                $res['inline_keyboard']['inline_keyboard'][] = [$this->inlBtn("Всю сумму","seloutblnout&all")];
            }
            return [$res];
        }
        if(strpos($command,'seloutblntob&')!==false) {
            $cmd_arr = explode('&',$command);
            $groupsBalance = BotGroup::userGroupsBalance($chat_id);
            if($cmd_arr[1] == 'all') { //вывод всей суммы
                $balance = $groupsBalance;                     
            }
            else if(is_numeric($cmd_arr[1])) {
                $balance = (int) $cmd_arr[1];
            }
            else $balance = 0;
            //списываем с баланса групп - зачисляем на баланс пользователя
            if($balance > $groupsBalance) {
                $res['text'] = "Сумма $balance 🪙 не может быть выведена. На балансе групп всего $groupsBalance 🪙";
            }
            else if($balance > 0) {
                $infoBalances = [];
                if($balance == $groupsBalance) $infoBalances = BotGroup::clearBalances($chat_id);
                else {
                    $sum = 0;
                    $groups = BotGroup::where('who_add',$chat_id)->get();
                    foreach($groups as $grp) {
                        $sum += $grp->balance;
                        if($grp->balance > 0) $infoBalances[$grp->id] = $grp->balance;
                        if($sum >= $balance) {
                            $delta = $sum - $balance;
                            $grp->balance = $delta;
                            $infoBalances[$grp->id] -= $delta;
                            $grp->save();    
                            break;
                        }
                        $grp->balance = 0;
                        $grp->save();
                    }
                }
                Withdrawal::create(['user_id'=>$chat_id,'groups'=>json_encode($infoBalances),'amount'=>$balance,'way'=>1,'status'=>1]);
                $user = BotUser::where('id',$chat_id)->first();
                $user->addBalance(Currency::R_WINDCOIN,$balance);
                $res['text'] = "На ваш баланс зачислено $balance 🪙";
            }
            else {
                $res['text'] = "Нечего выводить";
            }
            return [$res];
        }
        if(strpos($command,'seloutblnout&')!==false) {
            $cmd_arr = explode('&',$command);
            $groupsBalance = BotGroup::userGroupsBalance($chat_id);
            if($cmd_arr[1] == 'all') { //вывод всей суммы
                $balance = $groupsBalance;                     
            }
            else if(is_numeric($cmd_arr[1])) {
                $balance = (int) $cmd_arr[1];
            }
            else $balance = 0;
            //списываем с баланса групп - зачисляем на баланс пользователя
            if($balance < 200) {
                $res['text'] = "Недостаточная сумма для вывода. Минимальный вывод 200🪙";
            }
            if($balance > $groupsBalance) {
                $res['text'] = "Сумма $balance 🪙 не может быть выведена. На балансе групп всего $groupsBalance 🪙";
            }
            else {
                $infoBalances = [];
                if($balance == $groupsBalance) $infoBalances = BotGroup::clearBalances($chat_id);
                else {
                    $sum = 0;
                    $groups = BotGroup::where('who_add',$chat_id)->get();
                    foreach($groups as $grp) {
                        $sum += $grp->balance;
                        if($grp->balance > 0) $infoBalances[$grp->id] = $grp->balance;
                        if($sum >= $balance) {
                            $delta = $sum - $balance;
                            $grp->balance = $delta;
                            $infoBalances[$grp->id] -= $delta;
                            $grp->save();    
                            break;
                        }
                        $grp->balance = 0;
                        $grp->save();
                    }
                }
                $wzdr = Withdrawal::create(['user_id'=>$chat_id,'groups'=>json_encode($infoBalances),'amount'=>$balance,'way'=>2,'status'=>0]);
                $sumUsd = number_format(round($balance * 0.09, 2),2,',',' ');

                $res['text'] = "Заявка на вывод создана. Номер заявки #{$wzdr->id}.\nСумма $balance 🪙 = $sumUsd $
                \nВы получите уведомление, когда вывод будет выполнен";
                $adm['text'] = "Заявка на вывод #{$wzdr->id}\nПользователь: ".Game::userUrlName(BotUser::where('id',$chat_id)->first()).
                "\nСумма заявки: $balance 🪙 = $sumUsd $";
                $adm['inline_keyboard']['inline_keyboard'] = [
                    [$this->inlBtn('Подтвердить вывод','seloutblnoutaccept&'.$wzdr->id)],
                    [$this->inlBtn('Отклонить вывод','seloutblnoutcancel&'.$wzdr->id)]
                ];
                foreach($this->admins as $admId) {
                    $this->sendAnswer([$adm],$admId);
                }
            }            
            return [$res];
        }
        if(strpos($command,'seloutblnoutaccept&')!==false) {
            $cmd_arr = explode('&',$command);
            $wzdr = Withdrawal::where('id',$cmd_arr[1])->where('status',0)->first();
            if($wzdr) {
                $wzdr->status = 1;
                $wzdr->save();
                $res['text'] = "Вы успешно подтвердили заявку {$wzdr->id}";
                $usermess['text'] = "<b>Ваша заявка #{$wzdr->id} успешно исполнена</b>";
                $this->sendAnswer([$usermess],$wzdr->user_id);
            }
            else {
                $res['text'] = "Заявка уже обработана";
            }
            return [$res];
        }
        if(strpos($command,'seloutblnoutcancel&')!==false) {
            $cmd_arr = explode('&',$command);
            $wzdr = Withdrawal::where('id',$cmd_arr[1])->where('status',0)->first();
            if($wzdr) {
                $wzdr->status = 2;
                $wzdr->save();
                $res['text'] = "Вы отклонили заявку {$wzdr->id}";
                $usermess['text'] = "<b>Ваша заявка #{$wzdr->id} отклонена</b>. По каким-то причинам ее не удалось выполнить. Напишите в поддержку для уточнения деталей. При обращении в поддержку указывайте номер заявки";
                $this->sendAnswer([$usermess],$wzdr->user_id);
            }
            else {
                $res['text'] = "Заявка уже обработана";
            }
            return [$res];
        }
        if($this->clearCommand($command) === 'Пополнение' || $this->clearCommand($command) === 'Replenishment') {
            $res['text'] = "Выберите валюту, которую хотите купить";
            $res['inline_keyboard'] = $this->offerKeyboard(0,null,'💸 Пополнение');
            return [$res];
        }
        if(strpos($command,'buyoffer&')!==false) {
            $cmd_arr = explode('&',$command);
            $offer = Offer::where('id',$cmd_arr[1])->first();
            if(!$offer) return [];
            if($cmd_arr[2] == 'tlgstars') {
                $tlgStarsApi = new TelegramStarsApi;
                $tlgStarsApi->createOrder($chat_id,TelegramStarsApi::usdToXTR($offer->price),$offer->id);
                return [];
            }
            if($cmd_arr[2] == 'freekassa') {
                $botUser = BotUser::where('id',$chat_id)->first();
                $res['text'] = "<b>Покупка ".$offer->name."</b>\nСтоимость {$offer->price} $\n<b>Выбранный метод:</b> Оплата СБП
                \n<i>Для продолжения, нажмите кнопку под этим сообщением. Если вдруг контент открывшегося окна долго не отображается, попробуйте использовать VPN</i>";

                /*                
                $rubPrice = CurrencyRate::calcCurrencySum('USD',$offer->price,'RUB');
                */
                $fk = new FreeKassaSBP();
                $fkOrder = $fk->createOrder($botUser->id,$offer->price * 1.25,$offer->id,'USD');
                /*
                $res['inline_keyboard']['inline_keyboard'] =[[
                    ['text'=>"Продолжить","web_app"=>["url"=>route("payment.start",['offer'=>$offer->id])]],
                ]];*/
                $res['inline_keyboard']['inline_keyboard'] =[[
                    ['text'=>"Продолжить","web_app"=>["url"=>$fkOrder['location'] ]],
                ]];
                $res['saver'] = new MessageResultSaver($botUser);
                return [$res];
            }
            return [];
        }
        if(strpos($command,'btnseloffer&')!==false) {
            $cmd_arr = explode('&',$command);
            $offer = Offer::where('id',$cmd_arr[1])->first();
            if(!$offer) return [];
            $res = [];
            if($offer->price > 0) {
                $botUser = BotUser::where('id',$chat_id)->first();
                $res['text'] = "<b>Покупка ".$offer->name."</b>
                \nСтоимость {$offer->price} $
                \n<i>Выберите метод оплаты</i>"; //.CurrencyRate::calcCurrencySum('USD',$offer->price,'RUB')." RUB";
                $res['inline_keyboard']['inline_keyboard'] =[[
                    ['text'=>"🌟 Telegram Stars","callback_data"=>"buyoffer&".$cmd_arr[1]."&tlgstars"],
                ]];
                //if($offer->price >= 5) {
                    $res['inline_keyboard']['inline_keyboard'][] = [['text'=>"💳 Оплата СБП","callback_data"=>"buyoffer&".$cmd_arr[1]."&freekassa"]];
                //}
                if(!empty($cmd_arr[2])) {
                    array_shift($cmd_arr); array_shift($cmd_arr);
                    $nazadCmd = implode("&",$cmd_arr);
                    $res['inline_keyboard']['inline_keyboard'][] = [['text'=>"<< Назад",'callback_data'=>$nazadCmd]];
                }
                /*
                $res['inline_keyboard']['inline_keyboard'] =[[
                    ['text'=>"Продолжить","web_app"=>["url"=>route("payment.start",['offer'=>$offer->id])]],
                ]];
                $res['saver'] = new MessageResultSaver($botUser);
                */
            }
            else {
                $res['text'] = $offer->name;
                $res['inline_keyboard'] = $this->offerKeyboard($offer->id,null,$command);
                if(!empty($cmd_arr[2])) {
                    array_shift($cmd_arr); array_shift($cmd_arr);
                    $nazadCmd = implode("&",$cmd_arr);
                    $res['inline_keyboard']['inline_keyboard'][] = [['text'=>"<< Назад",'callback_data'=>$nazadCmd]];
                }
            }
            return [$res];
        }
        if($command === '/add_warning_words') {
            $groups = BotGroup::where('who_add',$chat_id)->get();
            if($groups->count()>0) {
                $res['text'] = "Для какой группы вы хотите добавить запрещенные слова?";
                $res['inline_keyboard'] = $this->inlineKeyboard($groups,1,"addwarnselgrp&",false,'id','title');
            }
            else {
                $res['text'] = "У вас нет ни одной группы";
            }
            return [$res];
        }
        if(strpos($command, "produnwarn&")!==false) {
            $cmd_arr = explode('&',$command);
            $uprod = UserProduct::where('id',$cmd_arr[1])->first();
            if($uprod && !$uprod->was_used) {
                $unwarn = new Unwarn($uprod);
                $res = $unwarn->activate(['group_id'=>$cmd_arr[2]]);
            }
            else {
                $res['text'] = "Анварн уже был использован";
            }
            return [$res];
        }
        if($this->clearCommand($command) === 'Настройки групп' || $this->clearCommand($command) === 'Group settings' || $this->clearCommand($command) === 'Settings group') {
            $userGroups = BotGroup::where('who_add',$chat_id)->get();
            if($userGroups->count() < 1) {
                $res['text'] = "Нет игровых групп, владельцем которых вы являетесь. Сначала добавьте меня в группу и сделайте админом";
            }
            else {
                $res['text'] = "Выберите группу, настройки которой нужно изменить";
                $ugList = [];
                foreach($userGroups as $ug) {
                    try {
                        $chatMember = $this->getApi()->getChatMember(['chat_id'=>$ug->id,'user_id'=>$chat_id]);
                    }
                    catch(Exception $e) {
                        $chatMember = null;                       
                    }
                    if($chatMember && in_array($chatMember->status,['administrator','creator'])) {
                        $ugList[] = $ug;                        
                    }
                    else {
                        $ug->who_add = null;
                        $ug->save();
                    }
                }
                if(!$ugList) {
                    $res['text'] = "Нет игровых групп, владельцем которых вы являетесь. Сначала добавьте меня в группу и сделайте админом";
                }
                else $res['inline_keyboard'] = $this->inlineKeyboard($ugList,1,"changegrpset&",false,'id','title');
            }

            return [$res];
        }
        if(strpos($command,'grpwidetarif&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $res['text'] = "<b>Группа $grp</b>
            \n<b>Текущий тариф:</b> {$grp->tarif}";
            $btns = [];
            if($grp->tarif_id > 1) {
                $res['text'] .= "\n<b>Действует до:</b> ".Functions::rusDate($grp->tarif_expired);
                $btns[] = [$this->inlBtn('Продлить тариф',"grptarprolong&{$grp->id}")];
            }
            if($grp->tarif_id < 3) {
                $btns[] = [$this->inlBtn('Расширить тариф',"grptarwide&{$grp->id}")];
            }
            $res['inline_keyboard']['inline_keyboard'] = $btns;
            return [$res];
        }
        if(strpos($command,'grptarwide&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $res['text'] = "<b>Группа $grp</b>
            \n<b>Текущий тариф:</b> {$grp->tarif}
            \n<b>Стоимости тарифов:</b>";
            $tarifs = GroupTarif::where('id','>',1)->get();            
            foreach($tarifs as $ctarif) {
                $res['text'].="\n 🟢 {$ctarif} - {$ctarif->price}🪙";
            }
            $res['text'].="\n\nВыберите тариф, на который хотите перейти";
            $ltarifs = GroupTarif::where('id','>',$grp->tarif_id)->get();
            $res['inline_keyboard'] = $this->inlineKeyboard($ltarifs,1,"newgrptarif&{$grp->id}&",false,'id','name');
            return [$res];
        }
        if(strpos($command,'newgrptarif&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $tarif = GroupTarif::where('id',$cmd_arr[2])->first();
            $user = BotUser::where('id',$chat_id)->first();
            $res = [];
            if($user->decBalance(Currency::R_WINDCOIN,$tarif->price)) {
                $grp->tarif_id = $tarif->id;
                $grp->reward = $tarif->reward;
                $grp->save();
                $res['text'] = "Вы успешно перешли на тариф <b>$tarif</b>";
            }
            else {
                $neHvataet = $tarif->price - $user->getBalance(Currency::R_WINDCOIN);
                $res['text'] = "Недостаточно 🪙 на балансе, не хватает $neHvataet 🪙 для продления тарифа.
                \nПополните баланс и попробуйте снова";
                $res['inline_keyboard'] = $this->offerKeyboard(1,null,$command);
            }
            return [$res];
        }
        if(strpos($command,'grptarprolong&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $res['text'] = "Продление тарифа <b>{$grp->tarif}</b> для группы <b>{$grp}</b> на 1 месяц
            \nСтоимость: {$grp->tarif->price}🪙";
            $res['inline_keyboard']['inline_keyboard'] = [[$this->inlBtn("Продлить","grptarprolnaccept&{$grp->id}")]];
            return [$res];
        }
        if(strpos($command,'grptarprolnaccept&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $user = BotUser::where('id',$chat_id)->first();
            $res = [];
            if($user->decBalance(Currency::R_WINDCOIN,$grp->tarif->price)) {
                $time = strtotime("+1 month",strtotime($grp->tarif_expired));
                $grp->tarif_expired = date('Y-m-d',$time);
                $grp->save();
                $res['text'] = "Тариф успешно продлен до ".Functions::rusDate($grp->tarif_expired);
            }
            else {
                $neHvataet = $grp->tarif->price - $user->getBalance(Currency::R_WINDCOIN);
                $res['text'] = "Недостаточно 🪙 на балансе, не хватает $neHvataet 🪙 для продления тарифа.
                \nПополните баланс и попробуйте снова";
                $res['inline_keyboard'] = $this->offerKeyboard(1,null,$command);
            }
            return [$res];
        }

        if(strpos($command,'changegrpset&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $res['text'] = "Вы настраиваете группу <b>{$grp->title}</b>\nТариф группы: <b>{$grp->tarif}</b>
            \nЧто вы хотели бы изменить?";
            $setObject = Setting::groupSettings($grp->id);
            $res['inline_keyboard'] = $this->inlineKeyboard($setObject['modifSets'],1,"changegr1st&{$grp->id}&",false,'set_key','title_value');
            if($grp->tarif_id < 3) {
                array_unshift($res['inline_keyboard']['inline_keyboard'],[['text'=>'Расширить тариф','callback_data'=>"grpwidetarif&".$grp->id]]);
            }

            Log::info('Command changegrpset&', ['command' => $command]);

            return [$res];
        }
        if(strpos($command,'changegr1st&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $setKey = $cmd_arr[2];
            $bstatuses = ['❌','✅'];
            switch($setKey) {
                case 'roles':
                    $res = $this->settingModelSwitchMessage("Настройка ролей",$grp->id,'roles',GameRole::class,
                "Для изменения состояния ролей используйте кнопки под этим сообщением",'changesetr1role');
                    break;
                case 'bafs':
                    $res = $this->settingModelSwitchMessage("Настройка бафов",$grp->id,'bafs',Baf::class,
                "Для изменения состояния бафов используйте кнопки под этим сообщением",'changesetr1bafs');
                    break;
                default:
                    $settingX = Setting::groupSetting($grp->id,$setKey);
                    $setting = $settingX['group'] ? $settingX['group'] : $settingX['base'];
                    $res['text'] = "Вы редактируете <b>{$setting->title}</b>
                    \n<b>Текущее значение: </b>".$setting->set_value;
                    if(empty($setting->variants)) {
                        $res['text'] .= "\n\nОтправьте новое значение: ";
                        $this->addCmd("changegr1stsend&{$grp->id}&{$cmd_arr[2]}&",$chat_id);
                    }
                    else {
                        $res['text'] .= "\n\n Выберите новое значение: ";
                        if(strpos($setting->variants,'{')!==false) {
                            $variants = json_decode($setting->variants,true);
                            $res['inline_keyboard'] = $this->inlineKeyboard($variants,1,"changegr1stsend&{$grp->id}&{$cmd_arr[2]}&");
                        }
                        else {
                            $variants = explode(",",$setting->variants);
                            $avariants = [];
                            foreach($variants as $v) $avariants[$v] = $v;
                            $res['inline_keyboard'] = $this->inlineKeyboard($avariants,3,"changegr1stsend&{$grp->id}&{$cmd_arr[2]}&");
                        }
                    }
            }
            return [$res];
        }
        if(strpos($command,'changegr1stsend&')!==false) {
            $cmd_arr = explode('&',$command);
            $grp = BotGroup::where('id',$cmd_arr[1])->first();
            $setKey = $cmd_arr[2];
            $setVal = $cmd_arr[3];
            $changeSet = Setting::changeGroupSettingValue($grp->id,$setKey,$setVal);
            $res['text'] = "<b>Настройка:</b> {$changeSet->title}
            \n<b>Новое значение:</b> {$changeSet->set_value}";
            /*
            if($setKey === 'mediafiles') {
                $allow =  ($changeSet->set_value === 'yes') ? true : false;
                try {
                    $this->getApi()->setChatPermissions([
                        'chat_id'=>$grp->id,
                        'permissions'=>$this->mediaChatPermisions($allow)
                    ]);
                }
                catch(Exception $e) {
                    Log::error("mediafiles: ".$e->getMessage());
                }
            }
            */

            if($grp)
                $res['inline_keyboard']['inline_keyboard'][] = [['text' => '<< Назад к настройкам', 'callback_data'=>'changegrpset&'.$grp->id]];

            return [$res];
        }
        if(strpos($command,'changesetr1role&')!==false) {
            $this->editSettingSwitchBFRLMessage($command,$chat_id,$callback_id,'roles',"Настройка ролей",
                "Для изменения состояния ролей используйте кнопки под этим сообщением",'changesetr1role',GameRole::class);
            return [];
        }
        if(strpos($command,'changesetr1bafs&')!==false) {
            $this->editSettingSwitchBFRLMessage($command,$chat_id,$callback_id,'bafs',"Настройка бафов",
                "Для изменения состояния бафов используйте кнопки под этим сообщением",'changesetr1bafs',Baf::class);
            return [];
        }
        if(strpos($command,'prodanyactivate&')!==false) {
            $cmd_arr = explode('&',$command);
            $uprod = UserProduct::where('id',$cmd_arr[1])->first();
            if($uprod && !$uprod->was_used) {
                $class = "\\App\\Modules\\Game\\Products\\".$uprod->product->class;
                $unwarn = new $class($uprod);
                $res = $unwarn->activate(['group_id'=>$cmd_arr[2]]);
            }
            else {
                $res['text'] = ''.$uprod->product." уже был использован раннее";
            }
            return [$res];
        }
        if(strpos($command,'addwarnselgrp&')!==false) {
            $cmd_arr = explode('&',$command);
            $res['text'] = "Вставьте список запрещенных слов. Каждое слово или фраза с новой строки";
            $this->addCmd("addwarninputlist&".$cmd_arr[1]."&",$chat_id);
            return [$res];
        }
        if(strpos($command,'addwarninputlist&')!==false) {
            $cmd_arr = explode('&',$command);
            $group_id = $cmd_arr[1];
            $listwords = explode("\n",$cmd_arr[2]);
            foreach($listwords as $word) {
                WarningWord::firstOrCreate(['word'=>trim($word),'group_id'=>$group_id]);
            }
            $res['text'] = "Список запрещенных слов добавлен";
            return [$res];
        }
        if(strpos($command,'activateuprod&')!==false) {
            $cmd_arr = explode('&',$command);
            $uproduct = UserProduct::where('id',$cmd_arr[1])->first();
            if($uproduct) {
                $class = "\\App\\Modules\\Game\\Products\\".$uproduct->product->class;
                $prodManager = new $class($uproduct);
                $mess = $prodManager->message();
                if($mess) {
                    if(isset($mess['innercmd'])) {
                        $this->addCmd($mess['innercmd'].$cmd_arr[1]."&",$chat_id);
                    }
                    return [$mess];
                }
                else {
                    $prodManager->activate();
                    $res['text'] = ''.$uproduct->product." успешно активирован";
                    return [$res];
                }
            }
        }
        if(strpos($command,'inputprefix&')!==false) {
            $cmd_arr = explode('&',$command);
            $uproduct = UserProduct::where('id',$cmd_arr[1])->first();
            //в скольки группах состоишь
            $chmbs = ChatMember::with('group')->where('member_id',$chat_id)->get()->all();
            if(count($chmbs) == 0) {
                $res['text'] = "Вы не состоите в игровой группе. Активация невозможна";
            }
            /*
            else if(count($chmbs) == 1) {
                $params['prefix'] = $cmd_arr[2];
                $params['group_id'] = $chmbs[0]->group_id;
                $class = "\\App\\Modules\\Game\\Products\\".$uproduct->product->class;
                $prodManager = new $class($uproduct);
                $prodManager->activate($params);
                $res['text'] = "Вы активировали префикс {$cmd_arr[2]} для группы ".$chmbs[0]->group;
            }
            */
            else {
                $res['text'] = "Выберите группу, для которой нужно активировать префикс";
                $res['inline_keyboard'] = $this->inlineKeyboard($chmbs,1,"selprefixgroup&".$cmd_arr[1]."&".$cmd_arr[2]."&",false,'group_id','group');
            }
            return [$res];
        }
        if(strpos($command,'selprefixgroup&')!==false) {
            $cmd_arr = explode('&',$command);
            $uproduct = UserProduct::where('id',$cmd_arr[1])->first();
            $params['prefix'] = $cmd_arr[2];
            $params['group_id'] = $cmd_arr[3];
            $class = "\\App\\Modules\\Game\\Products\\".$uproduct->product->class;
            $prodManager = new $class($uproduct);
            $prodManager->activate($params);
            $group = BotGroup::where('id',$params['group_id'])->first();
            $res['text'] = "Вы активировали префикс {$cmd_arr[2]} для группы ".$group;
            return [$res];
        }
        if(strpos($command,'changeubaf&')!==false) {
            $cmd_arr = explode('&',$command);
            $ubaf = UserBaf::where('id',$cmd_arr[1])->first();
            if($ubaf) {
                $ubaf->is_activate = $ubaf->is_activate ? 0 : 1;
                $ubaf->save();
            }
            $grpmess['chat_id'] = $chat_id;
            $grpmess['message_id'] = $callback_id;
            $grpmess['parse_mode'] = 'HTML';
            $profmess = $this->profileMessage($chat_id);
            $grpmess['text'] = $profmess['text'];
            $grpmess['reply_markup'] = json_encode($profmess['inline_keyboard']);
            $this->getApi()->editMessageText($grpmess);
            return [];
        }
        if(strpos($command,'exchangecb_')!==false) {
            $cmd_arr = explode('_',$command);
            $coinsum = abs($cmd_arr[1]);
            $exchanger = [
                '1'=>100,
                '3'=>320,
                '5'=>550,
                '8'=>900,
                '10'=>1250,
                '20'=>2600,
                '50'=>7000
            ];
            $user = BotUser::where('id',$chat_id)->first();
            if(!isset($exchanger[$coinsum])) {
                $res['text'] = "Ошибка выбора суммы";
            }
            else if($user) {
                if($user->decBalance(Currency::R_WINDCOIN,$coinsum,false)) {
                    $user->addBalance(Currency::R_WINDBUCKS,$exchanger[$coinsum],true);
                    $res['text'] = "Вы поменяли $coinsum 🪙 на {$exchanger[$coinsum]} 💶
                    \nНа ваш баланс зачислено {$exchanger[$coinsum]} 💶";
                }
                else {
                    $res['text'] = "Недостаточно 🪙 на балансе. Выберите другую сумму обмена или пополните баланс.";
                }
            }
            else {
                $res['text'] = "Ошибка. пользователь не найден";
            }
            return [$res];
        }
        if($command === 'vigruzka') {
            $res['text'] = json_encode(Game::vigruzka(),JSON_UNESCAPED_UNICODE);
            return [$res];
        }
        if($command === 'buyothers') {
            $seasonCur = Currency::seasonCurOfMonth();
            $curSybmols = Currency::allCurrencies();
            $products = Product::whereIn('cur_code',[$seasonCur, Currency::R_WINDBUCKS,Currency::R_WINDCOIN])->get();
            $textarr = ["<b>Товары</b>"];
            foreach($products as $product) {
                $textarr[] = "<b>{$product->name} - <i>{$product->price}".$curSybmols[$product->cur_code]."</i></b>\n{$product->description}";
            }
            $res['text'] = implode("\n\n",$textarr);
            $res['inline_keyboard'] = $this->inlineKeyboard($products, 2,"buyselprod&",false,'id','name');
            $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'<< Назад','callback_data'=>'🛒 Магазин']];
            return [$res];
        }
        if(strpos($command,'buyselprod&')!==false) {
            $cmd_arr = explode('&',$command);
            $curNames = Currency::allCurrencies();
            //процесс покупки товара
            $product = Product::where('id',$cmd_arr[1])->first();
            $user = BotUser::where('id',$chat_id)->first();
            if($product && $user) {
                if($user->decBalance($product->cur_code, $product->price)) { //удалось списать с баланса нужную сумму
                    UserProduct::create(['user_id'=>$chat_id,'product_id'=>$product->id]);
                    $res['text'] = "Вы успешно купили <b>{$product->name}</b>. Вы можете найти и активировать его, нажав кнопку <b>👁‍🗨 Моё инфо</b>.";
                }
                else {
                    $res['text'] = "Недостаточный баланс ".$curNames[$product->cur_code]." для покупки выбранного товара.";
                    $offer = Offer::where('parent_id', 0)->where('product',$product->cur_code)->first();
                    if($offer) {
                        $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'Пополнить баланс', 'callback_data'=>'btnseloffer&'.$offer->id."&buyothers"]];
                    }
                }
                $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'<< Назад', 'callback_data'=>'buyothers']];
                return [$res];
            }
            $res['text'] = "Ошибочный запрос";
            return [$res];
        }
        if($command == 'buyroles') {
            $buyRoles = BuyRole::with('role')->orderBy('id')->get();
            $res['text'] = "Выберите роль. Она будет применена в следующей игре";
            $res['inline_keyboard'] = $this->inlineKeyboard($buyRoles,2,"buyselrole&",false,'id','role_with_price');
            $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'<< Назад','callback_data'=>'🛒 Магазин']];
            return [$res];
        }
        if(strpos($command,'buyselrole&')!==false) {
            $cmd_arr = explode('&',$command);
            $buyRole = BuyRole::where('id',$cmd_arr[1])->first();
            $user = BotUser::where('id',$chat_id)->first();
            $curNames = Currency::allCurrencies();
            if($buyRole && $user) {
                if($user->decBalance($buyRole->cur_code, $buyRole->price)) { //удалось списать с баланса нужную сумму
                    UserBuyRole::create(['user_id'=>$chat_id,'role_id'=>$buyRole->role_id]);
                    $res['text'] = "Вы успешно купили <b>{$buyRole->role}</b>";
                }
                else {
                    $res['text'] = "Недостаточный баланс {$curNames[$buyRole->cur_code]} для покупки выбранной роли.";
                    $offer = Offer::where('parent_id', 0)->where('product',$buyRole->cur_code)->first();
                    if($offer) {
                        $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'Пополнить баланс','callback_data'=>'btnseloffer&'.$offer->id."&buyroles"]];
                    }
                }
                $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'<< Назад','callback_data'=>'buyroles']];
                return [$res];
            }
            $res['text'] = "Ошибочный запрос";
            return [$res];
        }
        if($command == 'buybafs') {
            $bafs = Baf::all();
            $textarr = ["<b>Баффы</b>"];
            $curSybmols = Currency::allCurrencies();
            foreach($bafs as $baf) {
                $textarr[] = "<b>{$baf->name} - <i>{$baf->price}".$curSybmols[$baf->cur_code]."</i></b>\n{$baf->description}";
            }
            $res['text'] = implode("\n\n",$textarr);
            $res['inline_keyboard'] = $this->inlineKeyboard($bafs, 2,"buyselbaf&",false,'id','name');
            $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'<< Назад','callback_data'=>'🛒 Магазин']];
            return [$res];
        }
        if(strpos($command,'fakepas&')!==false) {
            $cmd_arr = explode('&',$command);
            $prmId = $cmd_arr[1];
            $roleId = $cmd_arr[2];
            $role = GameRole::where('id',$roleId)->first();
            $gmParam = GamerParam::where('id',$prmId)->first();
            $gmParam->param_value = $role->name;
            $gmParam->save();
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            if($gamer) GamerParam::saveParam($gamer, 'puaro_check_result',"Комиссар Пуаро взял у вас паспорт. Вы выбрали {$role->name}. Кажется, он ничего не заметил...");
            return [];
        }
        if(strpos($command,'buyselbaf&')!==false) {
            $cmd_arr = explode('&',$command);
            //процесс покупки бафа
            $baf = Baf::where('id',$cmd_arr[1])->first();
            $user = BotUser::where('id',$chat_id)->first();
            if($baf && $user) {
                if($user->decBalance($baf->cur_code, $baf->price)) { //удалось списать с баланса нужную сумму
                    $ubaf = UserBaf::where(['user_id'=>$chat_id,'baf_id'=>$baf->id])->first();
                    if($ubaf) {
                        $ubaf->amount = $ubaf->amount + 1;
                        $ubaf->save();
                    }
                    else {
                        UserBaf::create(['user_id'=>$chat_id,'baf_id'=>$baf->id, 'amount'=>1]);
                    }
                    $res['text'] = "Вы успешно купили <b>{$baf->name}</b>";
                }
                else {
                    $curNames = Currency::allCurrencies();
                    $res['text'] = "Недостаточный баланс {$curNames[$baf->cur_code]} для покупки выбранного баффа.";
                    $offer = Offer::where('parent_id', 0)->where('product',$baf->cur_code)->first();
                    if($offer) {
                        $res['inline_keyboard']['inline_keyboard'][] = [['text'=>'Пополнить баланс','callback_data'=>'btnseloffer&'.$offer->id."&buybafs"]];
                    }
                }
                $res['inline_keyboard']['inline_keyboard'][]=[['text'=>"<< Назад",'callback_data'=>'buybafs']];
                return [$res];
            }
            $res['text'] = "Ошибочный запрос";
            return [$res];
        }
        if($command == "viewroles" && $chat_id == "376753094") {
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            if($gamer) {
                $gamers = GameUser::where('game_id',$gamer->game_id)->where('is_active',1)->get();
                $info = [];
                foreach($gamers as $tgamer){
                    $info[] = ''.$tgamer->user." -  ".$tgamer->role;
                }
                $res['text'] = implode("\n",$info);
            }
            else $res['text'] = "Нет активной игры";
            return [$res];
        }
        if($command === 'nightactionempty') {
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            if($gamer) {
                GamerParam::saveParam($gamer,'nightactionempty',1);
                $res['text'] = "Вы пропустили ход";
                return [$res];
            }
            return [];
        }
        if(strpos($command,'lastword_')!==false) {
            $cmd_arr = explode('_',$command);
            $gamer = GameUser::where('id',$cmd_arr[1])->first();
            $die_word_long = Setting::groupSettingValue($gamer->game->group_id,'die_word_long');
            $lastwordTime = $this->cmdTime ? strtotime($this->cmdTime) : 0;

            if($gamer->game->status == 1 && ($lastwordTime+$die_word_long) >= time()) {  //$gamer->kill_night_number == $gamer->game->current_night &&
                $text = "Последнее слово ".Game::userUrlName($gamer->user)." донеслось эхом среди темных переулков:\n<i>".$cmd_arr[2]."</i>";
                //$text = "Кто-то слышал, как ".Game::userUrlName($gamer->user)." перед смертью кричал: \n".$cmd_arr[2];
                $this->sendAnswer([['text'=>$text]],$gamer->game->group_id);
                $res['text'] = "Ваше сообщение отправлено";
                return [$res];
            }
        }
        if(strpos($command,'getrole=')!==false) {
            $cmd_arr = explode('=',$command);
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            if($gamer) {
                $gm2 = GameUser::where('game_id',$gamer->game_id)->where('role_id',$cmd_arr[1])->first();
                if($gm2) {
                    $gm2->role_id = $gamer->role_id;
                    $gm2->save();
                }
                $gamer->role_id = $cmd_arr[1];
                $gamer->save();
            }
            $res['text'] = 'ok';
            return [$res];
        }
        if(strpos($command,'/start')!==false) {
            $cmd_arr = explode(' ',$command);
            if(isset($cmd_arr[1]) && $cmd_arr[1] === 'roulette') {
                $season = Currency::seasonCurOfMonth();
                $res['text'] = "Вы можете выиграть в рулетку ценные призы. Стоимость участия 1".Currency::allCurrencies()[$season];
                $res['text'] .="\n\nЧтобы сыграть, нажми на команду /roulette";
                $res['keyboard'] = $this->mainMenu;
                return [$res];
            }
            if(isset($cmd_arr[1]) && strpos($cmd_arr[1],'teamgm_')!==false) {
                $gameParams = explode('_',$cmd_arr[1]);
                if(isset($gameParams[1]) && is_numeric($gameParams[1])) {
                    $isGamer = GameUser::where(['game_id'=>$gameParams[1],'user_id'=>$chat_id])->first();
                    $game = GameModel::where('id',$gameParams[1])->first();
                    if(!$isGamer && $game) {
                        if($game->status > 0) {
                            $res['text'] = "Регистрация на эту игру уже была завершена";
                            return [$res];
                        }
                        //обновим имена
                        $user = BotUser::where('id',$chat_id)->first();
                        $updates = $hookUpdate;
                        $params = [
                            'nick_name'=>empty($updates["message"]["from"]["username"]) ? null : $updates["message"]["from"]["username"],
                            'first_name'=>$updates['message']['from']['first_name'] ?? null,
                            'last_name'=>$updates['message']['from']['last_name']  ?? null,
                        ];
                        $user->fill($params);
                        $user->save();
                        GameUser::connectNewGame(['game_id'=>$gameParams[1],'user_id'=>$chat_id,'role_id'=>0,'team'=>$gameParams[2]]);
                        //GameUser::create(['game_id'=>$gameParams[1],'user_id'=>$chat_id,'role_id'=>0,'team'=>$gameParams[2]]);
                    }
                    if(!$isGamer && $game && $game->options) {
                        $gamers = Game::editRegistrationMessageTeam($game);
                        $gmCount = $gamers->count();
                        $res['text'] = "Вы присоединились к игре в чате <a href='".$game->group->getUrl()."'>{$game->group->title}</a>
                        \nИгра скоро начнётся. Вы получите свою роль в начале игры";

                        $gamersCount = Setting::groupSettingValue($game->group_id,'gamers_count');
                        if($gamersCount <= $gmCount) {  //нужное количество достигнуто -- запускаем
                            $options = json_decode($game->options,true);  // {"message_id":"2909"}
                            if(isset($options['task'])) {
                                $task = TaskModel::where('id',$options['task'])->first();
                                if($task) {
                                    $task->is_active = 0;
                                    $task->save();
                                }
                            }
                            $options = ['class'=>Game::class, 'method'=>'autostart','param'=>$game->id];
                            $task = TaskModel::create(['game_id'=>$game->id,'name'=>'Автостарт. Игра '.$game->id,'options'=>json_encode($options),'delay'=>2]);
                        }
                    }
                    else {
                        $res['text'] = "Вы уже присоединились к игре в чате <a href='".$game->group->getUrl()."'>{$game->group->title}</a>
                        \nОжидайте начала игры";
                    }
                    $res['keyboard'] = $this->mainMenu;
                    return [$res];
                }
            }

            //----------------------------------------------------------------------------
            if(isset($cmd_arr[1]) && strpos($cmd_arr[1],'game_')!==false) {
                $gameParams = explode('_',$cmd_arr[1]);
                if(isset($gameParams[1]) && is_numeric($gameParams[1])) {
                    $isGamer = GameUser::where(['game_id'=>$gameParams[1],'user_id'=>$chat_id])->first();
                    $game = GameModel::where('id',$gameParams[1])->first();
                    if(!$isGamer && $game) {
                        if($game->status > 0) {
                            $res['text'] = "Регистрация на эту игру уже была завершена";
                            return [$res];
                        }
                        //обновим имена
                        $user = BotUser::where('id',$chat_id)->first();
                        $updates = $hookUpdate;
                        $params = [
                            'nick_name'=>empty($updates["message"]["from"]["username"]) ? null : $updates["message"]["from"]["username"],
                            'first_name'=>$updates['message']['from']['first_name'] ?? null,
                            'last_name'=>$updates['message']['from']['last_name']  ?? null,
                        ];
                        $user->fill($params);
                        $user->save();
                        
                        GameUser::connectNewGame(['game_id'=>$gameParams[1],'user_id'=>$chat_id,'role_id'=>0]); //create
                    }
                    if(!$isGamer && $game && $game->options) {
                        $groupUrl = $game->group->getUrl();
                        if(!$groupUrl) {
                            $messGroup['text'] = "У меня недостаточно прав, чтоб вести игру в этой группе. Не могу создать ссылку на группу. \nРегистрация остановлена.
                            \nВыдайте мне недостающие права и снова запустите регистрацию";
                            $this->sendAnswer([$messGroup],$game->group_id);
                            $res['text'] = "Игра отменена. Недостаточно прав в группе для проведения игры";
                            $game->status = 2;
                            $game->save();

                            $options = json_decode($game->options,true);  // {"message_id":"2909"}
                            try {
                                $this->getApi()->deleteMessage(['chat_id'=>$game->group_id,'message_id'=>$options['message_id']]);
                            }
                            catch(Exception $e) {  }
                            return [$res];
                        }
                        $gamers = Game::editRegistrationMessageStandart($game);
                        $gmCount = $gamers->count();
                        $res['text'] = "Вы присоединились к игре в чате <a href='".$groupUrl."'>{$game->group->title}</a>
                        \nИгра скоро начнётся. Вы получите свою роль в начале игры";

                        $gamersCount = Setting::groupSettingValue($game->group_id,'gamers_count');
                        if($gamersCount <= $gmCount) {  //нужное количество достигнуто -- запускаем
                            $options = json_decode($game->options,true);  // {"message_id":"2909"}
                            if(isset($options['task'])) {
                                $task = TaskModel::where('id',$options['task'])->first();
                                if($task) {
                                    $task->is_active = 0;
                                    $task->save();
                                }
                            }
                            $options = ['class'=>Game::class, 'method'=>'autostart','param'=>$game->id];
                            $task = TaskModel::create(['game_id'=>$game->id,'name'=>'Автостарт. Игра '.$game->id,'options'=>json_encode($options),'delay'=>2]);
                        }
                    }
                    else {
                        $res['text'] = "Вы уже присоединились к игре в чате <a href='".$game->group->getUrl()."'>{$game->group->title}</a>
                        \nОжидайте начала игры";
                    }
                    $res['keyboard'] = $this->mainMenu;
                    return [$res];
                }
            }
            //-------------------------------------------------------------
            $res['text'] = "Привет. Ты в игровом боте Мафия";
            $res['keyboard'] = $this->mainMenu;
            return [$res];
        }
        if($command === "/view_payments" && in_array($chat_id,['522927544','376753094'])) {
            $start = date('Y-m-d',strtotime("-7 day"));
            $res['text'] = "<b>Платежи за последнюю неделю</b>";
            $payments = Payment::with('offer')->with('user')->where('status',1)->where('created_at','>',$start)->orderBy('created_at')->get();
            $idx = 1;
            foreach($payments as $paym) {
                $res['text'] .= "\n\n".($idx++).". <b>{$paym->offer}</b>\nПользователь: ".Game::userUrlName($paym->user).
                "\nДата: {$paym->created_at} \nСумма платежа: {$paym->amount} {$paym->currency}";
                if($paym->currency =='USD') {
                    $res['text'] .="\nМой доход: {$paym->offer->price} USD";
                }
            }
            return [$res];
        }
        if($command === "/addmybalance" && in_array($chat_id,['522927544','376753094'])) {
            $botUser = BotUser::where('id',$chat_id)->first();
            $allcurs = Currency::allCurrencies();
            foreach($allcurs as $k=>$v) {
                $botUser->addBalance($k,1000,false);
            }
            $botUser->save();
            $res['text'] = "Все ваши балансы увеличены на 1000";
            return [$res];
        }
        if(strpos($command,'onoopenrole_')!==false) {
            $cmd_arr = explode('_',$command);
            $victim = GameUser::where('id',$cmd_arr[2])->first();

            $tm = $cmd_arr[1];
            $tek_time = time();
            if($tek_time - $tm > 90) {
                $res['text'] = "Ты не успел сделать выбор";
            }
            else {
                $group_mes['text'] = "🎈Оно напел роль одного из игроков: ".Game::userUrlName($victim->user).' - '.$victim->role;
                $this->sendAnswer([$group_mes], $victim->game->group_id);
                $res['text'] = "Ты раскрыл роль: ".Game::userUrlName($victim->user).' - '.$victim->role;
            }
            return [$res];
        }
        if(strpos($command,'bombaget_')!==false) {
            $cmd_arr = explode('_',$command);
            $tm = $cmd_arr[1];
            $tek_time = time();
            $victim = GameUser::where('id',$cmd_arr[2])->first();
            $gamer = GameUser::where('user_id',$chat_id)->where('game_id',$victim->game_id)->first();
            $bomba_time = Setting::groupSettingValue($gamer->game->group_id,'bomba_long');
            if($victim->game->status != 1) {
                $res['text'] = "Игра была завершена";
            }
            else if($tek_time - $tm <= $bomba_time) {
                $res['text'] = "Вы забрали с собой ".Game::userUrlName($victim->user).' - '.$victim->role;
                $group_mes['text'] = "💣Бомба забрала к себе в мир иной ".Game::userUrlName($victim->user).' - '.$victim->role."\n".
                '"Мы навсегда будем вместе!", – только и слышали жители...';
                $winIfKillerRole = [16,17,18,20,21,22, 25, 28, 20, 30, 27];
                if(in_array($victim->role_id, $winIfKillerRole)) { //выиграла, если забрала убивающую роль
                    $gamer->is_active = 2;
                }
                else if($victim->role_id == 10 && GamerFunctions::oderjimIsNeytral($gamer->game)) {
                    $gamer->is_active = 2;
                }
                else {
                    $gamer->is_active = 0;
                }
                $gamer->save();
                GamerFunctions::user_deactivate(['killer_id'=>$gamer->id, 'cmd_param'=>$victim->id],false);
                $this->sendAnswer([$group_mes], $gamer->game->group_id);
            }
            else {
                $res['text'] = "Твоим решением было остаться на всю жизнь одинокой и никого не забирать к себе в мир иной...";
            }
            return [$res];
        }
        if(strpos($command,'voitprot_')!==false) {
            $cmd_arr = explode('_',$command);
            $voiting_id = $cmd_arr[1];
            $selgamer_id = $cmd_arr[2];
            $voiting = Voiting::where('id',$voiting_id)->first();
            if(!$voiting || !$voiting->isActive()) return [];
            $tGamer = GameUser::where('user_id', $chat_id)->where('game_id',$voiting->game_id)->where('is_active',1)->first();
            if(!$tGamer) return [];
            if($selgamer_id === 'empty') {
                $this->sendAnswer([['text'=>Game::userUrlName($tGamer->user)." решил никого не вешать"]],$tGamer->game->group_id);
                $res['text'] = "Вы решили никого не вешать";
                return [$res];
            }
            Vote::firstOrCreate(['voiting_id'=>$voiting_id,'vote_user_id'=>$chat_id],['gamer_id'=>$selgamer_id,'vote_role_id'=>$tGamer->role_id]);
            $gamer = GameUser::where('id', $selgamer_id)->first();
            $res['text'] = "Ваш выбор: ".$gamer->user;

            $user = BotUser::where('id',$chat_id)->first();
            $anonym_voiting = Setting::groupSettingValue($gamer->game->group_id,'anonym_voiting');
            if($anonym_voiting==='yes') {
                $this->sendAnswer([['text'=>"Кто-то проголосовал за: ".Game::userUrlName($gamer->user)]],$gamer->game->group_id);
            }
            else {
                $this->sendAnswer([['text'=>Game::userUrlName($user ?? new BotUser(['id'=>$chat_id, 'first_name'=>'Бот '.$chat_id]))." голосовал(а) за: ".Game::userUrlName($gamer->user)]],$gamer->game->group_id);
            }
            return [$res];
        }
        if($command === '/mybalance') {
            $gameCurrencies = Currency::allCurrencies();
            $botUser = BotUser::where('id',$chat_id)->first();
            if($botUser) {
                $res['text'] = "<b>Ваши балансы:</b>\n";
                if($botUser->balances) {
                    $balances = json_decode($botUser->balances,true);
                }
                else {
                    $balances[Currency::R_WINDBUCKS] = 0;
                    $balances[Currency::R_WINDCOIN] = 0;
                    $botUser->balances = json_encode($balances);
                    $botUser->save();
                }
                foreach($balances as $k=>$v) {
                    $res['text'] .= "\n<b>".Currency::CURNAMES[$k].":</b> ".$v.' '.$gameCurrencies[$k];
                }
                return [$res];
            }
            return [];
        }
        if($this->clearCommand($command) === 'Достижения' || $this->clearCommand($command) === 'Achievements') {
            $res['text'] = "<b>🏆 Достижения:</b>\n\n";

            $results = UserAchievement::with('achievement')->where('user_id',$chat_id)->get();
            if($results->count()) {
                $textarr = [];
                $index = 1;
                foreach($results as $uachive) {
                    $textarr[] = $index.'. '.$uachive->achievement->name;
                }
                $res['text'] .= implode("\n",$textarr);
            }
            else {
                $res['text'] .= "У вас пока нет достижений.";
            }
            return [$res];
        }
        if(strpos($command,'gunyes&')!==false) {
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            $fres = [];
            if($gamer) {
                $result = GamerFunctions::execBafMethod($gamer,'gunyes');
                if($result) {
                    $res['text'] = $result['text'];
                }
                else {
                    $res['text'] = "Баф не был активирован для этой игры";
                }
                $fres = [$res];
            }
            return $fres;

        }
        if(strpos($command,'gunno&')!==false) {
            return [];
        }

        if(strpos($command,'okoexecyes&')!==false) {
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            $fres = [];
            if($gamer) {
                $result = GamerFunctions::execBafMethod($gamer,'oko_exec');
                if($result) {
                    $res['text'] = $result['text'];
                }
                else {
                    $res['text'] = "Баф не был активирован для этой игры";
                }
                $fres = [$res];
            }
            return $fres;

        }
        //комиссар не может проявлять активность днем
        if(strpos($command, 'puaroucheck_')!==false || strpos($command, 'puaro_check')!==false) {
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();

            if($gamer && $gamer->game && !$gamer->game->isNight()) {
                $res['text'] = "Вы не успели сделать выбор ночью, уже наступил день....";

                return [$res];
            }
        }
        if(strpos($command,'okoexecno&')!==false) {
            return [];
        }


        return parent::prepareAnswer($command,$chat_id,$callback_id,$hookUpdate);
    }
    public function prepareGroupAnswer($command,$group_id,$from,$hookUpdate) {    
        if($from['is_bot']) return [];

        $cmd_arr = explode(' ',str_replace('/','',$command));
        // Log::info('PrepareGroupAnswer', [
        //     'cmd_arr'       => print_r($cmd_arr, true),
        //     'group_id'      => $group_id,
        //     'from'          => print_r($from, true),
        //     'hookUpdate'    => print_r($hookUpdate),
        //     'command'       => $command
        // ]);

        $res = [];

        $gameCurrencies = Currency::allCurrencies();
        if(in_array($cmd_arr[0],array_keys($gameCurrencies))) {
           if(!isset($hookUpdate['message']['reply_to_message'])) {
                $res['text'] = "Отправьте команду /{$cmd_arr[0]} в ответ на сообщение пользователя, которому хотите отправить валюту";
                return [$res];
           }
           if(!isset($cmd_arr[1]) || strpos($cmd_arr[1], '.') !== false || (int)$cmd_arr[1] <= 0) {
                $res['text'] = "Отправьте команду в правильном формате: команда целое, положительное число. Например /windbucks 10";
                return [$res];
           }
           $cmd_arr[1] = abs(round($cmd_arr[1]));
           $this->userStart($hookUpdate['message']['reply_to_message']['from']['id'],['message'=>$hookUpdate['message']['reply_to_message']]);
           $res = [];
           if($hookUpdate['message']['reply_to_message']['from']['is_bot']) {
                $res['text'] = "Нельзя отправить валюту боту. Отправьте валюту в ответ на сообщение игрока";
           }
           else if($from['id'] == $hookUpdate['message']['reply_to_message']['from']['id']) {
                $res['text'] = "Отправьте команду /{$cmd_arr[0]} в ответ на сообщение пользователя, которому хотите отправить валюту.\n<b>Нельзя отправлять валюту самому себе!</b>";
           }
           else if(Currency::sendCurrency($from['id'],$hookUpdate['message']['reply_to_message']['from']['id'],$cmd_arr[0],$cmd_arr[1],$group_id)) {
                $recp = $hookUpdate['message']['reply_to_message']['from'];
                $senderName = $from['first_name']." ".($from['last_name'] ?? '');
                $recpName = $recp['first_name']." ".($recp['last_name'] ?? '');
                $res['text'] = "<b>$senderName</b> подарил <b>$recpName</b> <b>{$cmd_arr[1]}</b> {$gameCurrencies[$cmd_arr[0]]}";
           }
           else {
                $res['text'] = "Недостаточно монет на балансе для отправки пользователю";
           }
           return [$res];
        }
        //if(strpos($command,'/leave')!==false) {
        if($command === '/leave' || $command === '/leave@'.config('app.bot_nick')) {
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            $isCanLeave = Setting::groupSettingValue($group_id, 'leave_game');
            //ищем запущенную игру
            $game = GameModel::where('group_id', $group_id)->where('status','<',2)->first();
            if(!$game) return [];
            if($isCanLeave !== 'yes' && $game->status == 1) return [];            
            $gamer = GameUser::where('user_id',$from['id'])->where('game_id',$game->id)->first();
            if(!$gamer || !$gamer->isActive()) return [];
            /* отключили блокировку ночи
            if($game->times_of_day == GameModel::NIGHT) {
                $res['text'] = "Чтобы выйти из игры дождитесь окончания ночи";
                return [$res];
            } */
            $fres = [];
            if($gamer->role_id) {
                $res['text'] =  "<b>".Game::userUrlName($gamer->user)." – {$gamer->role}</b> прыгнул в объятия смерти, вечная ему память...🕯";
                $this->sendAnswer([$res],$group_id);
                $game = $gamer->game;
                $gamer->is_active = 0;
                $gamer->save();

                GamerFunctions::topGamersIfNeed($gamer->game);

                // if($gamer->role_id == 4) {
                //     GamerFunctions::ifSergantTop($gamer->game);
                // }

                // if($gamer->role_id == 15) {
                //     GamerFunctions::ifAssistentTop($gamer->game);
                // }

                if($gamer->role_id == 17) {
                    $g = GameUser::where(['game_id' => $game->id, 'role_id' => 25, 'is_active' => 1])->first();
                    if($g) {
                        $groupMess = ['text'=>"<b>🤵🏻 Мафия</b> повышен до <b>🤵🏻 Дона Корлеоне</b>"];
                        $this->sendAnswer([$groupMess], $group_id);
                    }
                }

                return [];
            }
            else {
                //лив во время регистрации. не предупреждать и не информировать
                $res['text'] ="Вы покинули регистрацию в чате <a href='".$gamer->game->group->getUrl()."'>".$gamer->game->group."</a>";
                $this->sendAnswer([$res],$gamer->user_id);
                //и обновить сообщение
                $gamer->delete();
                Game::editRegistrationMessage($game);
                return [];
            }
            $fres[] = $res;
            $warn_for_leave = Setting::groupSettingValue($group_id, 'warn_for_leave');
            if($warn_for_leave === 'yes') {
                UserWarning::create(['user_id'=>$from['id'],'group_id'=>$group_id,'warning_id'=>5]);
                $warns = UserWarning::where(['user_id'=>$from['id'],'group_id'=>$group_id])->get();
                $warn = WarningType::where('id',5)->first();
                $res=[]; $res['text'] = Game::userUrlName($gamer->user). " предупреждён (".$warns->count()."/6).\n<b>Причина:</b> {$warn->name}";
                $fres[] = $res;
            }
            $game = $gamer->game;
            $gamer->is_active = 0;
            $gamer->save();

            if($game->status && $gameOver = Game::isGameOver($game->id)) {
                Game::stopGame($game, $gameOver);
            }
            return $fres;
        }
        if($command === '/active') { //активировать пользователя в этой группе
            $member = ChatMember::where(['group_id'=>$group_id, 'member_id'=>$from['id']])->first();
            if($member) {
                $res['text'] = Game::userUrlName($member->member)." уже в этой группе";
            }
            else {
                $member = ChatMember::updateOrCreate(
                    [
                        'member_id'=>$from['id'],'group_id'=>$group_id
                    ],
                    [
                        'username'=> $from['username'] ?? '',
                        'first_name'=> $from['first_name'] ?? null,
                        'last_name'=> $from['last_name'] ?? null,
                        'is_bot'=>($from['is_bot'] ?? false) ? 1 : 0,
                        'is_premium'=>($from['is_premium'] ?? false) ? 1 : 0,
                    ]
                );
                $member->refresh();
                $res['text'] = Game::userUrlName($member->member)." добавлен в участники этой группы";
            }
            return [$res];
        }
        if($command === '/roles') {
            $res['text'] = '🔥 Описание ролей 🔥';
            $res['inline_keyboard']['inline_keyboard'] = [[['text'=>'Роли','url'=>"https://google.com"]]];
            return [$res];
        }
        if(strpos($command, "gallow_")!==false) { //голосование Да/Нет
            $cmd_arr = explode('_',$command);
            $gamer = GameUser::where('id',$cmd_arr[1])->first();
            if(!$gamer) return [];
            $voiting = Voiting::where('id',$cmd_arr[2])->first();
            if(!$voiting || $voiting->is_active == 2) return [];
            //проверим, является ли юзер активным геймером, который может голосовать
          //  print_r(['user_id'=>$from['id'],'game_id'=>$gamer->game_id]);
            $voteUser = GameUser::where(['user_id'=>$from['id'],'game_id'=>$gamer->game_id])->first();
            if(!$voteUser || !$voteUser->isActive()) return []; //не может голосовать
            if($voteUser->id == $cmd_arr[1]) return [];  //нельзя голосовать за себя
            if(!GamerFunctions::isCanMove($voteUser)) return [];  //нельзя голосовать под красоткой

            YesnoVote::updateOrCreate(['voiting_id'=>$cmd_arr[2],'gamer_id'=>$cmd_arr[1],'vote_user_id'=>$from['id']],
                ['answer'=>$cmd_arr[3], 'vote_role_id'=>$voteUser->role_id]);
            $answers = Game::voting_yesno_results($cmd_arr[2]);
            $inlineKbd = Game::yes_no_buttons($cmd_arr[1],$cmd_arr[2],$answers['yes'],$answers['no']);
            $this->editInlineKeyboard($group_id,$hookUpdate['message_id'],$inlineKbd);
            return [];
           // return [['text'=>"$group_id , {$hookUpdate['message_id']} "]];
        }
        if(strpos($command,"/reggame=")!==false && in_array($group_id,['-1002046415969','-1002082482712','-1002594328742'])) {
            $cmd_arr = explode('=',$command);
            if(!is_numeric($cmd_arr[1])) {
                $res['text'] = "значение после знака '=' должно быть числом";
                return [$res];
            }

            $reg_long_all = Setting::groupSettingValue($group_id,'reg_long_all');
            if($reg_long_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) return [];

            $game = GameModel::where('status',0)->where('group_id',$group_id)->first();
            if(!$game) return [];
            $count = GameUser::select('id')->where('game_id',$game->id)->get()->count();
            $genCnt = round($cmd_arr[1]) - $count;
            $sort_id = $count+1;
            while($genCnt > 0) {            
                GameUser::create(['game_id'=>$game->id,'user_id'=>random_int(123,9876),'role_id'=>0, 'sort_id'=>$sort_id++]);
                $genCnt--;
            }
           return [['text'=>'ok']];
        }
        if(strpos($command,'/teamgame@'.config('app.bot_nick')) !== false) {
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            $reg_game_all = Setting::groupSettingValue($group_id,'reg_game_all');
            if($reg_game_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) return [];

            $game = GameModel::where('group_id',$group_id)->whereIn('status',[0,1])->first();
            if($game && $game->status == 1) {
                return [];
            }

            if($game && $game->status == 0) {
                $options = json_decode($game->options, true);
                if(isset($options['message_id'])) $this->deleteMessage($group_id, $options['message_id']);
                $gamers = GameUser::with('user')->where('game_id',$game->id)->where('is_active',1)->get();
                $gmCount = $gamers->count();
                $txtUsers = [];
                foreach($gamers as $gamer) {
                    $prefTeam = isset(Game::COMMAND_COLORS[$gamer->team]) ? Game::COMMAND_COLORS[$gamer->team] . ' ' : '';
                    $txtUsers[] = $prefTeam . Game::userUrlName($gamer->user);                    
                }
                $res['text'] = "Ведётся набор в игру\n\n".implode("\n",$txtUsers).
                "\n\nПрисоединилось: $gmCount игроков";
            }
            else {
                $res['text'] = "Ведётся набор в игру\n\nПрисоединилось: 0 игроков";
                $game = GameModel::firstOrCreate(['group_id'=>$group_id,'status'=>0],['is_team'=>1]);
                $options = ['class'=>Game::class, 'method'=>'autostart','param'=>$game->id];
                $delay = Setting::groupSettingValue($game->group_id,'registr_long');
                $task = TaskModel::create(['game_id'=>$game->id,'name'=>'Автостарт. Игра '.$game->id,'options'=>json_encode($options),'delay'=>$delay]);
                $saver = new MessageResultSaver($game);
                $saver->saveOption('task',$task->id);
            }

            $res['saver'] = new MessageResultSaver($game);
            $auto_locking_message = Setting::groupSettingValue($group_id, 'auto_locking_message');
            if($auto_locking_message === 'yes') $res['pin'] = 1;
            foreach(Game::COMMAND_COLORS as $k=>$v) {
                $res['inline_keyboard']['inline_keyboard'][] = [['text'=>$v.' Присоединиться к игре','url'=>"https://t.me/".config('app.bot_nick')."?start=teamgm_".$game->id."_".$k]];
            }
            return [$res];
        }
        if(strpos($command,'/settings@'.config('app.bot_nick'))!==false) {
            $this->deleteMessage($group_id, $hookUpdate['message']['message_id']);
            try {
                $chatMember = $this->getApi()->getChatMember(['chat_id'=>$group_id,'user_id'=>$from['id']]);
            }
            catch(Exception $e) {
                $chatMember = null;
                $mess['text'] = "Я не смог проверить права пользователя, который пытается открыть настройки группы. Предоставьте мне соответствующие разрешения в настройках группы";
                $this->sendAnswer([$mess], $group_id);
            }
            if($chatMember && in_array($chatMember->status,['administrator','creator'])) {
                //открыть настройки собственно
                $botGroup = BotGroup::where('id',$group_id)->first();
                if($botGroup) {
                    $mess['text'] = "Вы настраиваете группу <b>{$botGroup->title}</b>\nТариф группы: <b>{$botGroup->tarif}</b>
                    \nЧто вы хотели бы изменить?";
                    $setObject = Setting::groupSettings($botGroup->id);
                    $mess['inline_keyboard'] = $this->inlineKeyboard($setObject['modifSets'],1,"changegr1st&{$botGroup->id}&",false,'set_key','title_value');
                    $this->sendAnswer([$mess],$from['id']);

                    if(!$botGroup->who_add) {
                        $botGroup->who_add = $from['id'];
                        $botGroup->save();
                    }
                }
            }
            return [];
        }
        if($command === '/updowner') {
            $result = $this->getApi()->getChatMember(['chat_id'=>$group_id,'user_id'=>$from['id']]);
            if($result->status == 'creator') {
                $group = BotGroup::where('id',$group_id)->first();
                if($group) {
                    $group->who_add = $from['id'];
                    $group->save();
                    $res['text'] = "Владелец группы обновлен";
                    return [$res];
                }
            }
            return [];
        }
        if(strpos($command,"/kick") !==false ) {
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            if(!Game::hasRightToStart($from['id'],$group_id)) return [];
            $cmd_arr = explode(" ",$command);
            $game = GameModel::where('group_id',$group_id)->where('status','<',2)->first();
            if(!$game) {
                $res['text'] = "Нет запущенной игры или регистрации";
                return [$res];
            }
            if(!isset($cmd_arr[1]) || !is_numeric($cmd_arr[1])) {
                $res['text'] = "Неверный формат команды. Укажите после /kick номер или ID игрока
                \n<b>/kick 8</b> или <b>/kick 123456789</b>";
                return [$res];
            }
            $glGamer = false;
            if($cmd_arr[1] < 100) { //номер 
                $gamers = GameUser::where('game_id',$game->id)->get()->all();
                if($cmd_arr[1] <= count($gamers)) {
                    $glGamer = $gamers[$cmd_arr[1]-1];
                }
                else {
                    $res['text'] = "Указан несуществующий номер участника";
                }                
            }
            else {  //ID
                $gamer = GameUser::where('user_id',$cmd_arr[1])->where('game_id',$game->id)->first();
                if($gamer) {
                    $glGamer = $gamer;
                }
                else {
                    $res['text'] = "Указан несуществующий ID участника";
                }                
            }

            if($glGamer) {
                $gamerName = Game::userUrlName($glGamer->user);
                if($glGamer->role) {
                    $res['text'] = "<b>".$gamerName." – {$glGamer->role}</b> прыгнул в объятия смерти, вечная ему память...🕯";
                    $glGamer->update(['is_active' => 0]);
                }

                
                if($game->status == 0) { //обновим сообщение
                    $glGamer->delete();
                    $res['text'] = "<b>".$gamerName."</b> был удален!";  
                    Game::editRegistrationMessage($game);
                } elseif ($gameOver = Game::isGameOver($game->id)) {
                    Game::stopGame($game, $gameOver);
                    $res['text'] .= "\n\n❌ Игра была завершена из-за нехватки пользователей...";
                }
                
            }
            return [$res];
        }
        if(strpos($command,'/game@'.config('app.bot_nick'))!==false) {   // && $group_id == '-1002082482712'
            //сразу удалим это сообщение
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            //------------------------------
            $reg_game_all = Setting::groupSettingValue($group_id,'reg_game_all');
            if($reg_game_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) return [];

            $game = GameModel::where('group_id',$group_id)->whereIn('status',[0,1])->first();
            if($game && $game->status == 1) {
                return [];
            }
            if($game && $game->status == 0) {
                if(!$game->options) return [];
                $options = json_decode($game->options, true);                
                if(!$options || !isset($options['message_id'])) return [];
                $this->deleteMessage($group_id, $options['message_id']);
                $gamers = GameUser::with('user')->where('game_id',$game->id)->where('is_active',1)->get();
                $gmCount = $gamers->count();
                $txtUsers = [];
                foreach($gamers as $gamer) {
                    $txtUsers[] = Game::userUrlName($gamer->user);
                }
                $res['text'] = "Ведётся набор в игру\n\n".implode("\n",$txtUsers).
                "\n\nПрисоединилось: $gmCount игроков";
            }
            else {
                $res['text'] = "Ведётся набор в игру\n\nПрисоединилось: 0 игроков";
                $game = GameModel::firstOrCreate(['group_id'=>$group_id,'status'=>0]);
                $options = ['class'=>Game::class, 'method'=>'autostart','param'=>$game->id];
                $delay = Setting::groupSettingValue($game->group_id,'registr_long');
                $task = TaskModel::create(['game_id'=>$game->id,'name'=>'Автостарт. Игра '.$game->id,'options'=>json_encode($options),'delay'=>$delay]);
                $saver = new MessageResultSaver($game);
                $saver->saveOption('task',$task->id);
            }
            $res['saver'] = new MessageResultSaver($game);
            $auto_locking_message = Setting::groupSettingValue($group_id, 'auto_locking_message');
            if($auto_locking_message === 'yes') $res['pin'] = 1;
            $res['inline_keyboard']['inline_keyboard'] = [[['text'=>'Присоединиться к игре','url'=>"https://t.me/".config('app.bot_nick')."?start=game_".$game->id]]];
            return [$res];
        }
        if(strpos($command,'/extend@'.config('app.bot_nick'))!==false) {
            //сразу удалим это сообщение
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            //------------------------------
            $reg_long_all = Setting::groupSettingValue($group_id,'reg_long_all');
            if($reg_long_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) return [];

            $game = GameModel::where(['group_id'=>$group_id,'status'=>0])->first();
            if(!$game) {
                $res['text'] = "Набор игроков не был запущен";
            }
            else {
                $options = json_decode($game->options,true);  // {"message_id":"2909"}
                if(isset($options['task'])) {
                    $task = TaskModel::where('id',$options['task'])->first();
                    if($task) {
                        $task->is_active = 0;
                        $task->save();
                    }
                }
                $res['text'] = "<b>Таймер автоматического старта игры отключен.</b>\nЗапустите игру вручную через команду /start.";
            }
            return [$res];
        }
        if(strpos($command,'/start@'.config('app.bot_nick'))!==false) {
            //сразу удалим это сообщение
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            //------------------------------
            $game_start_all = Setting::groupSettingValue($group_id,'game_start_all');
            if($game_start_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) return [];
            
            //проверим количество игроков
            $game = GameModel::where(['group_id'=>$group_id,'status'=>0])->first();
            if($game) {
                $gamers = GameUser::where('game_id',$game->id)->where('is_active',1)->get();
                if($gamers->count() < 5) {
                    Game::stopGameRegistration($game);
                    $res['text'] = '<b>Недостаточно игроков для начала игры...</b>';
                    return [$res];
                }
                if($game->is_team) {
                    $team1 = GameUser::where('game_id',$game->id)->where('team',1)->where('is_active',1)->get();
                    $team2 = GameUser::where('game_id',$game->id)->where('team',2)->where('is_active',1)->get();
                    if($team1->count() !== $team2->count()) {
                        Game::stopGameRegistration($game);
                        $res['text'] = '<b>Количество игроков в командах должно быть одинаковым...</b>';
                        return [$res];
                    }
                }
            }
            //-----------------------------            
            if(!$game) {
                $res['text'] = "Набор игроков не был запущен";
            }
            else {
                $options = json_decode($game->options,true);  // {"message_id":"2909"}
                try {
                    $this->getApi()->deleteMessage(['chat_id'=>$group_id,'message_id'=>$options['message_id']]);
                }
                catch(Exception $e) {

                }
                if(isset($options['task'])) {
                    $task = TaskModel::where('id',$options['task'])->first();
                    if($task) {
                        $task->is_active = 0;
                        $task->save();
                    }
                }
                $game->options = null;
                $game->status = 1;
                $game->save();
                $res['text'] = '<b>Игра начинается. Игроки получают свои роли...</b>';
                if($game->group_id == '-1002082482712') $res['text'] = "<b>Игра #{$game->id} начинается. Игроки получают свои роли...</b>"; //Мафия тест
                $options = ['class'=>Game::class, 'method'=>'assignRolesToGamers','param'=>$game->id];
                TaskModel::create(['game_id'=>$game->id,'name'=>'Назначение ролей. Игра '.$game->id,'options'=>json_encode($options)]);
            }

            return [$res];
        }
        if($command === '/pause'  && Game::hasRightToStart($from['id'],$group_id)) {
            //сразу удалим это сообщение
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            //------------------------------
            $game = GameModel::where(['status'=>1,'group_id'=>$group_id])->first();
            if($game) {
                $game->status = 2;
                $game->save();
                $res['text'] = "Игра приостановлена ...";
                $res['inline_keyboard']['inline_keyboard'] = [[['text'=>'Продолжить','callback_data'=>'resume&'.$game->id]]];
            }
            else {
                $res['text'] = "Нет запущенной игры";
            }
            return [$res];

        }
        if(strpos($command,"unmute&")!==false  && Game::isGroupAdmin($from['id'],$group_id)) {
            try {
                $this->getApi()->deleteMessage(['chat_id'=>$group_id,'message_id'=>$hookUpdate['message_id']]);
            }
            catch(Exception $e) {

            }
            $cmd_arr = explode("&",$command);
            $this->unmute($cmd_arr[1],$group_id);
            return [];
        }
        if(strpos($command,'/stop@'.config('app.bot_nick'))!==false) {
            //сразу удалим это сообщение
            $this->deleteMessage($group_id,$hookUpdate['message']['message_id']);
            //------------------------------
            $game_stop_all = Setting::groupSettingValue($group_id,'game_stop_all');
            if($game_stop_all!=='yes' && !Game::hasRightToStart($from['id'],$group_id)) {
                Log::info('non_stop');
                return [];
            }

            $game = GameModel::where('group_id',$group_id)->whereIn('status',[0,1])->first();
            if($game && $game->status == 1) {
                $game->status = 2;
                $game->save();
                DB::table('user_game_roles')->where(['game_id'=> $game->id])->update(['is_active'=>0]); //'is_active'=>1,
                $res['text'] = "<b>Игра остановлена со стороны администратора!</b>";
            }
            else if($game && $game->status == 0) {
                Game::stopGameRegistration($game);
                $res['text'] = "<b>Регистрация остановлена со стороны администратора!</b>";
            }
            else {
                $res['text'] = "Нет запущенной игры";
            }
            return [$res];

        }
        if(strpos($command,"resume&")!==false) {
            $cmd_arr = explode('&',$command);
            $game = GameModel::where('id',$cmd_arr[1])->first();
            $res = [];
            if($game && $game->status == 2) {
                $game->status = 1;
                $game->save();
                $task = TaskModel::where('game_id',$cmd_arr[1])->orderByDesc('id')->first();
                if($task) {
                    $task->is_active = 1;
                    $task->save();
                    $res['text'] = "Игра продолжается ...";

                    if(isset($hookUpdate['message_id'])) {
                        try {
                            $this->getApi()->deleteMessage(['chat_id'=>$game->group_id,'message_id'=>$hookUpdate['message_id']]);
                        }
                        catch(Exception $e) {}
                    }
                }
            }
            if($res) return [$res];
        }       
        return [];
    }
}
