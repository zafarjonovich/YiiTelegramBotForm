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

    public function __construct($callback,Cache $cache,TelegramModel $form,BotApi $botApi){
        $this->callback = $callback;
        $this->cache = $cache;
        $this->telegramBotApi = $botApi;
        $this->form = $form;
    }

    private function callback($data,$form_values = []){
        $callback = $this->callback;
        $callback($data['method'],array_merge($data['params'],$form_values));
    }

    public function render(){

        $cache = $this->cache;

        $questionKey = $cache->getValue('questionKey','');
        $answers = $cache->getValue('answers',[]);
        $formState = $cache->getValue('formState',[]);
        $formFieldState = $cache->getValue('formFieldState',[]);

        $this->form->setAttributes($answers);
        $this->form->state = $formState;

        $scenario = $this->form->scenariosForForm();

        $currentFormFieldData = $this->form->getCurrentFormField($answers);

        /** @var FormField $formField */
        $formField = new $currentFormFieldData['class']($currentFormFieldData['params'],$this->telegramBotApi);

        $formField->state = $formFieldState;

        $formField->beforeHandling();

        if($questionKey == $currentFormFieldData['params']['name']){

            $formField->atHandling();

            if($formField->goBack()){

                $cache->setValue('formState',[]);

                if(empty($answers)){
                    $formField->afterOverAction();
                    $this->callback($scenario['fail']);
                }else{
                    $names = [];
                    foreach ($scenario['formFields'] as $field){
                        if(isset($answers[$field['params']['name']]))
                            $names[] = $field['params']['name'];
                    }
                    $last_name = $names[(count($names)-1)];
                    unset($answers[$last_name]);
                    $cache->setValue('answers',$answers);
                    $this->render($cache);
                }
                return;
            }

            if(
                (($formFieldValue = $formField->getFormFieldValue()) !== false) and
                $this->form->validateCurrentField($currentFormFieldData,$formFieldValue)
            ){

                $answers[$currentFormFieldData['params']['name']] = $formFieldValue;
                foreach ($answers as $key => $answer){
                    $answers[$key] = $this->form->{$key};
                }

                $cache->setValue('formState',[]);
                $cache->setValue('answers',$answers);

                if(empty($this->form->getCurrentFormField($answers))){
                    $formField->afterOverAction();
                    $this->callback($scenario['success'],$answers);
                }else{
                    $this->render($cache);
                }
                return;
            }else{
                if($errors = $this->form->getErrors($currentFormFieldData['params']['name'])){
                    $formField->showErrors($errors);
                    return;
                }
            }
        }

        $formFieldState = $formField->state;

        $currentFormFieldData = $this->form->getCurrentFormField($answers);

        /** @var FormField $formField */
        $formField = new $currentFormFieldData['class']($currentFormFieldData['params'],$this->telegramBotApi);
        $formField->state = $formFieldState;

        $formField->render();

        $cache->setValue('questionKey',$currentFormFieldData['params']['name']);
        $cache->setValue('formState',$this->form->state);
        $cache->setValue('formFieldState',$formField->state);

    }
}