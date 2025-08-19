<?php

namespace Algsoft\Etl22bot;

trait SetTimer {
    protected $managers = [];
    public function selectDateMessage() {
        $res['text'] = "Выберите дату";
        $mom = time();
        $r = $this->db->selectResource("select * from {$this->dbPrefix}schedule where bot_id={$this->botId} and moment>{$mom} and user_id is null order by moment");
        $kb = [];
        $dmoms = [];
        while($line = mysqli_fetch_assoc($r)) {
            $dmom = date("d.m.Y",$line['moment']);
            $dm2 = date("Y-m-d",$line['moment']);
            if(in_array($dmom,$dmoms)) continue;
            $dmoms[] = $dmom;
            $kb[] = [['text'=>$dmom, 'callback_data'=>"selectTime&".strtotime($dm2)]];            
        }
        $res['inline_keyboard']['inline_keyboard'] = $kb;
        return $res;        
    }
    public function selectTimeMessage($startday) {
        $endday = $startday+60*60*24;
        $res['text'] = "<b>Выбранная дата ".date('d.m.Y',$startday)."</b>\n\nВыберите время";
        $mom = time();
        $r = $this->db->selectResource("select * from {$this->dbPrefix}schedule where bot_id={$this->botId} and moment>{$startday} and moment<{$endday} and user_id is null order by moment");
        $kb = [];
        $dmoms = [];
        while($line = mysqli_fetch_assoc($r)) {
            $dmom = date("H:i",$line['moment']);            
            if(in_array($dmom,$dmoms)) continue;
            $dmoms[] = $dmom;
            $kb[] = [['text'=>$dmom, 'callback_data'=>"selectTimeFin&{$line['moment']}"]];            
        }
        $res['inline_keyboard']['inline_keyboard'] = $kb;
        return $res;        
    }
    public function recordOnTime($user_id,$moment) {
        $isRec = $this->db->selectOne("select * from {$this->dbPrefix}schedule where bot_id={$this->botId} and moment={$moment} and user_id is not null");
        if($isRec) {
            $res['text'] = "Похоже выбранное время уже занято. Попробуйте выбрать время снова";
            $startday = strtotime(date("Y-m-d",$moment));
            $stm = $this->selectTimeMessage($startday);
            $res['inline_keyboard'] = $stm['inline_keyboard'];
            return $res;
        }
        $this->db->execSql("update {$this->dbPrefix}schedule set user_id='$user_id' where bot_id={$this->botId} and moment={$moment}");
        $res['text'] = "Вы успешно записались на консультацию.\nДата/время консультации <b>".date("d.m.Y H:i",$moment)."</b>\nПримерно за 15 минут перед консультацией вам будет направлена ссылка на ZOOM";
        return $res;
    }
    public function fillTimerCron() {        
        $start = strtotime(date("Y-m-d 10:00"));
        $end = $start + 60*60*24*7;
        for($mom = $start;$mom<=$end;$mom+=2.5*60*60) {
            if(date('H',$mom)<10) {
                $mom = strtotime(date("Y-m-d 10:00",$mom));
            }
            if(date('H',$mom)>20) continue;
            $isMom = $this->db->selectOne("select * from {$this->dbPrefix}schedule where bot_id={$this->botId} and moment={$mom}");
            if(!$isMom) {
                $this->db->insert("{$this->dbPrefix}schedule",[
                    'bot_id'=>$this->botId,
                    'moment'=>$mom
                ]);
            }
        }
    }
    public function actionNotification() {
        $mom = time()+3*60*60; //корректируем на 3 часа (МСК = +3)
        $finmom = $mom+20*60;
        $r = $this->db->selectResource("select * from {$this->dbPrefix}schedule where bot_id={$this->botId} and moment>{$mom} and moment<{$finmom} and user_id is not null");
        $api = $this->getApi();
        while($line = mysqli_fetch_assoc($r)) {
            foreach($this->managers as $manager_id) {            
                $sm['chat_id'] = $manager_id;
                $sm['text'] = $this->getManagerNotificationText($line);
                $api->sendMessage($sm);
            }  
            $sm['chat_id'] = $line['user_id'];
            $sm['text'] = $this->getClientNotificationText($line);
            $api->sendMessage($sm);          
        }
    }

    abstract public function getManagerNotificationText(array $scheduleRecord);
    abstract public function getClientNotificationText(array $scheduleRecord);

    public function prepareAnswer($command, $chat_id, $callback_id = false)
    {        
        if($command == 'selectdate') {
            return [$this->selectDateMessage()];
        }
        if(strpos($command,'selectTime&')!==false) {
            $cmd_arr = explode("&",$command);
            return [$this->selectTimeMessage($cmd_arr[1])];
        }
        if(strpos($command,'selectTimeFin&')!==false) {
            $cmd_arr = explode("&",$command);
            return [$this->recordOnTime($chat_id,$cmd_arr[1])];
        }
        return parent::prepareAnswer($command, $chat_id, $callback_id);
    }
}