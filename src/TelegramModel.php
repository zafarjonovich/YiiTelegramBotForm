<?php


namespace zafarjonovich\YiiTelegramBotForm;

use yii\base\Model;

class TelegramModel extends Model
{
    public $state = [];

    public $hiddenInputs = [];

    public $buttonTextBack = 'Back';

    public $buttonTextHome = 'Home';

    public function scenariosForForm(){
        return [];
    }

    public function isFilled(Cache $cache)
    {
        return false;
    }

    public function getCurrentFormField(array $answers){

        foreach ($this->scenariosForForm()['formFields'] as $item){
            if(!isset($answers[$item['name']])){
                return $item;
            }
        }

        return [];
    }

    public function validateCurrentField($currentFormFieldData,$value){
        $this->{$currentFormFieldData['name']} = $value;

        return $this->validate([$currentFormFieldData['name']]);
    }

}