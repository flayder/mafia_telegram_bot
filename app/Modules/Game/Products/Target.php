<?php
namespace App\Modules\Game\Products;

use App\Models\ChatMember;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\ProhibitKill;

class Target extends BaseProduct {
    public function message()
    {
        $bot = AppBot::appBot();        
        //предупреждения
        $ugroups = ChatMember::where('member_id',$this->product->user_id)->get();
        if($ugroups->count() > 0) {
            $res['text'] = "Выберите группу, в которой вы желаете активировать ".$this->product;
            $res['inline_keyboard'] = $bot->inlineKeyboard($ugroups,1,"prodanyactivate&".$this->product->id."&",
            false,'group_id','group');
        }
        else {
            $res['text'] = "Вы не зарегистрированны как участник ни одной из групп. Если у вас есть группа для игры, то отправьте в ней команду /active и после этого снова попробуйте активировать Таргет";
        }        
        return $res;
    }
    public function activate(array $params = [])
    {
        if($this->product->is_deactivate == 0) {
            $this->product->was_used = date('Y-m-d H:i:s');        
            $this->product->group_id = $params['group_id'];
            $this->product->is_deactivate = 1;
            
            $time = time()+24 * 3600;
            $dt = date('d.m.Y H:i',$time + 3*3600);
            $dt2 = date('Y-m-d H:i:s',$time);
            $this->product->avail_finish_moment = $dt2;
            $this->product->save();
            $this->product->refresh();
            $res['text'] = "".$this->product." успешно активирован для группы ".$this->product->group.
            "\nАктивен до: $dt (МСК)";
            ProhibitKill::create(['user_id'=>$this->product->user_id,
            'group_id'=>$params['group_id'],'night_count'=>$params['night_count'],
            'expire_time'=>$dt2 ]);

            $mess = ['text'=>"Пользователь ".Game::userUrlName($this->product->user)." активировал '{$this->product}' для группы <b>".$this->product->group->title."</b> до $dt"];
            $bot = AppBot::appBot();
            $bot->sendAnswer([$mess],$this->product->group->who_add);
            $this->addReward();
        }
        else {
            $res['text'] = "<b>".$this->product."</b> уже использован раннее";
        }
        return $res;        
    }
    public function deactivate()
    {
        
    }
}