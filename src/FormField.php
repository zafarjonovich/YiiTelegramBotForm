<?php


namespace zafarjonovich\YiiTelegramBotForm;

use zafarjonovich\Telegram\BotApi;
use zafarjonovich\Telegram\Keyboard;

class FormField
{

    /** @var BotApi\ $telegramBotApi*/
    protected $telegramBotApi;

    public $params;

    public $state = [];

    public $show_home_button = false;

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

    public function goHome(){
        return false;
    }
    
    public function goBack(){
        return false;
    }

    public function render(){
        return false;
    }

    public function createNavigatorButtons($keyboard)
    {
        $keyboard = new Keyboard($keyboard);
        if((isset($this->params['canGoToBack']) and $this->params['canGoToBack']) or !isset($this->params['canGoToBack']))
            $keyboard->addButton(\Yii::t('app','Back'),json_encode(['go'=>'back']));


        if($this->show_home_button)
            $keyboard->addButton(\Yii::t('app','Home'),json_encode(['go'=>'home']));

        return $keyboard->get();
    }
}