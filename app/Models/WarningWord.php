<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarningWord extends Model
{
    use HasFactory;
    protected $table = "warning_words";
    public static $entityLabel="WarningWord";
    protected $fillable = ['id','word','group_id'];
    public static function labels() {
        return ['id'=>'id','word'=>'word','group_id'=>'group_id'];
    }
    public static function viewFields() {
        return ['id','word','group_id'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
    public static function testword($wordstr,$group_id) {
        $wordstr = mb_strtolower($wordstr);
        $wordlist = explode(" ",$wordstr);
        $wl2 = [];
        foreach($wordlist as $ww) {
            $wl2[] = trim($ww);
        }
        $groupWarnWords = self::where('group_id',$group_id)->get();    
        foreach($groupWarnWords as $gww) {
            if(in_array($gww->word,$wl2)) return true;
        }
        return false;
    }
}