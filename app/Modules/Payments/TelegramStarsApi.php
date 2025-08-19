<?php

namespace App\Modules\Payments;

use App\Models\Offer;
use App\Models\Payment;
use App\Modules\Bot\AppBot;

class TelegramStarsApi
{

    public function __construct()
    {
    }
    public function createOrder($chat_id, $amount, $offer_id, $currency='XTR')
    {
        $dbd = [
            'user_id' => $chat_id,
            'amount' => $amount,
            'currency' => $currency,
            'pay_method' => 'telegram stars',
            'offer_id' => $offer_id
        ];
        $payment = Payment::create($dbd);
        //--------------------------------------
        $offer = Offer::where('id',$offer_id)->first();
        $price = ['label' => "Покупка ".$offer->name, 'amount' => $amount];
        $params = [
            'chat_id' => $chat_id,
            'title' => "Покупка ".$offer->name,
            'description' => "Покупка ".$offer->name." в @Windmafia_bot",
            'payload' => "" . $payment->id,
            'currency' => $currency,
            'prices' =>[$price],
        ];
        $bot = AppBot::appBot();
        $responce = $bot->getApi()->sendInvoice($params);
    }
    public function getOrder($orderId)
    {
        return Payment::where('id', $orderId)->first();
    }
    public function paymentSuccess($successful_payment)
    {
        $res = ['text' => "Ошибка оплаты"];
        $bot = AppBot::appBot();
        if (isset($successful_payment['currency']) && isset($successful_payment['invoice_payload'])) {
            $order = $this->getOrder($successful_payment['invoice_payload']);
            if ($order->amount == $successful_payment['total_amount'] && $successful_payment['currency'] == $order->currency) {
                $order->status = 1;
                $order->save(); //успешно оплачен
                $res['text'] = "Ваша заявка на пополнение №{$order->id} исполнена. " .
                    "Вам начислено " . $order->offer;
                //mysqli_query($this->db->getLink(),"update {$this->dbPrefix}users set `balance`=`balance`+{$order['amountUsd']} where id='{$order['user_id']}'");
                $res['parse_mode'] = 'HTML';
                $user = $order->user;
                $user->addBalance($order->offer->product, $order->offer->product_amount);

                if ($user->options) {
                    $options = json_decode($user->options, true);
                    if (isset($options['message_id'])) {
                        $bot->deleteInlineKeyboard($user->id, $options['message_id']);
                    }
                }
            }
            $bot->sendAnswer([$res], $order->user_id);
        }
        echo 'YES';
    }
    public static function usdToXTR($sumUSD) {
        return ceil($sumUSD * 100);
    }
}
