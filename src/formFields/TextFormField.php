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

    public function atHandling(Cache $cache){
        if(isset($this->params['delete_value_from_chat']) and $this->params['delete_value_from_chat']){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
            if($message_id = $cache->getValue('currentFormField.message_id')){
                $this->telegramBotApi->deleteMessage(
                    $this->telegramBotApi->chat_id,
                    $message_id
                );
            }
        }
    }

    public function showErrors($cache,$errors){
        $text = implode(PHP_EOL.PHP_EOL,$errors);

        $response = $this->telegramBotApi->sendMessage(
            $this->telegramBotApi->chat_id,
            $text,
            ['reply_markup' => $this->telegramBotApi->makeCustomKeyboard([
                [['text' => \Yii::t('app','Back')]]])
            ]
        );

        $cache->setValue('currentFormField.message_id',$response['result']['message_id']);
    }

    public function getFormFieldValue(){

        if(!isset($this->telegramBotApi->update['message'])){
            return false;
        }

        return $this->telegramBotApi->update['message']['text'];
    }

    public function render(Cache $cache){

        $update = $this->telegramBotApi->update;

        if(isset($update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }

        $options = [];

        if((isset($this->params['canGoToBack']) and $this->params['canGoToBack']) or !isset($this->params['canGoToBack'])){
            $options['reply_markup'] = $this->telegramBotApi->makeCustomKeyboard([
                [['text' => \Yii::t('app','Back')]]
            ]);
        }

        $response = $this->telegramBotApi->sendMessage(
            $this->telegramBotApi->chat_id,
            $this->params['text'],$options
        );

        $cache->setValue('currentFormField.message_id',$response['result']['message_id']);
    }
}