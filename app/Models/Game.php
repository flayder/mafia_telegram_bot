<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model {
    protected $table = "games";    
    const NIGHT = 'night';
    const DAY = 'day';
    protected $fillable = ['status','group_id','options','current_night','times_of_day','is_team'];
    public function group() {
        return $this->hasOne(BotGroup::class, 'id','group_id');
    }
    public function gamers() {
        return $this->hasMany(GameUser::class,'game_id','id');
    }
    public function activeGamers() {
        return $this->hasMany(GameUser::class,'game_id','id')->where('is_active',1); //is_active
    }
    public function getOptions() {
        return !empty($this->options) ? json_decode($this->options, true) : [];
    }
    public function isDay() {
        return $this->times_of_day === self::DAY;
    }
    public function isNight() {
        return $this->times_of_day === self::NIGHT;
    }
    public function setDay() {
        $this->times_of_day = self::DAY;
    }
    public function setNight() {
        $this->times_of_day = self::NIGHT;
    }
   
}