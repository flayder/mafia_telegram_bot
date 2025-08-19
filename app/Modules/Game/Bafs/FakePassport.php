<?php
namespace App\Modules\Game\Bafs;

use App\Models\GameRole;
use App\Models\GamerParam;
use App\Models\GameUser;
use App\Modules\Bot\AppBot;

class FakePassport extends BaseBaf {
    public function puaro_check(GameUser $gamer)
    {
        if($this->execBaf()) {
            $bot = AppBot::appBot();
            $mess['text'] = "Комиссар Пуаро пришёл к вам, выберете, кем вы будете ";
            //живые миры с нужными ролями. Интересуют только роли живых
            $needGamers = GameUser::where('is_active',1)->whereIn('role_id',[1,3,6,7,8,9,10,11,12,13,14,15])->get()->all();
            $accessRoleIds  = array_column($needGamers, 'role_id');
            $roles = GameRole::whereIn('id',$accessRoleIds)->get()->all();
            if($roles) {
                $paramModel = GamerParam::saveParam($gamer,'puaro_view',$roles[0]->name);
                $mess['inline_keyboard'] = $bot->inlineKeyboard($roles,1,"fakepas&".$paramModel->id."&",false,'id','name');
                $bot->sendAnswer([$mess],$gamer->user_id);
                return ['sleep' => 7];
            }
        }
        return null;    //не осталось ролей. не получается применить        
    }
    public function kill(GameUser $gamer)
    {
        return null;
    }
    public function gallow(GameUser $gamer)
    {
        return null;
    }
    public function visit_role(GameUser $gamer)
    {
        return null;
    }
    public function shot(GameUser $gamer)
    {
        return null;
    }
    public function ciganka_view(GameUser $gamer)
    {
        return null;
    }

}