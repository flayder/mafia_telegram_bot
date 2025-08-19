<?php
namespace App\Modules\Bot;

use App\Models\GameUser;

class GamerSaver {
    public $message_id;
    public $gameUser = null;
    public function __construct(GameUser $gu)
    {
        $this->gameUser = $gu;
    }
    public function saveMessageId(string $message_id) {
        $this->gameUser->message_id = $message_id;
        $this->gameUser->save();
    }
}