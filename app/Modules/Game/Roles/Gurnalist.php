<?php
namespace App\Modules\Game\Roles;

use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\ActiveBaf;
use App\Models\GamerParam;
use App\Models\RoleAction;
use App\Modules\Game\Game;

trait Gurnalist {
    public static function gurnalist_select($params) {
        self::gamer_set_move($params, 'gurnalist_select', 'gurnalist_select_itog');
    }    
    public static function gurnalist_select_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $gurnalist = GameUser::where('game_id',$game->id)->where('role_id',24)->first();
        if(!isset($gameParams['gurnalist_select'])) return null; //ошибочный запуск функции
        $victim = GameUser::where('id',$gameParams['gurnalist_select'])->first(); //потерпевший
        if(!$victim) return null; //ошибочный запуск функции
        
        if(!$gurnalist || !self::isCanMove($gurnalist)) {  //обездвижен           
            return;
        }        
        $gamerTxt = "Ты провел интервью с ".Game::userUrlName($victim->user)." но не узнал ничего важного...";
        if($victim->role_id == 4) { //не видит гостей комиссара Пуаро
            $message = ['text'=>$gamerTxt];       
            Game::message(['message'=>$message,'chat_id'=>$gurnalist->user_id]);
            return;
        }
        $viewActions = [
            'advokat_select','blacklady_select_gamer','ciganka_select','doctor_treat','dvulikiy_select',
            'dvulikiy_kill','shutnik_select','krasotka_select','lubovnik_select','mafdoctor_treat',
            'mafiya_select','manjak_select','oderjim_select','poetfind','poet_butylka',
            'puaro_check','puaro_kill','sutener_select','vedma_lechit','vedma_morozit','vedma_kill','karleone_select'
        ];        
        $texts = [];
        foreach($viewActions as $action) {
            $gamerTxt = null;
            if(isset($gameParams[$action]) && $gameParams[$action] == $gameParams['gurnalist_select']) {
                $roleAction = RoleAction::where('action', $action)->first();   
                $role = $roleAction->role;
                $kogoUvidel = GameUser::where(['game_id'=>$game->id,'role_id'=>$roleAction->role_id])->first();
                if($kogoUvidel) {
                    $gamerTxt = "Ты провел интервью с ".Game::userUrlName($victim->user)." и узнал, что "
                    .Game::userUrlName($kogoUvidel->user).' - '.$role;
                    //если найдется баф, подменим текст
                    $activeBafs = ActiveBaf::with('baf')->where(['game_id'=>$game->id,'user_id'=>$kogoUvidel->user_id,'is_active'=>1])->get();
                    foreach($activeBafs as $activeBaf) {                      
                        $class = "\\App\\Modules\\Game\\Bafs\\".$activeBaf->baf->baf_class;
                        $actbaf = new $class($activeBaf);
                        $result = $actbaf->visit_role($kogoUvidel);
                        if($result) {                      
                            $gamerTxt = "Ты провел интервью с ".Game::userUrlName($victim->user).".Ты не смог рассмотреть лицо гостя...";
                            break;
                        }
                    }
                }
                //break;
            }
            if($gamerTxt) $texts[] = $gamerTxt;
        }
        if($texts) {
            $message = ['text'=>implode("\n",$texts)];     
            Game::message(['message'=>$message,'chat_id'=>$gurnalist->user_id]);
        }
        
        /*
        if(isset($gameParams['shutnik_poet']) && !self::isTreated($victim->id, $game) ) {
            self::user_kill($poet->id, $victim->id); //убивает вдохновением...
        }*/
    }
}