<?php
namespace App\Modules\Game\Roles;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Models\DeactivatedCommand;

trait Student {
    public static function student_fak_select($params) {
        //advokat, mafdoc, mafi
        $results = ['advokat'=>'Адвокат','mafdoc'=>'Подпольный доктор', 'mafi'=>'Мафия'];
        $gamer = GameUser::where('user_id', $params['user_id'])->where('is_active', 1)->first();
        if($gamer) {
            GamerParam::saveParam($gamer,'student_fak',$params['cmd_param']);
            DeactivatedCommand::create(['game_id'=>$gamer->game_id,'command'=>'student_fak_select']);
            return $results[$params['cmd_param']];
        }  
        return '';
    }
}
