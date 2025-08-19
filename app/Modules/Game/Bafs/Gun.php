<?php
namespace App\Modules\Game\Bafs;

use App\Models\GamerParam;
use App\Models\GameUser;
use App\Modules\Game\Game;
use App\Models\Task as TaskModel;

class Gun extends BaseBaf {
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
        return null;
    }
    public function shot(GameUser $gamer) //пробивает щит
    {
        if($this->execBaf(false)) {  //многоразово. пока не кончаться патроны    
            $message['text'] = "Пистолет может пробить щит. Хотите его использовать?";
            $message['inline_keyboard']['inline_keyboard'] = [[[
                'text' => 'ДА',
                'callback_data' => "gunyes&".$gamer->id
            ],
            [
                'text' => 'НЕТ',
                'callback_data' => "gunno&".$gamer->id
            ]]];
            $params = ['chat_id' => $gamer->user_id, 'message' => $message];
            $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
            TaskModel::create(['game_id' => $gamer->game_id, 'name' => "Пистолет, игрок {$gamer->id}. Игра {$gamer->game_id}", 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 2]);
            
            return [
                'work'=>1
            ];
        }
        return null;
    }
    public function gunyes(GameUser $gamer) {
        if($this->execBaf()) {  //многоразово. пока не кончаться патроны    
            GamerParam::saveParam($gamer, 'gunyes', '1');            
            return [
                'text'=>"Вы использовали пистолет!"
            ];
        }
        return [
            'text'=>"Ой, кажется у вас закончились патроны. Но вы еще можете пострелять в следующую ночь, если докупите их прямо сейчас"
        ];
    }
    public function ciganka_view(GameUser $gamer)
    {
        return null;
    }
}