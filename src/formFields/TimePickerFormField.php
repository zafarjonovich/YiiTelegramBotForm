<?php


namespace zafarjonovich\YiiTelegramBotForm\formFields;


use zafarjonovich\Telegram\Emoji;
use zafarjonovich\YiiTelegramBotForm\Cache;
use zafarjonovich\YiiTelegramBotForm\FormField;

class TimePickerFormField extends FormField{

    const TYPE_SELECTOR = 1;

    const TYPE_TAPPER = 2;

    private $delta_minute = 10;

    private $delta_hour = 1;

    public $hour = null;

    public $minute = null;

    private $hours_of_day = 24;

    private $minutes_of_hour = 60;

    private $interval = [];

    private $keyboard_type_selector = false;

    public function afterOverAction(){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }

    }

    public function goBack(){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);
            return $data and isset($data['go']) and $data['go'] == 'back';
        }

        return false;
    }

    public function getFormFieldValue(){

        if(isset($this->telegramBotApi->callback_query['data']) and
            $data = json_decode($this->telegramBotApi->callback_query['data'],true) and
            isset($data['a'])
        ){
            return $data['a'];
        }

        return false;
    }

    public function atHandling(Cache $cache)
    {
        if((bool)$this->telegramBotApi->message){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
            $this->telegramBotApi->message = false;
        }
    }

    public function beforeHandling(Cache $cache){

        if(isset($this->telegramBotApi->callback_query['data']) and
            $data = json_decode($this->telegramBotApi->callback_query['data'],true)
        ){

            if(isset($data['h'])){
                $this->hour = $data['h'];
            }

            if(isset($data['m'])){
                $this->minute = $data['m'];
            }

            if(isset($data['u'])){
                if($data['u'] == 'h'){
                    $this->addDeltaForHour();
                }else{
                    $this->addDeltaForMinute();
                }
            }

            if(isset($data['d'])){
                if($data['d'] == 'h'){
                    $this->removeDeltaForHour();
                }else{
                    $this->removeDeltaForMinute();
                }
            }

        }

    }

    public function addDeltaForHour(){
        if($this->interval){
            $this->hour = ($this->hour + $this->delta_hour <= $this->interval['end_hour'])?$this->hour + $this->delta_hour:$this->interval['start_hour'];
        }else{
            $this->hour = ($this->hour + $this->delta_hour < $this->hours_of_day)?$this->hour + $this->delta_hour:0;
        }
    }

    public function removeDeltaForHour(){
        if($this->interval){
            $this->hour = ($this->hour - $this->delta_hour < $this->interval['start_hour'])? $this->interval['end_hour']:$this->hour - $this->delta_hour;
        }else{
            $this->hour = ($this->hour - $this->delta_hour < 0)?$this->hours_of_day - $this->delta_hour:$this->hour - $this->delta_hour;
        }
    }

    public function addDeltaForMinute(){
        if($this->interval and $this->hour == $this->interval['start_hour']){
            $this->minute = ($this->minute + $this->delta_minute >= $this->minutes_of_hour)?$this->interval['start_minute']:$this->minute + $this->delta_minute;
        }else if($this->interval and $this->hour == $this->interval['end_hour']){
            $this->minute = ($this->minute + $this->delta_minute > $this->interval['end_minute'])?0:$this->minute + $this->delta_minute;
        }else{
            $this->minute = ($this->minute + $this->delta_minute >= $this->minutes_of_hour)?0:$this->minute + $this->delta_minute;
        }
    }

    public function removeDeltaForMinute(){
        if($this->interval and $this->hour == $this->interval['start_hour']){
            $this->minute = ($this->minute - $this->delta_minute < $this->interval['start_minute'])?$this->minutes_of_hour - $this->delta_minute:$this->minute - $this->delta_minute;
        }else if($this->interval and $this->hour == $this->interval['end_hour']){
            $this->minute = ($this->minute - $this->delta_minute < 0)?$this->interval['end_minute']:$this->minute - $this->delta_minute;
        }else{
            $this->minute = ($this->minute - $this->delta_minute < 0)?$this->minutes_of_hour - $this->delta_minute:$this->minute - $this->delta_minute;
        }
    }


    private function initTimes(){

        $this->interval = $this->params['interval'] ?? [];

        if(isset($this->params['lock']['beforeNow']) and $this->params['lock']['beforeNow']){

            $locked_times = $this->params['lock']['times'] ?? [];

            foreach (range(0,23) as $hour){
                if($hour < date('H')){
                    $hour = $hour < 10?"0{$hour}":$hour;
                    $locked_times[] = $hour.':00';
                    $locked_times[] = $hour.':30';
                }else if($hour == date('H')){
                    if(0 < date('i')){
                        $locked_times[] = $hour.':00';
                    }
                    if(0 < date('i') + 30){
                        $locked_times[] = $hour.':30';
                    }
                    break;
                }
            }

            $this->params['lock']['times'] = $locked_times;
        }

        if($this->hour === null){
            $this->hour = 00;
        }

        if($this->minute == null){
            $this->minute = 00;
        }

        $this->delta_minute = 30;

        $this->interval = [
            'start_hour' => 9,
            'start_minute' => 00,
            'end_hour' => 17,
            'end_minute' => 59
        ];

        if(empty($this->interval)){
            $this->interval = [
                'start_hour' => 00,
                'start_minute' => 00,
                'end_hour' => 23,
                'end_minute' => 59
            ];
        }

    }

    private function generateSelectorKeyboard(){

        $default_callback = ['-'=>'-'];
        $keyboard = [];
        $buttons = [];

        $lock = Emoji::Decode('\\ud83d\\udd12');

        $locked_times = $this->params['lock']['times'] ?? [];

        for($h = $this->interval['start_hour']; $h <= $this->interval['end_hour']; $h += $this->delta_hour){
            if($this->hour == $this->interval['start_hour']){
                $start = $this->interval['start_minute'];
                $end = 59;
            }else if($this->hour == $this->interval['end_hour']){
                $start = 00;
                $end = $this->interval['end_minute'];
            }else{
                $start = 00;
                $end = 59;
            }
            for($m = $start; $m <= $end; $m += $this->delta_minute){
                $show = $this->format($h).':'.$this->format($m);
                if(in_array($show,$locked_times)){
                    $buttons[] = ['text'=>$lock,'callback_data'=>json_encode($default_callback)];
                }else{
                    $buttons[] = ['text'=>$show,'callback_data'=>json_encode(['a'=>$show])];
                }
            }
        }

        $keyboard = array_chunk($buttons,5);

        if((isset($this->params['canGoToBack']) and $this->params['canGoToBack']) or !isset($this->params['canGoToBack'])){
            $keyboard[] = [['text'=> \Yii::t('app','Back'),'callback_data'=>json_encode(['go'=>'back'])]];
        }

        return $keyboard;
    }

    private function generateTapSelectorKeyboard($type){
        $default_callback = ['-'=>'-'];
        $keyboard = [];
        $buttons = [];

        $lock = Emoji::Decode('\\ud83d\\udd12');
        
        $locked_times = $this->params['lock']['times'] ?? [];

        if($type == 'h'){
            for($e = $this->interval['start_hour']; $e <= $this->interval['end_hour']; $e += $this->delta_hour){
                if(in_array($e.':'.$this->minute,$locked_times)){
                    $buttons[] = ['text'=>$lock,'callback_data'=>json_encode($default_callback)];
                }else{
                    $buttons[] = ['text'=>$this->format($e),'callback_data'=>json_encode(['h' => $e, 'm' => $this->minute])];
                }
            }
        }else{
            if($this->hour == $this->interval['start_hour']){
                $start = $this->interval['start_minute'];
                $end = 59;
            }else if($this->hour == $this->interval['end_hour']){
                $start = 00;
                $end = $this->interval['end_minute'];
            }else{
                $start = 00;
                $end = 59;
            }
            for($e = $start; $e <= $end; $e += $this->delta_minute){
                if(in_array($this->hour.':'.$e,$locked_times)){
                    $buttons[] = ['text'=>$lock,'callback_data'=>json_encode($default_callback)];
                }else{
                    $buttons[] = ['text'=>$this->format($e),'callback_data'=>json_encode(['h' => $this->hour, 'm' => $e])];
                }
            }
        }

        return array_chunk($buttons,5);
    }

    private function generateTapperKeyboard(){
        $default_callback = ['-'=>'-'];

        $up = "🔼";
        $down = "🔽";
        $non = ' ';
        $ok = '✅';

        $hour = $this->hour;
        $minute = $this->minute;

        $keyboard = [
            [['text'=>$up,'callback_data'=>json_encode(['u'=>'h','h'=>$hour,'m'=>$minute])],['text'=>$non,'callback_data'=>json_encode($default_callback)],['text'=>$up,'callback_data'=>json_encode(['u'=>'m','h'=>$hour,'m'=>$minute])]] ,
            [['text'=>$this->format($hour),'callback_data'=>json_encode(['s'=>'h','h'=>$hour,'m'=>$minute])],['text'=>':','callback_data'=>json_encode($default_callback)],['text'=>$this->format($minute),'callback_data'=>json_encode(['s'=>'m','h'=>$hour,'m'=>$minute])]] ,
            [['text'=>$down,'callback_data'=>json_encode(['d'=>'h','h'=>$hour,'m'=>$minute])],['text'=>$non,'callback_data'=>json_encode($default_callback)],['text'=>$down,'callback_data'=>json_encode(['d'=>'m','h'=>$hour,'m'=>$minute])]] ,
            [['text'=>$ok,'callback_data'=>json_encode(['a'=>self::format($hour).":".self::format($minute)])]],
        ];

        if((isset($this->params['canGoToBack']) and $this->params['canGoToBack']) or !isset($this->params['canGoToBack'])){
            $keyboard[] = [['text'=> \Yii::t('app','Back'),'callback_data'=>json_encode(['go'=>'back'])]];
        }

        return $keyboard;
    }

    private function format($value){
        $value = strval($value);
        return (strlen($value) == 1)?'0'.$value:$value;
    }

    public function render(Cache $cache){

        $type = $this->params['type'] ?? self::TYPE_TAPPER;

        $this->initTimes();

        if((bool)$this->telegramBotApi->message){
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                '~',
                [
                    'reply_markup' => $this->telegramBotApi->removeCustomKeyboard()
                ]
            );
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $response['result']['message_id']
            );
        }

        if($type == self::TYPE_TAPPER){
            if(isset($this->telegramBotApi->callback_query['data']) and
                $data = json_decode($this->telegramBotApi->callback_query['data'],true) and
                isset($data['s'])
            ){
                $keyboard = $this->generateTapSelectorKeyboard($data['s']);
            }else{
                $keyboard = $this->generateTapperKeyboard();
            }
        }else{
            $keyboard = $this->generateSelectorKeyboard();
        }

        $options = [
            'reply_markup' =>$this->telegramBotApi->makeInlineKeyboard($keyboard)
        ];

        if((bool)$this->telegramBotApi->message){
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                $this->params['text'],
                $options
            );
        }else{
            $response = $this->telegramBotApi->editMessageText(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id,
                $this->params['text'],
                $options
            );
        }

        if($response['ok']){
            $cache->setValue('currentFormField.message_id',$response['result']['message_id']);
        }

    }
}