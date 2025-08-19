<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeactivatedCommand extends Model
{
    use HasFactory;
    protected $table = 'deactivated_commands';
    protected $fillable = ['game_id','command'];
    public function game() {
        return $this->hasOne(Game::class, 'id','game_id');
    }
    public static function all_deactivated($game_id) {
        $arr = self::where('game_id',$game_id)->get()->all();
        return array_column($arr, 'command');
    }
}
