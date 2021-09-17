<?php


namespace zafarjonovich\YiiTelegramBotForm;

use yii\base\BaseObject;
use zafarjonovich\Telegram\BotApi;
use zafarjonovich\Telegram\Keyboard;

class FormField extends BaseObject
{

    /** @var BotApi $telegramBotApi*/
    public $telegramBotApi;

    public $state = [];

    public $canGoToHome = false;

    public $canGoToBack = true;

    public $buttonTextBack = 'Back';

    public $buttonTextHome = 'Home';

    public $isInlineKeyboard = false;

    public $clearChat = false;

    public $keyboard = [];

    public $name;

    public $text;

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

        if($this->isInlineKeyboard){
            if($this->canGoToBack)
                $keyboard->addCallbackDataButton($this->buttonTextBack,json_encode(['go'=>'back']));

            if($this->canGoToHome)
                $keyboard->addCallbackDataButton($this->buttonTextHome,json_encode(['go'=>'home']));

            return $keyboard->initInlineKeyboard();
        }else{
            if($this->canGoToBack)
                $keyboard->addCustomButton($this->buttonTextBack);

            if($this->canGoToHome)
                $keyboard->addCustomButton($this->buttonTextHome);

            return $keyboard->initCustomKeyboard();
        }
    }
}