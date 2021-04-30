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

        $fields = [array_pop(explode('\\', get_class($this))) => [
            $currentFormFieldData['params']['name'] => $value
        ]];

        $this->load($fields);

        return $this->validate([$currentFormFieldData['params']['name']]);
    }

}