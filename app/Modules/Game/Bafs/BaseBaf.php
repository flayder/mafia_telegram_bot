<?php
namespace App\Modules\Game\Bafs;

use App\Models\ActiveBaf;
use App\Models\GameUser;
use App\Modules\Game\Currency;

abstract class BaseBaf {
    protected  ActiveBaf $activeBaf;
    abstract public function puaro_check(GameUser $gamer);
    abstract public function kill(GameUser $gamer);
    abstract public function gallow(GameUser $gamer);
    abstract public function visit_role(GameUser $gamer);    
    abstract public function shot(GameUser $gamer);    //предлагает выбрать пистолет если куплен баф
    abstract public function ciganka_view(GameUser $gamer);    

    public function __construct(ActiveBaf $activeBaf)
    {
        $this->activeBaf = $activeBaf;
    }
    protected function execBaf($decrementing = true) {
        if($this->activeBaf->is_active) {            
            if($this->activeBaf->need_decrement) {                
                $userbaf = $this->activeBaf->userbaf();
                if(!$userbaf || $userbaf->amount <= 0) {
                    $this->activeBaf->is_active = 0;
                    $this->activeBaf->save();
                    return false;
                }
                if($decrementing) {
                    $userbaf->decrement('amount');                    
                    if($this->activeBaf->baf->cur_code === Currency::R_WINDCOIN) {
                        $ggroup = $this->activeBaf->game->group;                        
                        $ggroup->addReward($this->activeBaf->baf->price,"Использование бафа {$this->activeBaf->baf} в игре {$this->activeBaf->game_id}",$this->activeBaf->game_id);
                    }
                }
            }
            return true;
        }
        return false;
    }
}