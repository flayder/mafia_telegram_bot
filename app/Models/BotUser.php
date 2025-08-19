<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BotUser extends Model
{
    use HasFactory;
    protected $table = "bot_users";
    public static $entityLabel="BotUser";
    protected $fillable = ['id','nick_name','first_name','last_name','balances','referal_link_id','referer_id'];
    public static function labels() {
        return ['id'=>'id','nick_name'=>'Ник','first_name'=>'Имя','last_name'=>'Фамилия','balances'=>'Балансы'];
    }
    public static function viewFields() {
        return ['id','nick_name','first_name','last_name','balances'];
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
        return $this->first_name.($this->last_name ? ' '.$this->last_name : '');
    }
    public function getBalances() {
        if(empty($this->balances)) return [];
        $balances = json_decode($this->balances, true);
        return $balances;
    }
    public function getBalance($cur_code) {
        $balances = $this->getBalances();
        if(isset($balances[$cur_code])) return $balances[$cur_code];
        return 0;
    }
    public function changeBalance($cur_code, $sum, $saved = true) {  // $sum может быть с + или с -
        $balances = $this->getBalances();
        $balances[$cur_code] = $this->getBalance($cur_code) + $sum;
        $this->balances = json_encode($balances);
        if($saved) $this->save();
    }
    public function addBalance($cur_code, $sum, $saved = true) {
        $this->changeBalance($cur_code,abs($sum), $saved);
    }
    public function decBalance($cur_code, $sum, $saved = true) {
        $curBalance = $this->getBalance($cur_code);
        if($curBalance < abs($sum)) return false;  //не получилось списать
        $this->changeBalance($cur_code,-abs($sum), $saved);
        return true;
    }
    public function bafs() {
        return $this->hasOne(UserBaf::class, 'user_id','id');
    }
}