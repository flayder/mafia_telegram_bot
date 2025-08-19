<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBaf extends Model
{
    use HasFactory;
    protected $table = 'user_bafs';
    protected $fillable = ['user_id','baf_id','amount','is_activate'];
    public function baf() {
        return $this->hasOne(Baf::class, 'id', 'baf_id');
    }
}
