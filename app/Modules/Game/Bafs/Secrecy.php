<?php
namespace App\Modules\Game\Bafs;

use App\Models\GameUser;
use App\Modules\Game\Game;

class Secrecy extends BaseBaf {
    public function puaro_check(GameUser $gamer)
    {          
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
        if($this->execBaf()) {
            //сразу отключаем. использование единоразовое
            $this->activeBaf->is_active = 0;
            $this->activeBaf->save();
            $message = ['text'=>"Кажется, кто-то заметил вас на месте преступления..."];        
            Game::message(['message'=>$message,'chat_id'=>$gamer->user_id]);
            return [
                'work'=>1
            ];
        }
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