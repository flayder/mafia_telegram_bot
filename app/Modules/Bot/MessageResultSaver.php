<?php
namespace App\Modules\Bot;

use Illuminate\Database\Eloquent\Model;

class MessageResultSaver {
    protected $model;
    public $message_id;
    public function __construct(Model $model)
    {        
        $this->model = $model;
    }
    public function saveMessageId(string $message_id) {
        if($this->model->options) $options = json_decode($this->model->options, true);
        else $options = [];        
        $options['message_id'] = $message_id;
        $this->message_id = $message_id;
        $this->model->options = json_encode($options);
        $this->model->save();
    }
    public function saveOption(string $opt_key, $opt_value) {
        if($this->model->options) $options = json_decode($this->model->options, true);
        else $options = [];        
        $options[$opt_key] = $opt_value;        
        $this->model->options = json_encode($options);
        $this->model->save();
    }
    public function getOption(string $opt_key) {
        if($this->model->options) $options = json_decode($this->model->options, true);
        else $options = [];
        return $options[$opt_key] ?? null;
    }
}