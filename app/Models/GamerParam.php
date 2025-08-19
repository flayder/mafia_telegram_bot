<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GamerParam extends Model
{
    use HasFactory;
    protected $table = "gamer_params";
    public static $entityLabel="GamerParam";
    protected $fillable = ['id','game_id','night','gamer_id','param_name','param_value'];
    protected static $needUpdate = false;
    protected static $filledGameParams = [];
    protected static $paramGamers = [];  //[night][param] = gamer
    public static function labels() {
        return ['id'=>'id','game_id'=>'game_id','gamer_id'=>'gamer_id','param_name'=>'param_name','param_value'=>'param_value'];
    }
    public static function viewFields() {
        return ['id','game_id','gamer_id','param_name','param_value'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function gamer() {
        return $this->hasOne(GameUser::class, 'id','gamer_id');
    }
    public static function saveParam(GameUser $gamer, $paramName, $paramValue, $needUpdate = true) {
        $model = self::updateOrCreate(['gamer_id'=>$gamer->id,'param_name'=>$paramName, 'night'=>$gamer->game->current_night],
                                ['game_id'=>$gamer->game_id, 'param_value'=>$paramValue]);
        self::$needUpdate = $needUpdate;                        
        return $model;
    }
    public static function getParam(GameUser $gamer, $paramName) {
        $model = self::where(['gamer_id'=>$gamer->id,'param_name'=>$paramName])->first();
        if($model) {
            return $model->param_value;
        }
        return null;
    }
    public static function gamerParams(GameUser $gamer, $night=null) {
        $night = $night ?? $gamer->game->current_night;
        $models = self::where(['gamer_id'=>$gamer->id, 'night'=>$night])->get();
        $arr = [];
        foreach($models as $model) {
            $arr[$model->param_name] = $model->param_value;
        }
        return $arr;
    }
    public static function gameParams(Game $game, $night=null, $update = false) {        
        $night = $night ?? $game->current_night;
        if(!isset(self::$filledGameParams[$night]) || $update || self::$needUpdate) {
            self::$needUpdate = false;  //обновлено
            $where = ['game_id'=>$game->id, 'night'=>$night];            
            $models = self::where($where)->get();
            $arr = [];
            foreach($models as $model) {
                $arr[$model->param_name] = $model->param_value;
            }
            self::$filledGameParams[$night] = $arr;
        }
        return self::$filledGameParams[$night];        
    }

    public static function gamerFromParam(Game $game,string $param, $night=null, $update = false) {
        $night = $night ?? $game->current_night;
        //[night][param]
        if(!isset(self::$paramGamers[$night][$param]) || $update) {
            $where = ['game_id'=>$game->id, 'night'=>$night, 'param_name'=>$param]; 
            $model = self::where($where)->first();
            if($model) self::$paramGamers[$night][$param] = $model->gamer;
            else self::$paramGamers[$night][$param] = null;
        }
        return self::$paramGamers[$night][$param];
    }

    public static function gameBeforeNightsParams(Game $game, $night=null) {
        $night = $night ?? $game->current_night;
        $results = [];
        if($night == 1) return $results;  //нет ночей меньше первой. экономим запросы
        $models = self::where('game_id', $game->id)->where('night','<',$night)->orderBy('night')->get();
        foreach($models as $model) {
            $results[$model->param_name] = $model->param_value;
        }
        return $results;
    }

    public static function deleteAction($game, $action) {        
        $param = self::where(['night'=>$game->current_night,'param_name'=>$action, 'game_id'=>$game->id])->first();
        if($param) {            
            $result = $param->toArray();            
            $param->delete();   
            self::$needUpdate = true;          
            return $result;
        }
        Log::info("параметр $action для удаления НЕ обнаружен");
        return null;
    }
    public static function afkList($game_id) {
        $afkParams = self::where('game_id',$game_id)->where('param_name','afk')->get()->all();
        return array_column($afkParams, 'param_value');         
    }
}