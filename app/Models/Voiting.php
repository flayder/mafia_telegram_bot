<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voiting extends Model
{
    use HasFactory;
    protected $table = "voitings";
    public static $entityLabel="Voiting";
    protected $fillable = ['id','game_id','is_active','long_in_seconds'];
    public static function labels() {
        return ['id'=>'id','game_id'=>'game_id','is_active'=>'is_active','long_in_seconds'=>'long_in_seconds'];
    }
    public static function viewFields() {
        return ['id','game_id','is_active','long_in_seconds'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function game() {
        return $this->hasOne(Game::class,'id','game_id');
    }
    public function isActive() {
        return ($this->is_active == 1);
    } 
}