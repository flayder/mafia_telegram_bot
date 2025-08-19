<?php
namespace App\Modules\Payments;

use Exception;
use App\Models\BotUser;
use App\Models\Payment;
use App\Modules\Bot\AppBot;
use Illuminate\Support\Facades\Log;

class FreeKassaApi{     
    protected $apiKey = '';
    protected $shopId = 52192;   
    protected $secretWord = '';
    protected $callback;
    protected $botUrl = 'https://t.me/Windmafia_bot';
   
    protected function signature(array $data)
    {
        ksort($data);
        return hash_hmac('sha256', implode('|', $data), $this->apiKey);
    }   
    protected function md5Signature(array $data) {
        return md5($data['m'].':'.$data['oa'].':'.$this->secretWord.':'.$data['currency'].':'.$data['o']);
    }
    public function __construct()
    {
        $this->callback = route('freekassa.notice');    
        $this->apiKey = config("app.fk_apikey");
        $this->secretWord = config('app.fk_secretword');
    }
    
    public function sendpost(array $data, $url)
    {
        $data['signature'] = $this->signature($data);
        $request = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        $result = trim(curl_exec($ch));
        curl_close($ch);
        Log::info("FK: request = $request ; answer = $result");
        return $result;
    }
    public function getIP()
    {
        if (isset($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
        return $_SERVER['REMOTE_ADDR'];
    }
    public function fk_method_id() {
        return 36;  //карта
    }
    public function createOrderAPI($chat_id,$amount_cur, $currency, $client_transaction_id) {
        $params = [
            'nonce' => time(),
            'shopId' => $this->shopId, // ID магазина (kassa id)
            'paymentId' =>$client_transaction_id, // Уникальный Номер заказа (На каждый заказ новый номер, к примеру из БД ключ id) 
            'i' => $this->fk_method_id(), // ID платежной системы
            'ip' => $this->getIP(), // IP пользователя 
            'currency' => $currency, // Вид валюты
            'amount' => $amount_cur, // Сумма для оплаты
            'email' => $chat_id.'@windmafia.net', // выслать чек
            'notification_url' => $this->callback,    
            'success_url' => route('payment.success'),
            'failure_url' => route('payment.fail')       
        ];       
        //Log::channel('daily')->info("create API: ".json_encode($params));
        $createFkData = $this->sendpost($params, 'https://api.freekassa.com/v1/orders/create');       
        $result =  json_decode($createFkData,true);
        $result['client_transaction_id'] = $client_transaction_id;
        return $result;
    }
    public function createOrderUI($amount_cur, $currency,$client_transaction_id)
    {        
        $sci = [
            'm'=>$this->shopId,
            'oa'=>$amount_cur,
            'currency'=>$currency,
            'o'=>$client_transaction_id,            
        ];
        $sci['s'] = $this->md5Signature($sci);
        Log::info("FK create UI: ".json_encode($sci));
        //$createFkData = $this->sendpost($params, 'https://api.freekassa.ru/v1/orders/create');       
        //$result =  json_decode($createFkData,true);        
        $result['location'] = "https://pay.freekassa.com/?".http_build_query($sci);
        $result['client_transaction_id'] = $client_transaction_id;
        return $result;
    }
    public function createOrder($chat_id,$amount,$offer_id, $currency = "USD")
    {
        $dbd = [
            'user_id'=>$chat_id,  
            'amount'=>$amount,
            'currency'=>$currency,
            'pay_method'=>'freekassa',           
            'offer_id'=>$offer_id
          ];
        //$payment = Paymen 
        $payment = Payment::create($dbd);
        /*
        $params = [
            'nonce' => time(),
            'shopId' => $this->shopId, // ID магазина (kassa id)
            'paymentId' =>$payment->id, // Уникальный Номер заказа (На каждый заказ новый номер, к примеру из БД ключ id) 
            'i' => $this->fk_method_id(), // ID платежной системы
            'ip' => $this->getIP(), // IP пользователя 
            'currency' => $currency, // Вид валюты
            'amount' => $amount, // Сумма для оплаты
            'email' => $chat_id.'@mafiya.com', // выслать чек
            'notification_url' => $this->callback,    
            'success_url' => route('payment.success'),
            'failure_url' => route('payment.fail')       
        ];       
        
        $sci = [
            'm'=>$this->shopId,
            'oa'=>$amount,
            'currency'=>$currency,
            'o'=>$payment->id,            
        ];
        $sci['s'] = $this->md5Signature($sci);
        //$createFkData = $this->sendpost($params, 'https://api.freekassa.ru/v1/orders/create');       
        //$result =  json_decode($createFkData,true);        
        $result['location'] = "https://pay.freekassa.com/?".http_build_query($sci);
        $result['client_transaction_id'] = $payment->id;
        return $result;
        */
        if($amount >= 11.2) return $this->createOrderUI($amount,$currency,$payment->id);
        else return $this->createOrderAPI($chat_id,$amount,$currency,$payment->id);
    }
    public function getOrder($id) {
        return Payment::where('id', $id)->first();
    }
   
    public function notification()
    {           
        if($_POST) {  
            Log::info("FK webhook: ".json_encode($_POST));
            $bot = AppBot::appBot();          
            $orderId = $_POST['MERCHANT_ORDER_ID'];
            //$orderId = str_replace("bot_","",$orderId);            
            $order = $this->getOrder($orderId);            
            if($order && $_POST['AMOUNT'] >=  $order->amount) {                
                $order->status = 1;
                $order->save(); //успешно оплачен
                $res['text'] = "Ваша заявка на пополнение №{$order->id} исполнена. ".
                "Вам начислено ".$order->offer;
                //mysqli_query($this->db->getLink(),"update {$this->dbPrefix}users set `balance`=`balance`+{$order['amountUsd']} where id='{$order['user_id']}'");
                $res['parse_mode'] = 'HTML';
                $user = $order->user;
                $user->addBalance($order->offer->product,$order->offer->product_amount);

                if($user->options) {
                    $options = json_decode($user->options, true);
                    if(isset($options['message_id'])) {                        
                        $bot->deleteInlineKeyboard($user->id,$options['message_id']);
                    }
                }
            }
            if($res) {                
                $bot->sendAnswer([$res],$order->user_id);
            } 
            echo 'YES';
        }  
    }
}