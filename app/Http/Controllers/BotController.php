<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Union;
use App\Models\BotUser;
use App\Models\GameUser;
use App\Models\GamerParam;
use App\Modules\Game\Game;
use App\Modules\Bot\AppBot;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use App\Models\GameRolesOrder;
use App\Models\Game as GameModel;
use App\Models\Setting;
use App\Models\UserBuyRole;
use Illuminate\Support\Facades\Log;
use App\Modules\Game\GamerFunctions;
use App\Modules\Payments\FreeKassaApi;

class BotController extends Controller
{
    public function index() {
        $bot = AppBot::appBot();
        $bot->run();
    }
    public function test() {
       // $gamers =  GameUser::where('game_id', 229)->orderBy('team')->orderBy('id')->get()->all();
        echo "<pre>";
       
       $vals = Setting::groupSetting('-1002478941472','gamers_count');
       print_r($vals);
    }
    public function webhookFreekassa() {
        $fk = new FreeKassaApi();
        $fk->notification();
    }
    public function paymentStart($offer) {
        $objOffer = Offer::where('id',$offer)->first();        
        $rubPrice = CurrencyRate::calcCurrencySum('USD',$objOffer->price,'RUB');
        return view('web.pay-start',['offer'=>$objOffer, 'rub_price'=>$rubPrice]);
    }
    public function paymentCreate() {
        if($_POST) {
            Log::info('Freekassa post = '.json_encode($_POST));
            $fk = new FreeKassaApi();
            $user = BotUser::where('id',$_POST['user_id'])->first();
            $offer = Offer::where('id',$_POST['offer_id'])->first();
            $rubPrice = CurrencyRate::calcCurrencySum('USD',$offer->price,'RUB');
            if(!$user || !$offer) return 'error';
          //  $fkOrder = $fk->createOrder($user->id,$rubPrice,$offer->id);
            $fkOrder = $fk->createOrder($user->id,$offer->price * 1.25,$offer->id,'USD');
            Log::info('fkOrder = '.json_encode($fkOrder));
            return redirect($fkOrder['location']);
        }
        return 'no post';
    }
    public function paymentSuccess() {
        return "Оплата прошла успешно. Окно можно закрыть";
    }
    public function paymentFail() {
        return "Не удалось принять платеж";
    }
    public function testPay() {
        $fk = new FreeKassaApi();
        $user = BotUser::where('id',"376753094")->first();
        $offer = Offer::where('id',10)->first();       
        $fkOrder = $fk->createOrder($user->id,$offer->price,$offer->id,'USD');
        Log::info('fk test usdt: '.json_encode($fkOrder));
        return redirect($fkOrder['location']);
    }
    
}
