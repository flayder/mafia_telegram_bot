<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardHistory extends Model
{
    use HasFactory;
    protected $table = "reward_history";
    public static $entityLabel="RewardHistory";
    protected $fillable = ['id','group_id','game_id','buy_sum','reward_percent','reward_sum','description'];
    public static function labels() {
        return ['id'=>'id','group_id'=>'group_id','group'=>'Группа','game_id'=>'Игра','buy_sum'=>'Покупка','reward_percent'=>'Награда %','reward_sum'=>'Награда','description'=>'Описание'];
    }
    public static function viewFields() {
        return ['id','group','game_id','buy_sum','reward_percent','reward_sum','description'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function group() {
        return $this->hasOne(BotGroup::class, 'id','group_id');
    }
}