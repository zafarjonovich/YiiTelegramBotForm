<?php


namespace zafarjonovich\YiiTelegramBotForm\formFields;


use zafarjonovich\YiiTelegramBotForm\Cache;
use zafarjonovich\YiiTelegramBotForm\FormField;

class TextFormField extends FormField{

    public function goBack(){
        if(isset($this->telegramBotApi->update['message']['text']) and
            $this->telegramBotApi->update['message']['text'] == \Yii::t('app','Back')){
            return true;
        }
        return false;
    }

    public function goHome(){
        if(isset($this->telegramBotApi->update['message']['text']) and
            $this->telegramBotApi->update['message']['text'] == \Yii::t('app','Home')){
            return true;
        }
        return false;
    }

    public function atHandling(){
        if(isset($this->params['delete_value_from_chat']) and $this->params['delete_value_from_chat']){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
            if(isset($this->state['message_id'])){
                $this->telegramBotApi->deleteMessage(
                    $this->telegramBotApi->chat_id,
                    $this->state['message_id']
                );
            }
        }
    }

    public function showErrors($errors){
        $text = implode(PHP_EOL.PHP_EOL,$errors);

        $response = $this->telegramBotApi->sendMessage(
            $this->telegramBotApi->chat_id,
            $text,
            ['reply_markup' => $this->telegramBotApi->makeCustomKeyboard([
                [['text' => \Yii::t('app','Back')]]])
            ]
        );

        $this->state['message_id'] = $response['result']['message_id'];
    }

    public function getFormFieldValue(){

        if(!isset($this->telegramBotApi->update['message']['text'])){
            return false;
        }

        $value = $this->telegramBotApi->update['message']['text'];

        return $this->params['pattern'][$value] ?? $value;
    }

    public function render(){

        $update = $this->telegramBotApi->update;

        if(isset($update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }

        $options = [];

        $keyboard = [];

        if(isset($this->params['keyboard'])){
            $keyboard = $this->params['keyboard'];
        }

        $keyboard = $this->createNavigatorButtons($keyboard);

        if($keyboard){
            $options['reply_markup'] = $this->telegramBotApi->makeCustomKeyboard($keyboard);
        }

        $response = $this->telegramBotApi->sendMessage(
            $this->telegramBotApi->chat_id,
            $this->params['text'],$options
        );

        if(isset($response['ok']) and $response['ok']){
            $this->state['message_id'] = $response['result']['message_id'];
        }
    }
}