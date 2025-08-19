<?php
namespace App\Modules\Game;

use App\Models\Baf;
use App\Models\BotUser;
use App\Models\UserBaf;
use App\Modules\Bot\AppBot;

class RouletteFuncs {
    protected static function message(BotUser $user, $subject) {
        $bot = AppBot::appBot();
        $txt = "в рулетку $subject";
        $mess['text'] = "Вы выиграли ".$txt;
        $grp['text'] = Game::userUrlName($user)." выиграл(а) ".$txt;
        $grp['inline_keyboard']['inline_keyboard'] =[[$bot->inlBtn("Хочу сыграть","https://t.me/".config('app.bot_nick')."?start=roulette",'url')]];
        
        $bot->sendAnswer([$mess],$user->id);
        $bot->sendAnswer([$grp],AppBot::OFIC_GROUP_ID);
    }
    public static function addCoin(BotUser $user) {
        $user->addBalance(Currency::R_WINDCOIN,1);
        self::message($user,'1' . Currency::allCurrencies()[Currency::R_WINDCOIN]);
    }
    public static function addSeason(BotUser $user) {
        $season = Currency::seasonCurOfMonth();
        $user->addBalance($season,1);
        self::message($user,'1' . Currency::allCurrencies()[$season]);
    }
    public static function addWindbuks(BotUser $user) {
        $user->addBalance(Currency::R_WINDBUCKS,200);
        self::message($user,'200' . Currency::allCurrencies()[Currency::R_WINDBUCKS]);
    }
    public static function addBaf(BotUser $user,$bafId) {
        $ubaf = UserBaf::where(['user_id'=>$user->id,'baf_id'=>$bafId])->first();
        if($ubaf) $ubaf->increment('amount');
        else UserBaf::create(['user_id'=>$user->id,'baf_id'=>$bafId, 'amount'=>1]);
        $baf = Baf::where('id',$bafId)->first();
        self::message($user, $baf);
    }
    public static function addBaf_1(BotUser $user) {
        self::addBaf($user,1);        
    }
    public static function addBaf_2(BotUser $user) {
        self::addBaf($user,2);
    }
    public static function addBaf_3(BotUser $user) {
        self::addBaf($user,3);
    }
    public static function addBaf_4(BotUser $user) {
        self::addBaf($user,4);
    }
    public static function addBaf_5(BotUser $user) {
        self::addBaf($user,5);
    }
    public static function addBaf_6(BotUser $user) {
        self::addBaf($user,6);
    }
    public static function addBaf_7(BotUser $user) {
        self::addBaf($user,7);
    }
    public static function addSeason25(BotUser $user) {
        $season = Currency::seasonCurOfMonth();
        $user->addBalance($season,25);
        self::message($user,'<b>ДЖЕКПОТ</b> 25' . Currency::allCurrencies()[$season]);
    }
    public static function addCoin50(BotUser $user) {
        $user->addBalance(Currency::R_WINDCOIN,50);
        self::message($user,'<b>ДЖЕКПОТ</b> 50' . Currency::allCurrencies()[Currency::R_WINDCOIN]);
    }
}