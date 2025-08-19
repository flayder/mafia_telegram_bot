<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\GamerParam;
use App\Models\RoleAction;
use App\Modules\Game\Game;

trait TvPresenter {
    public static function televed_select($params) {
        self::gamer_set_move($params,'televed_select','televed_select_itog');
    }  
    public static function televed_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);        
        $televed = GameUser::where('game_id', $game->id)->where('role_id', 29)->first();
        if(!isset($gameParams['televed_select'])) return null; //ошибочный запуск функции        
        if(!$televed || !self::isCanMove($televed)) return null; 

        $victim = GameUser::where('id',$gameParams['televed_select'])->first(); //потерпевший
        if(!$victim) return null;

        $gamerTxt = "Сегодня ночью тебе не удалось ничего узнать…";
        
        $viewActions = [
            'advokat_select','blacklady_select_gamer','ciganka_select','doctor_treat','dvulikiy_select',
            'dvulikiy_kill','shutnik_select','krasotka_select','lubovnik_select','mafdoctor_treat',
            'mafiya_select','manjak_select','oderjim_select','poetfind','poet_butylka',
            'puaro_check','puaro_kill','sutener_select','vedma_lechit','vedma_morozit','vedma_kill','karleone_select'
        ];                  
        $uvidelMess = [];
        foreach($viewActions as $action) {
            if(isset($gameParams[$action]) && $gameParams[$action] == $gameParams['televed_select']) {
                $roleAction = RoleAction::where('action', $action)->first();   
                $role = $roleAction->role;
                $kogoUvidel = GameUser::where(['game_id'=>$game->id,'role_id'=>$roleAction->role_id])->first();
                if($kogoUvidel) {
                    $uvidelStr = ''.$role;                    
                    //если найдется баф, подменим текст
                    $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$game->id,'user_id'=>$kogoUvidel->user_id,'is_active'=>1])->get();
                    foreach($activeBafs as $activeBaf) {                      
                        $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
                        $actbaf = new $class($activeBaf);
                        $result = $actbaf->visit_role($kogoUvidel);
                        if($result) {                      
                            $uvidelStr = null;                            
                            break;
                        }
                    }
                    if($uvidelStr) $uvidelMess[] = $uvidelStr;
                }                
            }
        }
        if($uvidelMess) {
            $gamerTxt = "Какая удача! Сегодня ты подсмотрел за ".Game::userUrlName($victim->user)." и увидел там ".implode(', ',$uvidelMess);
            $victimMess = ['text'=>"📺Телеведущий провел с тобой репортаж! И узнал твоих гостей этой ночью.."];
            $groupMess = ['text'=>"📺Телеведущий провел репортаж с ".Game::userUrlName($victim->user).", и узнал что этой ночью у него в гостях был ".implode(', ',$uvidelMess)];
            Game::message(['message'=>$groupMess,'chat_id'=>$game->group_id]);
        }
        else {
            $victimMess = ['text'=>"📺Телеведущий провел с тобой репортаж! Но не заметил ничего подозрительного.."];
        }
        $message = ['text'=>$gamerTxt];     
        Game::message(['message'=>$message,'chat_id'=>$televed->user_id]);
        Game::message(['message'=>$victimMess,'chat_id'=>$victim->user_id]);


        //сообщение для цыганки
        self::ciganka_message($victim, $victimMess['text']);
    }  

}