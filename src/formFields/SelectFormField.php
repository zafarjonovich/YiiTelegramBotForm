<?php


namespace zafarjonovich\YiiTelegramBotForm\formFields;


use zafarjonovich\Telegram\Keyboard;
use zafarjonovich\YiiTelegramBotForm\Cache;
use zafarjonovich\YiiTelegramBotForm\FormField;

class SelectFormField extends FormField{

    public function goBack(){
        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);
            return $data and isset($data['go']) and $data['go'] == 'back';
        }

        if(!$is_inline_keyboard and isset($this->telegramBotApi->update['message']['text'])){
            return $this->telegramBotApi->update['message']['text'] == \Yii::t('app','Back');
        }

        return false;
    }

    public function goHome()
    {
        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);
            return $data and isset($data['go']) and $data['go'] == 'home';
        }

        if(!$is_inline_keyboard and isset($this->telegramBotApi->message['text'])){
            return $this->telegramBotApi->message['text'] == \Yii::t('app','Home');
        }

        return false;
    }

    public function atHandling()
    {
        if(isset($this->params['clearChat'])){
            $this->telegramBotApi->deleteCurrentMessage();

            if(isset($this->state['message_id']))
                $this->telegramBotApi->deleteMessage(
                    $this->telegramBotApi->chat_id,
                    $this->state['message_id']
                );
        }

        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['message'])){
            $this->telegramBotApi->deleteCurrentMessage();
            $this->telegramBotApi->message = null;
        }
    }

    public function beforeHandling()
    {
        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['message'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function afterFillAllFields(){
        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function afterOverAction()
    {
        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }
    }

    public function getFormFieldValue(){

        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if($is_inline_keyboard and isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);

            if($data and isset($data[$this->params['name']])){
                return $data[$this->params['name']];
            }
        }

        if(!$is_inline_keyboard and isset($this->telegramBotApi->update['message']['text'])){

            foreach ($this->params['options'] as $option){
                if($option[1] == $this->telegramBotApi->update['message']['text'])
                    return $option[0];
            }
        }

        return false;
    }

    public function render(){

        $update = $this->telegramBotApi->update;

        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if((bool)$this->telegramBotApi->message and $is_inline_keyboard){
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                '~',
                [
                    'reply_markup' => $this->telegramBotApi->removeCustomKeyboard()
                ]
            );
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $response['result']['message_id']
            );
        }

        $buttons = [];

        foreach ($this->params['options'] as $option){
            $buttons[] = [json_encode([$this->params['name'] => $option[0]]),$option[1]];
        }

        $keyboard = (new Keyboard())->createWithPattern($buttons,$this->params['keyboardPattern']??1)->get();

        $keyboard = $this->createNavigatorButtons($keyboard);

        $options = [
            'reply_markup' => $is_inline_keyboard?$this->telegramBotApi->makeInlineKeyboard($keyboard):$this->telegramBotApi->makeCustomKeyboard($keyboard)
        ];

        if($this->telegramBotApi->message){
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                $this->params['text'],
                $options
            );
        }else if($this->telegramBotApi->callback_query){
            $response = $this->telegramBotApi->editMessageText(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id,
                $this->params['text'],
                $options
            );
        }

        if($response['ok']){
            $this->state['message_id'] = $response['result']['message_id'];
        }
    }
}