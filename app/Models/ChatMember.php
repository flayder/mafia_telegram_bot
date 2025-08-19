<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMember extends Model
{
    use HasFactory;
    protected $table = "chat_members";
    public static $entityLabel="ChatMember";
    protected $fillable = ['id','member_id','group_id','username','first_name','last_name','role','is_bot','is_premium'];
    public static function labels() {
        return ['id'=>'id','member_id'=>'member_id','group_id'=>'group_id','username'=>'username','first_name'=>'first_name','last_name'=>'last_name','role'=>'role','is_bot'=>'is_bot','is_premium'=>'is_premium'];
    }
    public static function viewFields() {
        return ['id','member_id','group_id','username','first_name','last_name','role','is_bot','is_premium'];
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
    public function member() {
        return $this->hasOne(BotUser::class, 'id', 'member_id');
    }
}