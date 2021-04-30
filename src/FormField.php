<?php


namespace zafarjonovich\YiiTelegramBotForm;

use zafarjonovich\Telegram\BotApi;

class FormField
{

    /** @var BotApi\ $telegramBotApi*/
    protected $telegramBotApi;

    protected $params;

    public function __construct($params,BotApi $telegramBotApi){
        $this->telegramBotApi = $telegramBotApi;
        $this->params = $params;
    }

    public function atHandling(Cache $cache){

    }

    public function beforeHandling(Cache $cache){

    }

    public function afterFillAllFields(){

    }

    public function showErrors($cache,$errors){

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

    public function render(Cache $cache){
        return false;
    }
}