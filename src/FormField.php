<?php


namespace zafarjonovich\YiiTelegramBotForm;

use zafarjonovich\Telegram\BotApi;

class FormField
{

    /** @var BotApi\ $telegramBotApi*/
    protected $telegramBotApi;

    public $params;

    public $state = [];

    public function __construct($params,BotApi $telegramBotApi){
        $this->telegramBotApi = $telegramBotApi;
        $this->params = $params;
    }

    public function atHandling(){

    }

    public function beforeHandling(){

    }

    public function afterOverAction(){

    }

    public function showErrors($errors){

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