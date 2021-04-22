<?php


namespace zafarjonovich\YiiTelegramBotForm\components;

use zafarjonovich\YiiTelegramBotForm\components\TelegramBotApiHelper;

class FormField
{

    /** @var TelegramBotApiHelper $telegramBotApi*/
    protected $telegramBotApi;

    protected $params;

    public function __construct($params,TelegramBotApiHelper $telegramBotApi){
        $this->telegramBotApi = $telegramBotApi;
        $this->params = $params;
    }

    public function getFormFieldValue(){
        return false;
    }

    public function isInlineMode(){
        return false;
    }
    
    public function goBack(){
        return false;
    }

    public function render(){
        return false;
    }
}