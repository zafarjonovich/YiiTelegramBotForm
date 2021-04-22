<?php


namespace zafarjonovich\YiiTelegramBotForm\components;

use zafarjonovich\YiiTelegramBotForm\components\TelegramBotApiHelper;
use zafarjonovich\YiiTelegramBotForm\components\Cache;


class Form{

    /** @var TelegramModel $form */
    private $form;

    /** @var TelegramBotApiHelper $telegramBotApi*/
    private $telegramBotApi;

    /** @var Cache $cache */
    private $cache;


    public function __construct(TelegramModel $form,&$cache_content,$telegram_methods){
        $this->cache = new Cache($cache_content);
        $this->telegramBotApi = new TelegramBotApiHelper($telegram_methods);
        $this->form = $form;
    }

    private function callSuccessCallback($form_values){

    }

    private function callFailCallback(){

    }


    public function render(){

        $question = $this->form->getCurrentFormField();

        /** @var FormField $formField */
        $formField = new $question['class']($question['params'],$this->telegramBotApi);

        if($formField->goBack()){

            $this->cache->deleteLastFormFieldValue();

            if($this->cache->getValue('answers',[])){
                $this->callFailCallback();
            }else{
                $this->render();
            }

            return;
        }

        $currentFormFieldKey = $this->cache->getValue('currentFormFieldClassName','');

        if($currentFormFieldKey == get_class($formField)){
            if(
                $formFieldValue = $formField->getFormFieldValue() and
                $this->form->validateCurrentField($formFieldValue)
            ){
                $this->cache->setValue('answers.'.$question['params']['name'],$formFieldValue);

                if($this->form->isFilled($this->cache)){
                    $this->callSuccessCallback($this->cache->getValue('answers'));
                }else{
                    $this->render();
                }
            }else{
                $errors = $this->form->getErrors();

            }
            return;
        }

        $this->cache->setValue('currentFormFieldClassName',get_class($formField));
        $formField->render();
    }
}