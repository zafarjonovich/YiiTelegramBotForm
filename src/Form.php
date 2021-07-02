<?php


namespace zafarjonovich\YiiTelegramBotForm;

use zafarjonovich\Telegram\BotApi;
use zafarjonovich\Telegram\update\Update;
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

        if(is_array($botApi->update)){
            $botApi->update = new Update($botApi->update);
        }

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

        $currentFormFieldData = $bCurrentFormFieldData = $this->form->getCurrentFormField($answers);

        $currentFormFieldData = array_merge($currentFormFieldData,[
            'telegramBotApi' => $this->telegramBotApi,
            'state' => $formFieldState,
            'canGoToHome' => isset($scenario['home']),
            'buttonTextBack' => $this->form->buttonTextBack,
            'buttonTextHome' => $this->form->buttonTextHome,
        ]);

        /** @var FormField $formField */
        $formField = \Yii::createObject($currentFormFieldData);

        $formField->beforeHandling();

        if($questionKey == $currentFormFieldData['name']){

            $formField->atHandling();

            if($formField->goHome())
            {
                $formField->afterOverAction();
                $this->callback($scenario['home']);
                return;
            }

            if($formField->goBack()){

                $cache->setValue('formState',[]);

                if(empty($answers)){
                    $formField->afterOverAction();
                    $this->callback($scenario['fail']);
                }else{
                    $names = [];
                    foreach ($scenario['formFields'] as $field){
                        if(isset($answers[$field['name']]))
                            $names[] = $field['name'];
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

                $answers[$currentFormFieldData['name']] = $formFieldValue;
                foreach ($answers as $key => $answer){
                    $answers[$key] = $this->form->{$key};
                }

                $cache->setValue('formState',[]);
                $cache->setValue('answers',$answers);

                if(empty($this->form->getCurrentFormField($answers))){
                    $formField->afterOverAction();
                    $answers = array_merge($answers,$this->form->hiddenInputs);
                    $this->callback($scenario['success'],$answers);
                }else{
                    $this->render($cache);
                }
                return;
            }else{
                if($errors = $this->form->getErrors($currentFormFieldData['name'])){
                    $formField->showErrors($errors);
                    return;
                }
            }
        }

        $formFieldState = $formField->state;

        $newFormFieldData = $this->form->getCurrentFormField($answers);

        if($bCurrentFormFieldData != $newFormFieldData)
        {
            $newFormFieldData = array_merge($newFormFieldData,[
                'telegramBotApi' => $this->telegramBotApi,
                'state' => $formFieldState,
                'canGoToHome' => isset($scenario['home']),
                'buttonTextBack' => $this->form->buttonTextBack,
                'buttonTextHome' => $this->form->buttonTextHome,
            ]);

            /** @var FormField $formField */
            $formField = \Yii::createObject($newFormFieldData);
        }

        $formField->render();

        $cache->setValue('questionKey',$currentFormFieldData['name']);
        $cache->setValue('formState',$this->form->state);
        $cache->setValue('formFieldState',$formField->state);

    }
}