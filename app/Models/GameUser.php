<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Game\Game as ModuleGame;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GameUser extends Model {
    use HasFactory;
    protected $table = "user_game_roles";
    protected $fillable = ['game_id','user_id','role_id','first_role_id','is_active','kill_night_number','killer_id','killers','team','message_id','sort_id'];  //kill_night_number - в какую ночь был убит
    public function role() {
        return $this->hasOne(GameRole::class,'id','role_id');
    }
    
    public function user() {
        return $this->hasOne(BotUser::class,'id','user_id');        
    }
    public function cuser() {
        return $this->hasOne(BotUser::class,'id','user_id');
    }
    
    public function game() {
        return $this->hasOne(Game::class, 'id','game_id');
    }
    public function killer() {
        return $this->hasOne(GameUser::class, 'id','killer_id');
    }
    public function isActive() {
        return ($this->is_active == 1);
    }
    public function getComandanameAttribute($value) {
        if($this->team) return ModuleGame::COMMAND_COLORS[$this->team].$this->user;
        return ''.$this->user;
    }
    
    public function getUserAttribute($value)
    {   
        $bu = BotUser::where('id',$this->user_id)->first();
        if(!$bu) $bu = new BotUser(['user_id'=>$this->user_id, 'first_name'=>"Бот ".$this->user_id]);        
        return $bu;
    } 
    public function addKiller($killer_id, $save = true) {
        $killers = explode(',',$this->killers ?? '');
        $killers[] = $killer_id;
        $this->killers = implode(',',$killers);
        if($save) $this->save();
    }
    public static function connectNewGame(array $gamerParams) {
        //отключаем от всех игр, к которым возможно присоединен был раньше
        DB::table('user_game_roles')->where(['user_id'=>$gamerParams['user_id'], 'is_active'=>1])->update(['is_active'=>0]);        
        //определим порядковый номер
        $count = GameUser::select('id')->where('game_id',$gamerParams['game_id'])->get()->count();
        $gamerParams['sort_id'] = $count+1;
        self::create($gamerParams);
    }
    


}