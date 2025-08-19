<?php
namespace App\Modules\Game;

use App\Models\BotUser;
use App\Models\SendCurHistory;

class Currency {
    const R_WINDBUCKS = 'windbucks';
    const R_WINDCOIN = 'windcoin';  // = 100 windbucks

    const S_AUTUMN = 'spirit';
    const S_SPRING='bunny';
    const S_SUMMER='sun';
    const S_WINTER='snow';

    const KURSES_WINDCOIN = [
        self::S_AUTUMN => 8.87,
        self::S_SPRING => 8.87,
        self::S_SUMMER => 8.87,
        self::S_WINTER => 8.87
    ];

    const CURNAMES = [
        self::R_WINDBUCKS=>'Виндбаксы',
        self::R_WINDCOIN=>'Виндкоины',
        self::S_AUTUMN=>'Дух Хэллоуина',
        self::S_SPRING=>'Пасхальный кролик',
        self::S_SUMMER=>'Cолнышко',
        self::S_WINTER=>'Снежинка'
    ];

    public static function seasonCurOfMonth($month=null) {
        $month = $month ?? date('m');
        $month = (int) $month;
        $assoc = [
            1=>self::S_WINTER,
            2=>self::S_WINTER,            
            3=>self::S_SPRING,
            4=>self::S_SPRING,            
            5=>self::S_SPRING,            
            6=>self::S_SUMMER,
            7=>self::S_SUMMER,
            8=>self::S_SUMMER,
            9=>self::S_AUTUMN,
            10=>self::S_AUTUMN,
            11=>self::S_AUTUMN,
            12=>self::S_WINTER
        ];
        return $assoc[$month];
    }

    public static function realCurrencies() {
        return [self::R_WINDBUCKS =>'💶',self::R_WINDCOIN => '🪙'];
    }
    public static function seasonCurrencies() {
        return [self::S_AUTUMN => '🎃',self::S_SPRING => '🐰',self::S_SUMMER=>'🌞', self::S_WINTER=>'❄️'];
    }
    public static function allCurrencies() {
        return array_merge(self::realCurrencies(),self::seasonCurrencies());
    }
    public static function sendCurrency($sender, $recipient, $curcode, $sum, $group_id) {
        $senderUser = BotUser::where('id',$sender)->first();
        $recipientUser = BotUser::where('id',$recipient)->first();
        if(!$senderUser || !$senderUser->balances) {
            return false;
        }        
        $balances = json_decode($senderUser->balances, true);
        if(!isset($balances[$curcode]) || $balances[$curcode] < $sum) return false;
        
        //если сюда дошли, значит всё должно получиться
        $balances[$curcode] -= $sum;
        $senderUser->balances = json_encode($balances);
        $senderUser->save();

        $rBalances = [];
        if($recipientUser->balances) $rBalances = json_decode($recipientUser->balances,true);
        if(!isset($rBalances[$curcode])) $rBalances[$curcode] = 0;
        $rBalances[$curcode] += $sum;
        $recipientUser->balances = json_encode($rBalances);
        $recipientUser->save();
        SendCurHistory::create(['currency'=>$curcode,'amount'=>$sum,'sender'=>$sender,'recipient'=>$recipient,'group_id'=>$group_id]);
        return true;
    }
}