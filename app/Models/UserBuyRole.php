<?php

namespace App\Models;

use App\Modules\Game\Gamer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBuyRole extends Model
{
    use HasFactory;
    protected $table = 'user_buy_roles';
    protected $fillable = ['user_id','role_id','game_id'];
    public function role() {
        return $this->hasOne(GameRole::class,'id','role_id');
    }
    public function buyRole() {
        return $this->hasOne(BuyRole::class,'role_id','role_id');
    }
}
