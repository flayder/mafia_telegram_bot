<?php

namespace App\Modules\Bot;

use App\Models\DialogLine;
use App\Models\DialogMessage;
use Exception;
use Illuminate\Support\Facades\DB;

trait Dialog {
    
    public function selectManager() {
        $rait = DB::selectOne("select dmng.manager_id, count(*) as cnt from dialog_managers as dmng left join 
        dialog_lines as dln
        on dmng.manager_id=dln.manager_id group by dmng.manager_id order by cnt");
        return $rait->manager_id;
    }
    public function initDialog($user_id,$manager_id,$dialog_id) {
        $this->addCmd(SysConst::CMD_DIALOG_WRITE.$dialog_id."&2&",$user_id);
        $res['text'] = "Ожидайте, менеджер скоро ответит Вам. Пока вы ждете, можете сформулировать суть вопроса. 
        В режиме ожидания пожалуйста отправляйте ТОЛЬКО ТЕКСТ. Когда начнется диалог, можно будет отправлять произвольные сообщения, в том числе картинки и файлы";
        $res['keyboard'] = [[SysConst::CMD_DIALOG_END]];
        $sm = ['chat_id'=>$manager_id];
        $sm['text'] = "Пользователь <a href='tg://user?id=$user_id'>{$this->userName($user_id)}</a> хочет связаться с менеджером";
        $keyboard['inline_keyboard'] = [
            [['text'=>"Присоединиться к диалогу",
            "callback_data"=>SysConst::CMD_DIALOG_MANAGER_START.$dialog_id
            ]]           
        ];
        $sm['reply_markup'] = json_encode($keyboard); 
        $sm['parse_mode'] = 'HTML';
        $this->getApi()->sendMessage($sm);
        return [$res];
    }
    public function createDialog($user_id,$manager_id=null) { 
        //создает диалог. Отправляет уведомление менеджеру. Переводит пользователя в состояние ожидания
        $manager_id = $manager_id ?? $this->selectManager();
        $dialog = DialogLine::create(['user_id'=>$user_id]);    
        return $this->initDialog($user_id,$manager_id,$dialog->id);
    }
    
    public function testDialogCommands($cmd,$user_id,$callback_id) {
        if(strpos($cmd,SysConst::CMD_DIALOG_END)!==false) {
            $arr_cmd = explode('&',$cmd);
            $dialog = DialogLine::where('id',$arr_cmd[1])->first();
            if($arr_cmd[2] == 1) { //это менеджер. Значит партнер - пользователь
                $partner = 'user_id';
            }
            else { //это пользователь. Значит партнер - менеджер
                $partner = 'manager_id';
            }
            if($dialog->$partner) {		
                $this->getCmd('',$dialog->$partner); //удалим команду для партнера
                $sm = ['chat_id'=>$dialog->$partner, 'text'=>"Диалог завершен"];
                $sm['reply_markup'] = $this->getApi()->replyKeyboardMarkup([ 'keyboard' => Bot::MAIN_MENU, 'resize_keyboard' => true, 'one_time_keyboard' => false ]);                
                try {		
				    $this->getApi()->sendMessage($sm);
                }
                catch(\Exception $e) {}
			}
            $dialog->finished=1;
            $dialog->save();
            $res['text'] = "Диалог завершен";
            $res['keyboard'] = Bot::MAIN_MENU;
            return [$res];
        }
        if(strpos($cmd,SysConst::CMD_DIALOG_CREATE)!==false) {
            //есть ли открытые диалоги у этого пользователя?
            $dialog = DialogLine::where(['user_id'=>$user_id,'finished'=>0])->first();
            if($dialog) {
                return $this->initDialog($user_id,$dialog->manager_id,$dialog->id);
            }
            else {
                return $this->createDialog($user_id);
            }
        }
        if(strpos($cmd,SysConst::CMD_DIALOG_MANAGER_START)!==false) {
            $arr_cmd = explode('&',$cmd);
            $dialog = DialogLine::where('id',$arr_cmd[1])->first();
            $dialog->manager_id=$user_id;
            $dialog->save();
            $this->addCmd(SysConst::CMD_DIALOG_WRITE.$dialog->id.'&1&',$user_id);
            $sm = ['chat_id'=>$dialog->user_id];
            $sm['text'] = "Менеджер присоединился к диалогу с Вами";
            $this->getApi()->sendMessage($sm);
            $res['text'] = "Вы начали диалог с пользователем";
            if($dialog->messages) {
                $res['text'] .= "\nСообщения пользователя до подключения менеджера:\n\n";
                foreach($dialog->messages as $msg) {
                    $res['text'] .="\n".$msg->message;
                }
            }
            $res['keyboard'] = [[SysConst::CMD_DIALOG_END]];
            return [$res];
        }
        if(strpos($cmd,SysConst::CMD_DIALOG_WRITE)!==false) {
            $arr_cmd = explode('&',$cmd);
            $dialog = DialogLine::where('id',$arr_cmd[1])->first();
            if($arr_cmd[2] == 1) { //это менеджер. Значит партнер - пользователь
                $partner = 'user_id';
            }
            else { //это пользователь. Значит партнер - менеджер
                $partner = 'manager_id';
            }
            $message = $arr_cmd[3] ?? 'file';
            DialogMessage::create(['message'=>$message,'author_id'=>$user_id,'author_type'=>$arr_cmd[2],'dialog_line_id'=>$arr_cmd[1]]);
            if($dialog->$partner) {		
                try {		
				    $this->getApi()->copyMessage(['chat_id'=>$dialog->$partner, 'from_chat_id'=>$user_id, 
                    'message_id'=>$callback_id]);
                }
                catch(\Exception $e) {}
			}
            $this->addCmd(SysConst::CMD_DIALOG_WRITE.$arr_cmd[1]."&{$arr_cmd[2]}&",$user_id);
			return [];
        }

        
        return false;
    }
}