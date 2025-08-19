<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Union extends Model
{
    use HasFactory;
    protected $table = "unions";
    public static $entityLabel="Union";
    protected $fillable = ['id','game_id'];
    public static function labels() {
        return ['id'=>'id','game_id'=>'game_id'];
    }
    public static function viewFields() {
        return ['id','game_id'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function participants() {
        $this->hasMany(UnionParticipant::class, 'union_id','id');
    }
}