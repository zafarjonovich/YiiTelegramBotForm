<?php


namespace zafarjonovich\YiiTelegramBotForm\components;

use yii\helpers\ArrayHelper;


class Cache
{
    private $content;

    public function __construct(&$cache_content){
        $this->content = $cache_content;
    }

    public function setValue($path,$value){
        ArrayHelper::setValue($this->content,$path,$value);
    }

    public function remove($key){
        ArrayHelper::remove($this->content,$key);
    }

    public function getValue($path = null,$default = null){
        return ArrayHelper::getValue($this->content,$path,$default);
    }

    public function deleteLastFormFieldValue(){
        array_pop($this->content['answers']);
    }
}