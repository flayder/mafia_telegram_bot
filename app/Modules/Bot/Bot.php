<?php

namespace App\Modules\Bot;

use Exception;
use App\Models\User;
use App\Models\BotUser;
use App\Models\Command;
use App\Models\Product;
use App\Models\Setting;
use App\Models\BotGroup;
use App\Models\GameUser;
use App\Models\ChatMember;
use App\Models\GamerParam;
use App\Modules\Functions;
use App\Modules\Game\Game;
use App\Models\UserWarning;
use App\Models\WarningWord;
use App\Models\TelegramUser;
use App\Modules\StringMaster;
use App\Models\UnionParticipant;
use App\Models\Game as GameModel;
use App\Models\RestrictMediaUser;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Keyboard\Keyboard;
use App\Modules\Game\GamerFunctions;
use App\Modules\Payments\TelegramStarsApi;
use Telegram\Bot\Objects\InlineQuery;
use Egulias\EmailValidator\Warning\Warning;
use Telegram\Bot\Objects\InlineQuery\InlineQueryResultArticle;

class Bot {
      

    protected $dbPrefix=''; //оставлен для совместимости
    protected $token;
    protected $botId;
    protected $botData=null;  
    protected $userData = [];  
    protected $api=null;
    protected $imgFolder;
    protected $admins = ['376753094','522927544'];
    protected $cmdTime = null;
    protected $commandFile;
    protected $message_thread_id = null; //ветка форума
    protected function mediaChatPermisions($allow = false) {
        $permissions = [
            'can_send_messages'=>true,
            'can_send_audios'=>$allow,
            'can_send_documents'=>$allow,
            'can_send_photos'=>$allow,
            'can_send_videos'=>$allow,
            'can_send_video_notes'=>$allow,
            'can_send_voice_notes'=>$allow,
            'can_send_polls'=>false,
            'can_send_other_messages'=>true,
            'can_add_web_page_previews'=>true,
            'can_change_info'=>true,
            'can_invite_users'=>true,
            'can_pin_messages'=>false,
            'can_manage_topics'=>false
        ];
        return $permissions;
    }
    public function mediaMute($user_id, $group_id, $period=1) {
        $permissions = [
            'can_send_messages'=>true,
            'can_send_audios'=>false,
            'can_send_documents'=>false,
            'can_send_photos'=>false,
            'can_send_videos'=>false,
            'can_send_video_notes'=>false,
            'can_send_voice_notes'=>false,
            'can_send_polls'=>false,
            'can_send_other_messages'=>false,
            'can_add_web_page_previews'=>true,
            'can_change_info'=>true,
            'can_invite_users'=>true,
            'can_pin_messages'=>false,
            'can_manage_topics'=>false
        ];
        try {
            $this->getApi()->restrictChatMember(['chat_id'=>$group_id,'user_id'=>$user_id,'permissions'=>$permissions,'until_date'=>(time()+$period)]);
        }
        catch(Exception $e) {

        }
    }
    public function mute($user_id, $group_id, $period=3600) {
        $permissions = [
            'can_send_messages'=>false,
            'can_send_audios'=>false,
            'can_send_documents'=>false,
            'can_send_photos'=>false,
            'can_send_videos'=>false,
            'can_send_video_notes'=>false,
            'can_send_voice_notes'=>false,
            'can_send_polls'=>false,
            'can_send_other_messages'=>false,
            'can_add_web_page_previews'=>false,
            'can_change_info'=>false,
            'can_invite_users'=>false,
            'can_pin_messages'=>false,
            'can_manage_topics'=>false
        ];
        $this->getApi()->restrictChatMember(['chat_id'=>$group_id,'user_id'=>$user_id,'permissions'=>$permissions,'until_date'=>(time()+$period)]);
    }
    public function clearCommand($text)
    {
        return trim(preg_replace("/[^a-zа-я\s]/iu", "", $text));
    }
    public function unmute($user_id, $group_id) {
        $this->mute($user_id,$group_id,35);
    }
    public function inlBtn($text, $data, $type='cmd') {
        $obj = ['text'=>$text];
        switch($type) {
            case 'cmd':
                $obj['callback_data'] = $data;
                break;
            case 'url':
                $obj['url'] = $data;
                break;

        }
        return $obj;
    }
    public function inlineKeyboard(iterable $preButtons,int $lineCount, string $prefix='', bool $isArray=true,$fieldK = null, $fieldV = null,$exclude=null,$captionPrefix='',$prefixArray = null) {
        $keyboard = [];
        $line = [];
        foreach($preButtons as $k=>$v) {            
            $prefText = '';
            if(!$prefixArray) $prefText = $captionPrefix;
            if($fieldK) {                
                if($isArray) {
                    if($exclude && in_array($v[$fieldK], $exclude) ) continue;                    
                    if($prefixArray && in_array($v[$fieldK], $prefixArray) ) $prefText = $captionPrefix;
                    $line[] = ['text'=>$prefText.$v[$fieldV],'callback_data'=>$prefix.$v[$fieldK]];   
                }
                else {
                    if($exclude && in_array($v->$fieldK, $exclude) ) continue;
                    if($prefixArray && in_array($v->$fieldK, $prefixArray) ) $prefText = $captionPrefix;
                    $line[] = ['text'=>$prefText.$v->$fieldV,'callback_data'=>$prefix.$v->$fieldK];   
                }
            }
            else {
                if($exclude && in_array($k, $exclude) ) continue;
                if($prefixArray && in_array($k, $prefixArray) ) $prefText = $captionPrefix;
                $line[] = ['text'=>$prefText.$v,'callback_data'=>$prefix.$k];
            }
            if(count($line) == $lineCount) {
                $keyboard[] = $line;
                $line = [];
            }
        }
        if($line) $keyboard[] = $line;
        return ['inline_keyboard'=>$keyboard];
    }
    public function editInlineKeyboard($chat_id,$message_id,$reply_marlup) {        
        try {              
            $this->getApi()->editMessageReplyMarkup(['chat_id'=>$chat_id,"message_id"=>$message_id, 'reply_markup'=>json_encode($reply_marlup)]); 
        } catch(Exception $e) {
            Log::error("editInlineKeyboard: ".$e->getMessage());
        }
    }
    public function deleteMessage($chat_id, $message_id) {
        try {
            $this->getApi()->deleteMessage(['chat_id'=>$chat_id,'message_id'=>$message_id]);
        }
        catch(Exception $e) {

        }
    }
    public function deleteInlineKeyboard($chat_id,$message_id) {
        try {            
            $this->getApi()->editMessageReplyMarkup(['chat_id'=>$chat_id,"message_id"=>$message_id]); 
        } catch(Exception $e) {}
    }
    public function userData($user_id) {
        if(!isset($this->userData[$user_id])) {
            $this->userData[$user_id] = BotUser::where('id',$user_id)->first();
        }
        if($this->userData[$user_id]) return $this->userData[$user_id]->toArray();
        return [];
    }
    public function userName($user_id) {
        $ud = $this->userData($user_id);
        $str = '';
        if(!empty($ud['mainname'])) $str = $ud['mainname'];
        if(!empty($ud['name'])) $str .=" @".$ud['name'];
        if(empty($str)) $str = $user_id;
        return $str;
    }
    public function genReferalLink() {
        $arr = array_column(BotUser::all()->toArray(),'referal_link_id');
        do {
            $key = Functions::randomString(8, "all");
        }
        while(in_array($key,$arr));
        return $key;
    }
    public function userStart($chat_id, $updates,$reflink=null) {     
        $botUser = BotUser::where('id',$chat_id)->first();
        if($botUser) return $botUser;
        $params = [
            'id'=>$chat_id,             
            'nick_name'=>empty($updates["message"]["from"]["username"]) ? null : $updates["message"]["from"]["username"],
            'first_name'=>$updates['message']['from']['first_name'] ?? null,
            'last_name'=>$updates['message']['from']['last_name']  ?? null,
            'referal_link_id'=>$this->genReferalLink(),            
        ];
        if($reflink) {
            $referer = BotUser::where('referal_link_id',$reflink)->first();
            if($referer) {
                $params['referer_id'] = $referer->id;
            }
        }
        return BotUser::create($params);        
    }
    public function addCmd($command,$chat_id){ //добавление промежуточной команды к вводу пользователя        
        Command::updateOrCreate(['user_id'=>$chat_id],['command'=>$command]);        
    }
    public function getCmd($command,$chat_id) { //получение сохраненной промежуточной команды (той, которую сохраняет addCmd)        
        $cmd = Command::where('user_id',$chat_id)->first();
        if($cmd) {
            $res = $cmd->command.$command;
            $this->cmdTime = $cmd->created_at;
            $cmd->delete();
            return $res;
        }
        return $command;
    }
    public function getApi():TelegramApi {
        if(!$this->api) $this->api = new TelegramApi($this->token);
        return $this->api;
    }
    public function findCommandKey($cmd) {
        $cmdarr=json_decode(file_get_contents($this->commandFile),true);
        if(array_key_exists($cmd,$cmdarr)) return $cmd;          
        return false;
    }
    public function findCommand($cmd) { //поиск команды в файле команд редактор бота        
        $ckey = $this->findCommandKey($cmd);
        if($ckey) {
            $cmdarr=json_decode(file_get_contents($this->commandFile),true);
            return $cmdarr[$ckey];
        }
        return false;
    }
    protected function messParams() {
        return ['#nickname'];
    }
    protected function paramReplacer($messText, $params=null) {
        $params = $params ?? [];
        foreach($params as $k=>$v) {
            $messText = str_replace('#'.$k,$v,$messText);
        }
        return $messText;
    }
    public function getResObj($answ_arr,$i,$chat_id="",$cmd="",$replaceParams=null) { 
        //строим объект сообщения на основе json-параметра       
        $res=[];
        $res['parse_mode']="HTML";
        if (isset($answ_arr[$i]['text'])) {		            
            $res['text'] = $answ_arr[$i]['text'];        
            $res['text'] = $this->paramReplacer($res['text'],$replaceParams);            
        }		
        if (isset($answ_arr[$i]['innercmd'])) {
            $res['innercmd'] = $answ_arr[$i]['innercmd'];
        }
        if (isset($answ_arr[$i]['photo'])) {
            $res['photo'] = $answ_arr[$i]['photo'];
            if (!file_exists($res['photo'])) {
                $res['photo'] = $this->imgFolder . $res['photo'];
            }				
        }
        if (isset($answ_arr[$i]['document'])) {
            $res['document'] = $answ_arr[$i]['document'];				
        }
        if (isset($answ_arr[$i]['keyboard'])) {
            $res['keyboard'] = $answ_arr[$i]['keyboard'];				
            if(strpos(json_encode($res['keyboard']),"FUNCTION")!==false) {
                $new_kb=[];
                for($i=0;$i<count($res['keyboard']);$i++) {						
                    if(strpos($res['keyboard'][$i][0],"FUNCTION")!==false) {
                        $arr=explode("(",$res['keyboard'][$i][0]);
                        $params=explode(",",str_replace(')','',$arr[1]));
                        $func=array_shift($params);							
                        $new_kb = array_merge($new_kb, $func($params,$chat_id));							
                    }
                    else $new_kb[]=$res['keyboard'][$i];
                }					               
                $res['keyboard']=$new_kb;
            }            
        }
        if (isset($answ_arr[$i]['inline'])) {
            $res['inline_keyboard']['inline_keyboard'] = $answ_arr[$i]['inline'];
        }
        if(isset($answ_arr[$i]['checkinline'])) { //выборная клавиатура
            $this->addCmd("checkin&{$cmd}&{$i}&",$chat_id);
            $res['inline_keyboard']['inline_keyboard'] = $answ_arr[$i]['checkinline'];
            $res['chekin']=1; //флаг для внешней функции
        }
        return $res;
    }
    public function __construct(string $token,$botId=0, string $imgFolder="img/")
    {  
        $this->token = $token;
        $this->botId = $botId;
        $this->imgFolder = $imgFolder;
        $this->commandFile = "../resources/json/commands.json";
        if(!file_exists($this->commandFile)) $this->commandFile = "resources/json/commands.json";
    }
    /*
    public function botData() {
        if(!$this->botData) {
            $this->botData = $this->db->selectOne("select * from {$this->dbPrefix}bots where id={$this->botId}");
        }
        return $this->botData;
    }
    */
    public function setUserMessageIdIfGameIsActive($user_id, $message_id)
    {
        $user = GameUser::where('user_id', $user_id)->where('is_active', 1)->where('message_id', '!=', $message_id)->first();
        if($user) {
            $user->update(['message_id' => $message_id]);
        }
    }

