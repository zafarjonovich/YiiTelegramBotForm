<?php


namespace zafarjonovich\YiiTelegramBotForm\components\formItems;


use zafarjonovich\YiiTelegramBotForm\components\FormField;

class TextFormField extends FormField{

    public function goBack(){
        if(isset($this->telegramBotApi->update['message']['text']) and
            $this->telegramBotApi->update['message']['text'] == \Yii::t('form','Back')){
            return true;
        }
        return false;
    }

    public function getFormFieldValue(){

        if(!isset($this->telegramBotApi->update['message'])){
            return false;
        }

        return $this->telegramBotApi->update['message']['text'];
    }

    public function render(){

        $update = $this->telegramBotApi->update;

        if(isset($update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }

        return $this->telegramBotApi->sendMessage(
            $this->telegramBotApi->chat_id,
            $this->params['text'],[
                'reply_markup' => $this->telegramBotApi->makeCustomKeyboard([
                    [['text' => \Yii::t('form','Back')]]
                ])
        ]);
    }
}