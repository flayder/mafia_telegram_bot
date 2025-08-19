<?php
namespace App\Modules\Game\Products;

use App\Models\UserWarning;
use App\Modules\Bot\AppBot;

class Unwarn extends BaseProduct {
    public function message()
    {
        $bot = AppBot::appBot();        
        //предупреждения
        $uwarns = UserWarning::selectRaw("group_id, count(*) as cnt")->where('user_id',$this->product->user_id)
        ->groupBy('group_id')->get();        
        if($uwarns->count() > 0) {
            $res['text'] = "Выберите группу, в которой вы желаете снять предупреждение";
            $res['inline_keyboard'] = $bot->inlineKeyboard($uwarns,1,"produnwarn&".$this->product->id."&",
            false,'group_id','group');
        }
        else {
            $res['text'] = "У вас нет предупреждений";
        }        
        return $res;
    }
    public function activate(array $params = [])
    {
        $warn = UserWarning::where(['user_id'=>$this->product->user_id,'group_id'=>$params['group_id']])->first();
        if($warn) {
            $this->product->was_used = date('Y-m-d H:i:s');        
            $this->product->group_id = $params['group_id'];
            $this->product->is_deactivate = 1;
            $this->product->save();
            $this->product->refresh();
            $warn->delete();
            $res['text'] = "Вы успешно сняли одно предупреждение для группы ".$this->product->group;
            $this->addReward();
        } 
        else {
            $res['text'] = "Нет предупреждений для группы ".$this->product->group;
        }
        return $res;
    }
    public function deactivate()
    {
        
    }
}