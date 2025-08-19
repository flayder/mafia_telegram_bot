<?php

namespace App\Models;

use Exception;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BotGroup extends Model
{
    use HasFactory;
    protected $table = "bot_groups";
    public static $entityLabel="BotGroup";
    protected $fillable = ['id','title','group_type','group_link','who_add','tarif_id','tarif_expired','reward'];
    public static function labels() {
        return ['id'=>'id','title'=>'Название','group_type'=>'Тип','group_link'=>'Ссылка','who_add'=>'Кто добавил','tarif_id'=>'Тариф','reward'=>'Награда %','balance'=>'Баланс'];
    }
    public static function viewFields() {
        return ['id','title','group_type','group_link','who_add','balance'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function getUrl() {
        if(!$this->group_link) {
            $bot = AppBot::appBot();
            try {
                $objLink = $bot->getApi()->createChatInviteLink(['chat_id'=>$this->id]);
                if($objLink && $objLink->invite_link) {
                    $this->group_link = $objLink->invite_link;
                    $this->save();
                }    
            }        
            catch(Exception $e) {
                return null;
            }
        }
        return $this->group_link;
    }
    public function tarif() {
        return $this->hasOne(GroupTarif::class, 'id', 'tarif_id');
    }
    public function addReward($buySum, $descr, $game_id=null) {  // $buySum - исходная сумма покупки
        $rewardSum = round($buySum * $this->reward / 100,2);
        RewardHistory::create([
            'group_id'=>$this->id,'game_id'=>$game_id,'buy_sum'=>$buySum,'reward_percent'=>$this->reward,'reward_sum'=>$rewardSum,'description'=>$descr
        ]);
        $this->balance += $rewardSum;
        $this->total_reward += $rewardSum;
        $this->save();
    }
    public static function userGroupsBalance($user_id) {
        $itogs = self::selectRaw("sum(balance) as ibalance")->where('who_add',$user_id)->first();
        if($itogs && $itogs->ibalance) return $itogs->ibalance;
        return 0;
    }
    public static function clearBalances($user_id) {
        $groups = self::where('who_add',$user_id)->get();
        $data = [];
        foreach($groups as $grp) {
            if($grp->balance > 0) $data[$grp->id] = $grp->balance;
        }
        DB::table('bot_groups')->where('who_add',$user_id)->update(['balance'=>0]);
        return $data;
    }
    public function __toString()
    {
        return $this->title;
    }
}