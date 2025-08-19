<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveBaf extends Model
{
    use HasFactory;
    protected $table = 'active_bafs';
    protected $fillable = ['game_id','user_id','baf_id','need_decrement','is_active'];
    public function baf() {
        return $this->hasOne(Baf::class,'id','baf_id');
    }
    public function userbaf() {
        return UserBaf::where(['user_id'=>$this->user_id,'baf_id'=>$this->baf_id])->first();
    }
    public function game() {
        return $this->hasOne(Game::class, 'id','game_id');
    }
}
