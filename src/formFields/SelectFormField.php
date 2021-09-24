<?php


namespace zafarjonovich\YiiTelegramBotForm\formFields;


use zafarjonovich\Telegram\Keyboard;
use zafarjonovich\Telegram\update\objects\Response;
use zafarjonovich\YiiTelegramBotForm\Cache;
use zafarjonovich\YiiTelegramBotForm\FormField;

class SelectFormField extends FormField
{
    public $options = [];

    public function goBack(){

        $update = $this->telegramBotApi->update;
        
        if($this->isInlineKeyboard and $update->isCallbackQuery()){
            $data = json_decode($update->getCallbackQuery()->getData(),true);
            return $data and isset($data['go']) and $data['go'] == 'back';
        }

        if(!$this->isInlineKeyboard and $update->isMessage()){
            return $update->getMessage()->getText() == \Yii::t('app','Back');
        }

        return false;
    }

    public function goHome()
    {
        $update = $this->telegramBotApi->update;

        if($this->isInlineKeyboard and $update->isCallbackQuery()){
            $data = json_decode($update->getCallbackQuery()->getData(),true);
            return $data and isset($data['go']) and $data['go'] == 'home';
        }

        if(!$this->isInlineKeyboard and $update->isMessage()){
            return $update->getMessage()->getText() == \Yii::t('app','Home');
        }

        return false;
    }

    public function atHandling()
    {
        $update = $this->telegramBotApi->update;

        if(($this->clearChat || $this->isInlineKeyboard) && $update->isMessage()) {
            $this->telegramBotApi->deleteCurrentMessage();

            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                '~',
                [
                    'reply_markup' => $this->telegramBotApi->removeCustomKeyboard()
                ]
            );
            $response = new Response($response);

            if($response->ok()){
                $this->telegramBotApi->deleteMessage(
                    $this->telegramBotApi->chat_id,
                    $response->getResult()->getMessageId()
                );
            }

            if(isset($this->state['message_id']) && ! $this->isInlineKeyboard)
                $this->telegramBotApi->deleteMessage(
                    $this->telegramBotApi->chat_id,
                    $this->state['message_id']
                );
        }
    }

    public function beforeHandling()
    {
        $update = $this->telegramBotApi->update;

        if(!$this->isInlineKeyboard && $update->isCallbackQuery()){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function afterFillAllFields(){
        $update = $this->telegramBotApi->update;
        if($this->isInlineKeyboard and $update->isCallbackQuery()){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function afterOverAction()
    {
        $update = $this->telegramBotApi->update;
        if($this->isInlineKeyboard and $update->isCallbackQuery()){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function getFormFieldValue(){
        $update = $this->telegramBotApi->update;
        if($this->isInlineKeyboard and $update->isCallbackQuery()){
            $data = json_decode($update->getCallbackQuery()->getData(),true);

            if($data and isset($data[$this->name])){
                return $data[$this->name];
            }
        }

        if(!$this->isInlineKeyboard and $update->isMessage()){

            $calls = [];

            foreach ($this->options as $option) {
                foreach ($option as $item) {
                    $calls[$item[1]] = $item[0];
                }
            }

            return isset($calls[$update->getMessage()->getText()])?$calls[$update->getMessage()->getText()]:false;
        }

        return false;
    }

    public function render(){

        $update = $this->telegramBotApi->update;

        $keyboard = new Keyboard();

        foreach ($this->options as $option) {
            foreach ($option as $item) {
                if($this->isInlineKeyboard){
                    $keyboard->addCallbackDataButton($item[1],json_encode([$this->name => $item[0]]));
                }else{
                    $keyboard->addCustomButton($item[1]);
                }
            }
            $keyboard->newRow();
        }

        $keyboard->newRow();

        $options = [
            'reply_markup' => $this->createNavigatorButtons($keyboard)
        ];

        if((!$this->isInlineKeyboard && $update->isCallbackQuery() && !isset($this->state['cp'])) || ($update->isMessage() && (!$this->isInlineKeyboard || !isset($this->state['cp'])))) {
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                $this->text,
                $options
            );
        } else if($update->isCallbackQuery()) {
            $response = $this->telegramBotApi->editMessageText(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id,
                $this->text,
                $options
            );
        }

        if(isset($response['ok']) and $response['ok']){
            $this->state['message_id'] = $response['result']['message_id'];
            $this->state['cp'] = true;
        }
    }
}