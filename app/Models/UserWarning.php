<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWarning extends Model
{
    use HasFactory;
    protected $table = 'user_warnings';
    protected $fillable = ['user_id','group_id','warning_id'];
    public function group() {
        return $this->hasOne(BotGroup::class,'id','group_id');
    }
    public function user() {
        return $this->hasOne(BotUser::class,'id','user_id');
    }    
    public function warning() {
        return $this->hasOne(WarningType::class,'id','warning_id');
    }
}
