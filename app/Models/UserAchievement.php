<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAchievement extends Model
{
    use HasFactory;
    protected $table = 'user_achievements';
    protected $fillable = ['user_id','achievement_id'];
    public function achievement() {
        return $this->hasOne(Achievement::class,'id','achievement_id');
    }
}
