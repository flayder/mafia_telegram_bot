<?php
namespace App\Modules\Game\Products;

use Exception;
use App\Modules\Game\Game;
use App\Models\UserProduct;
use App\Modules\Bot\AppBot;
use App\Modules\Game\Currency;

class Prefix extends BaseProduct{   
    public function message() {
        $res['text'] = "Отправьте желаемый префикс:";
        $res['innercmd'] = "inputprefix&";
        return $res;
    }
    public function activate(array $params = []) {
        $this->product->was_used = date('Y-m-d H:i:s');
        $this->product->avail_finish_moment = date('Y-m-d H:i:s',strtotime("+7 day"));
        $this->product->group_id = $params['group_id'];
        $this->product->save();
        $this->product->refresh();        
        $mess = ['text'=>"Пользователь ".Game::userUrlName($this->product->user)." активировал префикс '{$params['prefix']}' для группы <b>".$this->product->group->title."</b>"];
        $bot = AppBot::appBot();
        $bot->sendAnswer([$mess],$this->product->group->who_add);
        //награда
        $this->addReward();
    }
    public function deactivate() {
        $this->product->is_deactivate = 1;
        $this->product->save();
        
        $mess = ['text'=>"У пользователя ".Game::userUrlName($this->product->user)." закончился срок активации префикса для группы <b>".$this->product->group->title."</b>"];
        $messUser = ['text'=>"У вас закончился срок активации префикса для группы <b>".$this->product->group->title."</b>"];        
        $bot = AppBot::appBot();
        try {
            $bot->sendAnswer([$mess],$this->product->group->who_add);
            $bot->sendAnswer([$messUser],$this->product->user_id);
        }
        catch(Exception $e) {

        }
    }
}