<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProduct extends Model
{
    use HasFactory;
    protected $table = "user_products";
    public static $entityLabel="UserProduct";
    protected $fillable = ['id','user_id','group_id','product_id','avail_finish_moment','was_used','is_deactivate'];
    public static function labels() {
        return ['id'=>'id','user_id'=>'user_id','product_id'=>'product_id','avail_finish_moment'=>'avail_finish_moment','was_used'=>'was_used'];
    }
    public static function viewFields() {
        return ['id','user_id','product_id','avail_finish_moment','was_used'];
    }
    public static function headers() {
        $labels = self::labels();
        $res="";
        foreach(self::viewFields() as $field)  {
            $res .= "<th>{$labels[$field]}</th>";
        }
        return $res;
    }

    public function group() {
        return $this->hasOne(BotGroup::class, 'id', 'group_id');
    }
    public function user() {
        return $this->hasOne(BotUser::class, 'id', 'user_id');
    }
    public function product() {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
    public function __toString()
    {
        return $this->product->name;
    }
}