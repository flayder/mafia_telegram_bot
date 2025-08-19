<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payments';
    protected $fillable = ['user_id','amount','currency','pay_method','status','offer_id'];
    public function offer() {
        return $this->hasOne(Offer::class,'id','offer_id');
    }
    public function user() {
        return $this->hasOne(BotUser::class,'id','user_id');
    }
}
