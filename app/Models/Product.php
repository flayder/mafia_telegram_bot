<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = "products";
    public static $entityLabel="Product";
    protected $fillable = ['id','name','description','price','cur_code','class'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','description'=>'Описание','price'=>'Цена','cur_code'=>'Валюта','class'=>'Класс'];
    }
    public static function viewFields() {
        return ['id','name','description','price','cur_code'];
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