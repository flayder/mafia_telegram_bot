<?php

namespace App\Modules\Bot;

use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Objects\Message as MessageObject;

class TelegramApi extends \Telegram\Bot\Api
{
    public function replyKeyboardMarkup($params) {
        return Keyboard::make($params);
    } 
    public function sendRawMessage(array $params) {
        $params = ['form_params' =>$params];
        $result = $this->sendRequest('POST', 'sendMessage', $params);
    }
    public function sendPhoto(array $params): MessageObject
    {
        if(!isset($params['not_file'])) $params['photo'] = InputFile::create($params['photo']);
        else unset($params['not_file']);
        return parent::sendPhoto($params);
    } 
    public function sendVideo(array $params): MessageObject
    {
        if(!isset($params['not_file'])) $params['video'] = InputFile::create($params['video']);
        else unset($params['not_file']);
        return parent::sendVideo($params);
    }
    public function __construct($token = null, $async = false, $httpClientHandler = null)
    {
        if(!$httpClientHandler) $httpClientHandler = new TelegramGuzzleClient();
        parent::__construct($token,$async,$httpClientHandler);
    }
}