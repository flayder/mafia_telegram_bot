<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LimitSelect extends Model
{
    use HasFactory;
    protected $table = 'limit_select';
    protected $fillable = ['gamer_id','limit_select'];
    public function gamer() {
        return $this->hasOne(GameUser::class, 'id','gamer_id');
    }
    public static function gamersLimits(array $gamersIds) {
        $models = self::whereIn('gamer_id', $gamersIds)->get();
        $results = [];
        $game_id = null;
        foreach($models as $model) {
            $results[$model->gamer_id][] = $model->limit_select;
            if(!$game_id) $game_id = $model->gamer->game_id;
        }
        //дополним список уголовника ограничением бить по мафам
        $ugolovnik = GameUser::where('game_id', $game_id)->where('role_id', 23)->first();
        if($ugolovnik) {
            $mafs = GameUser::whereIn('role_id',[16,17,18,19,20,21,22,23,24,25])->get()->all();
            $results[$ugolovnik->id] = $results[$ugolovnik->id] ?? [];
            $results[$ugolovnik->id] = array_merge($results[$ugolovnik->id], array_column($mafs,'id'));
        }
        return $results;
    }
}
