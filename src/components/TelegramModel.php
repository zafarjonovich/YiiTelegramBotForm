<?php


namespace zafarjonovich\YiiTelegramBotForm\components;

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

    public function getCurrentFormField()
    {
        return [];
    }

    public function validateCurrentField($value){
        $formField = $this->getCurrentFormField();

        $fields = [get_class($this) => [
            $formField['params']['name'] => $value
        ]];

        $this->load($fields);

        return $this->validate([$formField['params']['name']]);
    }

}