<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestrictMediaUser extends Model
{
    use HasFactory;
    protected $table = 'restrict_media_users';
    protected $fillable = ['group_id','user_id'];
}
