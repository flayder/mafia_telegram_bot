<?php
namespace App\Modules\Game\Roles;

use App\Models\GameRole;
use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\NightFunction;
use App\Models\UnionParticipant;

trait BlackLady {
    public static function blacklady_selectuser($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'blacklady_select_gamer',$params['cmd_param']);
            $gamer = GameUser::where('id',$params['cmd_param'])->first();
            GamerParam::saveParam($gamer,'blacklady_selectgamerusername',Game::userUrlName($gamer->user));
        }        
    }
    public static function blacklady_selectrole($params) {
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        $gameParams = GamerParam::gameParams($gamer->game);
        if(!isset($gameParams['blacklady_select_gamer'])) return null;
        if($gamer) {
            GamerParam::saveParam($gamer,'blacklady_select_role',$params['cmd_param']);
            $role = GameRole::where('id',$params['cmd_param'])->first();            
            $check = GameUser::where('id',$gameParams['blacklady_select_gamer'])->first();
            if($check) {
                $message = ['text'=>"🤵🏻‍♀️ Черная леди готовит проверку {$check->user} на роль {$role}"];
                UnionParticipant::unionGamerMessage($gamer,$message);
                NightFunction::push_func($gamer->game, 'blacklady_itog');
            }
        }        
    }
    public static function blacklady_itog($game) {
        $gameParams = GamerParam::gameParams($game);
        $blacklady = GameUser::where('role_id', 18)->where('game_id', $game->id)->first();
        if(!self::isCanMove($blacklady)) return null;

        $role = GameRole::where('id',$gameParams['blacklady_select_role'] ?? 0)->first();            
        $check = GameUser::where('id',$gameParams['blacklady_select_gamer'] ?? 0)->first();

        if($role && $check) {
            if($check->role_id == $role->id) $message = ['text'=>"✅ ".Game::userUrlName($check->user)." - $role"];
            else $message = ['text'=>"❌ ".Game::userUrlName($check->user)." - $role"];
            GamerParam::saveParam($blacklady,"blacklady_check_result",$message['text'],false);
            UnionParticipant::unionGamerMessage($blacklady,$message,false);
        }
    }
    public static function isBlacklady($game) {
        return GameUser::where('role_id', 18)->where('game_id', $game->id)->first();
    }
    public static function allBlackLadyChecks($game) {
        $savedChecks = GamerParam::where('game_id',$game->id)->where('param_name','blacklady_check_result')->orderBy('night')->get();
        $messText = "";
        if($savedChecks->count()>0) {
            $messText = "\n\n<b>Проверки:</b>";
            foreach($savedChecks as $savedcheck) {
                $messText .= "\n".$savedcheck->param_value;
            }
        }
        return $messText;
    }

}