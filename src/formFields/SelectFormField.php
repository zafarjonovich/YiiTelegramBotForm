<?php


namespace zafarjonovich\YiiTelegramBotForm\formFields;


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

    public function afterFillAllFields(){
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

    public function render(Cache $cache){

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

        $keyboard = [];

        foreach ($this->params['options'] as $option){
            $keyboard[] = [['text' => $option[1],'callback_data'=>json_encode([$this->params['name'] => $option[0]])]];
        }

        if((isset($this->params['canGoToBack']) and $this->params['canGoToBack']) or !isset($this->params['canGoToBack'])){
            $keyboard[] = [['text' => \Yii::t('app','Back'),'callback_data'=>json_encode(['go' => 'back'])]];
        }

        $options = [
            'reply_markup' => $is_inline_keyboard?$this->telegramBotApi->makeInlineKeyboard($keyboard):$this->telegramBotApi->makeCustomKeyboard($keyboard)
        ];

        if(isset($update['callback_query'])){
            $response = $this->telegramBotApi->editMessageText(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id,
                $this->params['text'],
                $options
            );
        }else{
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                $this->params['text'],
                $options
            );
        }

        $cache->setValue('currentFormField.message_id',$response['result']['message_id']);
    }
}