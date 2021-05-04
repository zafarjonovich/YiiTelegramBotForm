<?php


namespace zafarjonovich\YiiTelegramBotForm;

use yii\base\Model;

class TelegramModel extends Model
{

    public function scenariosForForm(){
        return [];
    }

    public function isFilled(Cache $cache)
    {
        return false;
    }

    public function getCurrentFormField(array $answers){

        foreach ($this->scenariosForForm()['formFields'] as $item){
            if(!isset($answers[$item['params']['name']])){
                return $item;
            }
        }

        return [];
    }

    public function validateCurrentField($currentFormFieldData,$value){

        if(is_numeric($value)){
            $value = floatval($value);
        }

        $this->setAttributes([
            $currentFormFieldData['params']['name'] => $value
        ]);

        return $this->validate([$currentFormFieldData['params']['name']]);
    }

}