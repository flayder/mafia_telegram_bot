<?php
namespace App\Modules\Game\Bafs;

use App\Models\GameUser;

class Shield extends BaseBaf {
    public function puaro_check(GameUser $gamer)
    {        
        return null;
    }
    public function kill(GameUser $gamer)
    {
        if($this->execBaf()) {
            //сразу отключаем. использование единоразовое
            $this->activeBaf->is_active = 0;
            $this->activeBaf->save();
            return ['user_mess'=>'Вас хотели убить, но щит спас вас. Берегите себя..',
            'group_mess'=>'<i>❤️‍🩹 Кому-то повезло этой ночью. Это был всего лишь щит...</i>'];
        }
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
    public static function message() {
        return ['user_mess'=>'Вас хотели убить, но щит спас вас. Берегите себя..',
            'group_mess'=>'<i>❤️‍🩹 Кому-то повезло этой ночью. Это был всего лишь щит...</i>'];
    }
}