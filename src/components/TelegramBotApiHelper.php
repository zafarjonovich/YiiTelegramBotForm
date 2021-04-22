<?php


namespace zafarjonovich\YiiTelegramBotForm\components;

/**
 * Class TelegramBotApiHelper
 * @package app\telegramWidget
 * @property $chat_id
 * @property $message_id
 */


class TelegramBotApiHelper
{

    /** @var array $update */
    public $update = [];

    private $methods;

    public function __construct($methods){

        $this->methods = $methods;

        $this->init();

    }

    private function init(){

        if(isset($this->update['message'])){

            $this->chat_id = $this->update['message']['from']['id'];
            $this->message_id = $this->update['message']['message_id'];

        }else if(isset($this->update['callback_query'])){

            $this->chat_id = $this->update['callback_query']['message']['from']['id'];
            $this->message_id = $this->update['callback_query']['message']['message_id'];

        }
    }

    public function sendMessage($chat_id,$text,$optional_fields = []){
        return $this->request('sendMessage',compact('chat_id','text','optional_fields'));
    }

    public function editMessage($chat_id,$message_id,$text,$optional_fields = []){
        return $this->request('editMessage',compact('chat_id','message_id','text','optional_fields'));
    }

    public function deleteMessage($chat_id,$message_id){
        return $this->request('deleteMessage',compact('chat_id','message_id'));
    }

    public function removeCustomKeyboard() {
        return $this->request('removeCustomKeyboard',[]);
    }

    public function makeCustomKeyboard($buttons,$resize = true,$one_time = true) {
        return $this->request('makeCustomKeyboard',compact('buttons','resize','one_time'));
    }

    public function makeInlineKeyboard($buttons) {
        return $this->request('makeInlineKeyboard',compact('buttons'));
    }

    private function request($method_name,$params = []){
        if(!is_callable($this->methods[$method_name])){
            return false;
        }

        if(empty($params)){
            return $this->methods[$method_name]();
        }else{
            return $this->methods[$method_name](...$params);
        }
    }
}