<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;
    protected $table = "offers";
    public static $entityLabel="Offer";
    protected $fillable = ['id','name','price','product','product_amount','where_access','parent_id'];
    public static function labels() {
        return ['id'=>'id','name'=>'Название','price'=>'Цена','product'=>'Товар','product_amount'=>'Количество товара','where_access'=>'Доступность','parent_id'=>'Родительская категория','parent'=>'Родитель'];
    }
    public static function viewFields() {
        return ['id','name','price','product','product_amount','where_access','parent'];
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
    public function parent() {
        return $this->hasOne(self::class,'id','parent_id');
    }
    public function getBtnnameAttribute() {
        if($this->price > 0) return $this->name.' - '.$this->price.' $';
        return $this->name;
    }
}