    public function prepareAnswer($command,$chat_id,$callback_id=false,$hookUpdate=[]) {        
        $command = $this->getCmd($command,$chat_id);     
        $answ_arr = $this->findCommand($command);        
        $cmdarr = explode("&",$command);
        //Log::channel('daily')->info("answ_arr: ".print_r($answ_arr,true));
        if(!$answ_arr) return null;
        for ($i = 0; $i < count($answ_arr); $i++) { //лента
			$res = $this->getResObj($answ_arr,$i,$chat_id,$cmdarr[0]);
			if(isset($deleteprev)) $res['deleteprev']=$deleteprev;
            if(isset($res['innercmd'])) $this->addCmd($res['innercmd'], $chat_id);
			$fres[] = $res;
		}
        return $fres;        
    }
    public function editInlineMessageText($inline_message_id, $text, $reply_markup=null) {
        $telegram = $this->getApi();
        $params = [
            'inline_message_id'=>$inline_message_id,
            'text'=>$text
        ];
        if($reply_markup) $params['reply_markup'] = $reply_markup;
        $params['parse_mode'] = 'HTML';
        try {
            $telegram->editMessageText($params);
        }
        catch(Exception $e) {
            Log::error('editMessageText = '.$e->getMessage());
        }        
    }
    public function editMessageText($chat_id,$message_id, $text, $reply_markup=null) {
        $telegram = $this->getApi();
        $params = [
            'chat_id'=>$chat_id,
            'message_id'=>$message_id,
            'text'=>$text
        ];
        if($reply_markup) $params['reply_markup'] = $reply_markup;
        $params['parse_mode'] = 'HTML';
        try {
            $telegram->editMessageText($params);
        }
        catch(Exception $e) {
            Log::error('editMessageText = '.$e->getMessage());
        }        
    }
    public function sendAnswer($ans_arr,$user_id) {
        $telegram = $this->getApi();
        $mesjs = null;
        for($i=0;$i<count($ans_arr);$i++){
            $ans=$ans_arr[$i];
            $reply = $ans['text'];		
            $sm=[ 'chat_id' => $user_id, 'text' => $reply, 'caption'=>$reply];	
            //---------------------
            $sm['parse_mode'] = 'HTML';     
            if(isset($ans['not_file'])) $sm['not_file'] = $ans['not_file'];
            if(array_key_exists('inline_keyboard',$ans)) {					
                $keyboard=$ans['inline_keyboard'];
                $replyMarkup = json_encode($keyboard); 	   
                $sm['reply_markup'] =$replyMarkup;
            }
            else if(array_key_exists('webinline_keyboard',$ans)) {					
                $kbd = $ans['webinline_keyboard'];
                $inlineLayout = [];
                for($ki = 0;$ki<count($kbd);$ki++) {
                    for($kj = 0;$kj<count($kbd[$ki]);$kj++) {
                        $inlineLayout[$ki][] = Keyboard::inlineButton($kbd[$ki][$kj]);                        
                    }
                }
                $rmarkup = Keyboard::make()->inline();
                foreach($inlineLayout as $row) {
                    $rmarkup = $rmarkup->row($row);
                }               
                $sm['reply_markup'] = $rmarkup;
            }       
            else if(array_key_exists('keyboard',$ans)){
                $keyboard=$ans['keyboard'];
                $reply_markup = $telegram->replyKeyboardMarkup([ 'keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);
                $sm['reply_markup']=$reply_markup;
            }	
            try {			
                if(array_key_exists('photo',$ans)) {
                    $sm['photo']=$ans['photo'];
                    $mesjs = $telegram->sendPhoto($sm);
                }
                else if (array_key_exists('document', $ans)) {
                    $sm['document'] = $ans['document'];
                    $mesjs = $telegram->sendDocument($sm);
                }
                else if (array_key_exists('video', $ans)) {
                    $sm['video'] = $ans['video'];
                    $mesjs = $telegram->sendVideo($sm);
                }
                else $mesjs = $telegram->sendMessage($sm);           
            }
            catch(Exception $e) {
                Log::error($e->getMessage());
            }
            if(isset($ans['saver']) && $mesjs) {                
                $ans['saver']->saveMessageId($mesjs->message_id);    
                if(isset($ans['pin']) && $ans['pin'] == 1) {
                    $telegram->pinChatMessage(['chat_id'=>$user_id, 'message_id'=>$ans['saver']->message_id]);                    
                }
            }
        } 
        
        
       // Log::channel('daily')->info(print_r($mesjs,true));
        return $mesjs; 
    }
    public function prepareGroupAnswer($command,$group_id,$from,$hookUpdate) {
        return [];
    }
    public function updateLocale() {
        
    }
    public function run() { //метод, запускаемый в индексе
        $telegram = $this->getApi();
        $result = $telegram->getWebhookUpdate();
        
        if(isset($result['pre_checkout_query'])) {
            $telegram->answerPreCheckoutQuery(['pre_checkout_query_id'=>$result['pre_checkout_query']['id'],'ok'=>true]);
            return;
        }
        if(isset($result["inline_query"])) { //отправить inline ответ
            $anwer_params=['inline_query_id'=>$result["inline_query"]['id']];
            $anwer_params['results']=$this->getInlineAnswer($result['inline_query']['query'],$result['inline_query']["from"]["id"],$result);
            $anwer_params['cache_time']=0;
            //Log::channel('daily')->info('answerparams = '.json_encode($anwer_params));
            $telegram->answerInlineQuery($anwer_params);
            echo "OK";
            exit;
        }

        $chat_id = $result["message"]["chat"]["id"] ?? $result['callback_query']['message']['chat']['id'] ?? null;  
              
        if(!$chat_id) {
            if(isset($result['my_chat_member']) && isset($result['my_chat_member']['chat']['id'])) {
                $chat = $result['my_chat_member']['chat'];
                $from = $result['my_chat_member']['from'];
                $chat_id = $chat['id'];
                if($chat_id < 0) { //это группа
                    $botGroup = BotGroup::where('id',$chat_id)->first();
                    if(!$botGroup) {    
                        $botGroup = BotGroup::create(['id'=>$chat_id,'title'=>$chat['title'],'group_type'=>$chat['type'],'who_add'=>$from['id']]);
                        $user = $from['id'].' '.$from['first_name'].' '.($from['last_name'] ?? '').(($from['username'] ?? null) ? ' @'.$from['username'] : '');
                        $mess['text'] = "Добавлена группа {$chat['title']} пользователем $user";
                        foreach($this->admins as $adminId) {
                            $this->sendAnswer([$mess],$adminId);
                        }
                    }
                }

            }
            return 'ok';
        }
        $language_code = $result["message"]["from"]["language_code"] ?? $result['callback_query']["from"]["language_code"] ?? 'ru';  
        app()->setLocale($language_code);
        $this->updateLocale();

        if(isset($result['message']['successful_payment'])) {
            $tstarsApi = new TelegramStarsApi();
            $tstarsApi->paymentSuccess($result['message']['successful_payment']);     //,$chat_id
            return 'ok';
        }
        $cmd = $result["message"]["text"] ??  $result['callback_query']['data'] ?? $this->getCmd("",$chat_id);
        $callbackMessageId = $result["callback_query"]["message"]["message_id"] ?? $result['message']['message_id'] ?? null;
        if($chat_id < 0 && isset($result["message"]["new_chat_member"])) {
            $newChm = $result["message"]["new_chat_member"];
            ChatMember::updateOrCreate(
                [
                    'member_id'=>$newChm['id'],'group_id'=>$chat_id
                ],
                [
                    'username'=> $newChm['username'] ?? '',
                    'first_name'=> $newChm['first_name'] ?? null,
                    'last_name'=> $newChm['last_name'] ?? null,
                    'is_bot'=>($newChm['is_bot'] ?? false) ? 1 : 0,
                    'is_premium'=>($newChm['is_premium'] ?? false) ? 1 : 0,
                ]
            );
            return 'ok';
        }
        if($chat_id < 0 && isset($result["message"]["from"])) {  //сообщение отправлено в группе            
            if(WarningWord::testword($cmd, $chat_id)) {
                $warn = UserWarning::create(['user_id'=>$result["message"]["from"]['id'],'group_id'=>$chat_id,'warning_id'=>1]);
               // Log::channel('daily')->info("MUTE: ".$result["message"]["from"]['id'].' : '.$chat_id);
                try {
                    $this->mute($result["message"]["from"]['id'], $chat_id);
                }
                catch(Exception $e) {
                    Log::error($e->getMessage());
                }
                try {
                    $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                }
                catch(Exception $e) { }
                $warn->refresh();
                $mess['text'] = "Пользователь ".Game::userUrlName($warn->user)." получил MUTE. \nПричина: ".$warn->warning;
                $mess['inline_keyboard']['inline_keyboard'] = [[['text'=>"Размутить","callback_data"=>"unmute&".$result["message"]["from"]['id']]]];
                $this->sendAnswer([$mess],$chat_id);
                return 'ok';
            }
            if(isset($result["message"]['entities'])) {
                foreach($result["message"]['entities'] as $entity) {
                    if($entity['type'] == 'url') {
                        UserWarning::create(['user_id'=>$result["message"]["from"]['id'],'group_id'=>$chat_id,'warning_id'=>4]);                     
                        try {
                            $this->mute($result["message"]["from"]['id'], $chat_id);
                            $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                        }
                        catch(Exception $e) {
                            Log::error($e->getMessage());
                        }                        
                        return 'ok';
                    }
                }
            }

            $game = GameModel::where('group_id',$chat_id)->where('status',1)->first();
            if($game && !in_array($cmd,['/pause','/stop','/stop@'.config('app.bot_nick'),'/leave','/leave@'.config('app.bot_nick')]) && strpos($cmd,'/kick') === false) {//запрет писать
                if($result["message"]["from"]["id"] == '376753094') return 'ok';
                $sleep_write = Setting::groupSettingValue($game->group_id,'sleep_write');
                $nogamer_write = Setting::groupSettingValue($game->group_id,'nogamer_write');
                $nogamer_admin_write = Setting::groupSettingValue($game->group_id,'nogamer_admin_write');

                $gamer = GameUser::where('game_id',$game->id)->where('user_id',$result["message"]["from"]["id"])->first();
                if(!$gamer) {
                    if($nogamer_write==='yes') return 'ok';
                    if($nogamer_admin_write==='yes' && Game::hasRightToStart($result["message"]["from"]["id"],$chat_id)) return 'ok';
                    try {
                        $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                    }
                    catch(Exception $e) {}
                    return 'ok';
                }

                if($gamer && $game->isNight() && $sleep_write!=='yes') {
                    try {
                        $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                    }
                    catch(Exception $e) {
                        Log::error("delete error: ".json_encode(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']])." !".$e->getMessage());
                    }
                    return 'ok';
                }
                
                
                if($gamer && !$gamer->isActive()) {
                    $die_write = Setting::groupSettingValue($game->group_id,'die_write');
                    if($die_write!=='yes') {
                        try {
                            $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                        }
                        catch(Exception $e) {
                            Log::error("Попытка удалить сообщение не играющий: ".$e->getMessage());
                        }
                        return 'ok';
                    }
                }
                if($gamer && $gamer->isActive()) {
                    if(!GamerFunctions::isCanMove($gamer) && $sleep_write!=='yes') { //не может делать ход и писать в чат
                        try {
                            $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                        }
                        catch(Exception $e) {
                            Log::error("Попытка удалить сообщение спящий: ".$e->getMessage());
                        }
                        return 'ok';
                    }
                }
                $set_mediafiles = Setting::groupSettingValue($chat_id,'mediafiles');
                if($set_mediafiles!=='yes') {  //медиа запрещены. Проверяем, есть ли медиа
                    $message = $result["message"];
                    if(isset($message['photo']) || isset($message['video'])) {                                        
                        try {
                            $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$message['message_id']]);
                            $this->mute($message["from"]["id"],$chat_id,60);  //мут на минуту
                        }
                        catch(Exception $e) { }
                    }
                }
                $gameParams = GamerParam::gameParams($game);
                
                //не пересылка ли это?
                $reply_from_bot = Setting::groupSettingValue($chat_id,'reply_from_bot');
                $warn_for_resend = Setting::groupSettingValue($chat_id,'warn_for_resend');
                if($reply_from_bot!=='yes') {
                    if(isset($result["message"]['forward_from']) && isset($result["message"]['forward_from']['is_bot'])
                    && $result["message"]['forward_from']['is_bot']) {
                        try {
                            $this->getApi()->deleteMessage(['chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                        }
                        catch(Exception $e) {
                            Log::error("Не смог удалить пересылку: ".$e->getMessage());
                        }
                        if($warn_for_resend==='yes') {
                            UserWarning::create(['user_id'=>$result["message"]["from"]['id'],'group_id'=>$chat_id,'warning_id'=>3]);
                        }
                        try {
                            $this->mute($result["message"]["from"]['id'],$chat_id);
                        }
                        catch(Exception $e) {
                            Log::error($e->getMessage());
                        }
                    }
                }
                return 'ok';                
            }
            $from = $result["message"]["from"];           
            $ans_arr = $this->prepareGroupAnswer($cmd,$chat_id,$from,$result);
            try {
                if($ans_arr) {
                   $result = $this->sendAnswer($ans_arr,$chat_id);                                  
                }
            }
            catch(Exception $e) {
                Log::error('botrun. sendAsnwer: '.$e->getMessage());
            }
            return 'ok';
        }
        else if($chat_id < 0 && isset($result["callback_query"])) {  //inline кнопка в группе
            $from = $result["callback_query"]['from'];            
            $ans_arr = $this->prepareGroupAnswer($cmd,$chat_id,$from,$result["callback_query"]['message']);
            return 'ok';
        }
        //Log::channel('daily')->info("tghook: ".json_encode($result,JSON_UNESCAPED_UNICODE));
        if($chat_id < 0) return;
        //Log::channel('daily')->info("tghook: ".json_encode($result,JSON_UNESCAPED_UNICODE));
        //дальше сообщения в чате
        $cmd_arr = [];
        if(strpos($cmd,'/start')!==false) {
            $cmd_arr = explode(' ',$cmd);   
            if(isset($cmd_arr[1]) && strpos($cmd_arr[1],'game_')!==false) $cmd_arr[1]=null; //чтоб не воспринять начало игры как реф. ссылку            
        }
        $tgUser = $this->userStart($chat_id,$result,$cmd_arr[1] ?? null);                    
        $ans_arr=$this->prepareAnswer($cmd, $chat_id,$callbackMessageId,$result);   //массив
        if($ans_arr) {          
            try {
                //Log::channel('daily')->error('sendAsnwer 1: '.print_r($ans_arr,true));
                $this->sendAnswer($ans_arr,$chat_id);                                  
            }
            catch(Exception $e) {
                Log::error('botrun. sendAsnwer: '.$e->getMessage());
            }            
        }
        else if($chat_id > 0 && isset($result["message"]['message_id'])) {
            //активен ли как игрок
            $gamer = GameUser::where('user_id',$chat_id)->where('is_active',1)->first();
            if($gamer) {
                //$gamer->game_id
                $uParticipant = UnionParticipant::where(['gamer_id'=>$gamer->id,'game_id'=>$gamer->game_id])->first();
                if($uParticipant) { //состоит в союзе
                    $mess = ['text'=>'<b>'.Game::userUrlName($gamer->user).':</b> '.$cmd];
                    $participants = UnionParticipant::with('gamer')->where('union_id',$uParticipant->union_id)->get();
                    foreach($participants as $particip) { //послать всем кроме себя
                        if($particip->id == $uParticipant->id) continue;
                        if(!$particip->gamer || !$particip->gamer->isActive()) continue;
                        try {                            
                            $this->sendAnswer([$mess], $particip->gamer->user_id);
                            /*
                            $this->getApi()->copyMessage(['chat_id'=>$particip->gamer->user_id,
                            'from_chat_id'=>$chat_id, 'message_id'=>$result["message"]['message_id']]);
                            */
                            //отправляем копию всем, кто в союзе
                        }
                        catch(Exception $e) {
                            Log::error('botrun. sendAsnwer copyMessage: '.$e->getMessage());
                        }           
                        usleep(35000);
                    }
                }
            }
        }
        echo "OK";
    }
    public function getInlineAnswer($command,$user_id,$updates){ //функция ответа на нажатие строки в бургере       
        /*
       Log::channel('daily')->info(" $command , $user_id ");
        $res=['type'=>'article','id'=>0,'caption'=> "caption"];
        $res['title'] = "Скрыть список";
        $res['input_message_content']['message_text'] = "Список свернут. Перейдите к следующему действию";
        $res['reply_markup']['inline_keyboard'] = [[["text"=>"Список товаров","switch_inline_query_current_chat"=>$command]]];
      
        $query = Product::where('enabled',1)->where('servertype','old');
        if(!empty($command)) $query = $query->where('title','like','%'.$command.'%');
        $products = $query->orderByDesc('raiting')->limit(30)->get();
        foreach($products as $product) {                     
           
            $res=['type'=>'article','id'=>$product->id,'caption'=> "caption",'parse_mode'=>'HTML']; 
            $res['input_message_content']['message_text'] = $product->title.' '.$product->catalogPrice().'₽';
            $res['thumb_url'] = $product->botImage();            
            $res['reply_markup']['inline_keyboard'] = [[["text"=>"В корзину","callback_data"=>"buyprod_".$product->id]]];
            $res['title'] = $product->title.' $'.$product->price;
            $res['description'] = "Цена: ".$product->catalogPrice().'₽';            
            $fres[] = $res;
        }
        return json_encode($fres,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        */
        	
    }
}