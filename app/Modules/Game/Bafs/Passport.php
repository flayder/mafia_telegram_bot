<?php
namespace App\Modules\Game\Bafs;

use App\Models\GameUser;
use App\Models\GamerParam;


class Passport extends BaseBaf {
    public function puaro_check(GameUser $gamer)
    {  
        if($this->execBaf()) {
            GamerParam::saveParam($gamer, 'puaro_check_result', "Вы показали свой паспорт Комиссару Пуаро. Кажется, он ничего не заметил...");
            return ['puaro_view' => "🌑 Житель ночи"];
        }
        return null;
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