<?php
namespace App\Modules\Game\Bafs;

use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Models\Task as TaskModel;

class Oko extends BaseBaf {
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
    public function shot(GameUser $gamer)
    {
        return null;
    }
    public function ciganka_view(GameUser $gamer)
    {
        if($this->execBaf(false)) {  //многоразово. пока не кончаться попытки    
            $message['text'] = " Хотите использовать третий глаз? Полученная информация может пригодиться вам...";
            $message['inline_keyboard']['inline_keyboard'] = [[[
                'text' => 'ДА',
                'callback_data' => "okoexecyes&".$gamer->id
            ],
            [
                'text' => 'НЕТ',
                'callback_data' => "okoexecno&".$gamer->id
            ]]];
            $params = ['chat_id' => $gamer->user_id, 'message' => $message];
            $options = ['class' => Game::class, 'method' => 'message', 'param' => $params];
            TaskModel::create(['game_id' => $gamer->game_id, 'name' => "3-й глаз, игрок {$gamer->id}. Игра {$gamer->game_id}", 'options' => json_encode($options, JSON_UNESCAPED_UNICODE), 'delay' => 2]);
            
            return [
                'work'=>1
            ];
        }
        return null;
    }
    public function oko_exec(GameUser $gamer) {
        if($this->execBaf()) {  //многоразово. пока не кончаться попытки    
            GamerParam::saveParam($gamer, 'oko_exec', '1');            
            return [
                'text'=>"Вы использовали 3-й глаз!"
            ];
        }
        return [
            'text'=>"Ой, кажется у вас закончились купленные попытки. Но вы еще можете использовать эту возможность в следующую ночь, если докупите их прямо сейчас"
        ];
    }
}