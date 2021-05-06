<?php


namespace zafarjonovich\YiiTelegramBotForm;

use yii\helpers\ArrayHelper;


class Cache
{
    public $content;

    public function __construct(&$cache_content){
        $this->content = &$cache_content;
        if(!isset($this->content['answers'])){
            $this->content['answers'] = [];
        }
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
}