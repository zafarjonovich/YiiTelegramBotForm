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
        $currentFormFieldAttributes = $cache->getValue('currentFormField',['name'=>'','message_id'=>null,'params'=>[]]);

        $this->form->params = $currentFormFieldAttributes['params'];

        $this->form->setAttributes($answers);

        $scenario = $this->form->scenariosForForm();

        $currentFormFieldData = $this->form->getCurrentFormField($answers);

        /** @var FormField $formField */
        $formField = new $currentFormFieldData['class']($currentFormFieldData['params'],$this->telegramBotApi);

        $formField->beforeHandling($cache);

        if($currentFormFieldAttributes['name'] == $currentFormFieldData['params']['name']){

            $formField->atHandling($cache);

            if($formField->goBack()){

                $cache->setValue('currentFormField.params',[]);

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

                $new_answers = $cache->getValue('answers',[]);
                $new_answers[$currentFormFieldData['params']['name']] = $formFieldValue;
                foreach ($new_answers as $key => $answer){
                    $new_answers[$key] = $this->form->{$key};
                }

                $cache->setValue('currentFormField.params',[]);
                $cache->setValue('answers',$new_answers);

                if(empty($this->form->getCurrentFormField($new_answers))){
                    $formField->afterOverAction();
                    $this->callback($scenario['success'],$new_answers);
                }else{
                    $this->render($cache);
                }
                return;
            }else{
                if($errors = $this->form->getErrors($currentFormFieldData['params']['name'])){
                    $formField->showErrors($cache,$errors);
                    return;
                }
            }
        }

        $cache->setValue('currentFormField.params',$this->form->params);
        $cache->setValue('currentFormField.name',$currentFormFieldData['params']['name']);

        $formField->render($cache);
    }
}