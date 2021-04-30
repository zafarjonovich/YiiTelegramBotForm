<?php


namespace zafarjonovich\YiiTelegramBotForm;

use zafarjonovich\Telegram\BotApi;
use zafarjonovich\YiiTelegramBotForm\Cache;


class Form{

    /** @var TelegramModel $form */
    private $form;

    /** @var BotApi $telegramBotApi*/
    private $telegramBotApi;

    /** @var Cache $cache */
    private $cache;

    private $callback;

    public function __construct($callback,TelegramModel $form,BotApi $botApi){
        $this->callback = $callback;
        $this->telegramBotApi = $botApi;
        $this->form = $form;
    }

    private function callback($data,$form_values = []){
        $callback = $this->callback;
        $callback($data['method'],array_merge($data['params'],$form_values));
    }

    public function render(Cache $cache){

        $answers = $cache->getValue('answers',[]);

        $scenario = $this->form->scenariosForForm();

        $currentFormFieldData = $this->form->getCurrentFormField($answers);

        /** @var FormField $formField */
        $formField = new $currentFormFieldData['class']($currentFormFieldData['params'],$this->telegramBotApi);

        $currentFormFieldKey = $cache->getValue('currentFormField',['name'=>'','message_id'=>null]);

        $formField->beforeHandling($cache);

        if($currentFormFieldKey['name'] == $currentFormFieldData['params']['name']){

            $formField->atHandling($cache);

            if($formField->goBack()){
                if(empty($answers)){
                    $this->callback($scenario['fail']);
                }else{
                    $cache->deleteLastFormFieldValue($scenario['formFields']);
                    $this->render($cache);
                }
                return;
            }

            if(
                $formFieldValue = $formField->getFormFieldValue() and
                $this->form->validateCurrentField($currentFormFieldData,$formFieldValue)
            ){
                $cache->setValue('answers.'.$currentFormFieldData['params']['name'],$formFieldValue);
                $new_answers = $cache->getValue('answers',[]);

                if(empty($this->form->getCurrentFormField($new_answers))){
                    $formField->afterFillAllFields();
                    $this->callback($scenario['success'],$new_answers);
                }else{
                    $this->render($cache);
                }

                return;
            }else{
                $errors = $this->form->getErrors($currentFormFieldData['params']['name']);
                $formField->showErrors($cache,$errors);
            }

        }

        $cache->setValue('currentFormField',['name'=>$currentFormFieldData['params']['name']]);
        $formField->render($cache);
    }
}