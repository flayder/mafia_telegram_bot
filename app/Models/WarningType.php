<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningType extends Model
{
    use HasFactory;
    protected $table = "warning_types";
    public static $entityLabel="WarningType";
    protected $fillable = ['id','name','description','is_mute'];
    public static function labels() {
        return ['id'=>'id','name'=>'name','description'=>'description','is_mute'=>'is_mute'];
    }
    public static function viewFields() {
        return ['id','name','description','is_mute'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public function __toString()
    {
        return $this->name;
    }
}