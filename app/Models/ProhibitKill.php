<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProhibitKill extends Model
{
    use HasFactory;
    protected $table = 'prohibit_kills';
    protected $fillable = ['user_id','group_id','night_count','expire_time'];
}
