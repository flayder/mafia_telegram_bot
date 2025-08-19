<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterType extends Model
{
    use HasFactory;
    protected $table = "newsletter_types";
    public static $entityLabel="NewsletterType";
    protected $fillable = ['id','name'];
    public static function labels() {
        return ['id'=>'id','name'=>'name'];
    }
    public static function viewFields() {
        return ['id','name'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }
}