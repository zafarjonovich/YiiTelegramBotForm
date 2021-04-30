<?php


namespace zafarjonovich\YiiTelegramBotForm\components\formFields;


use zafarjonovich\YiiTelegramBotForm\Cache;
use zafarjonovich\YiiTelegramBotForm\FormField;

class CalendarFormField extends FormField{

    private $days = ['M','T','W','T','F','S','S'];

    private $months = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December'
    ];

    private $lock = false;

    private $date = 'now';

    public function goBack(){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);
            return $data and $data['go'] == 'back';
        }

        return false;
    }

    public function beforeHandling(Cache $cache){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);
            if($data and isset($data['todate'])){
                $this->date = $data['todate'];
            }
        }
    }

    public function afterFillAllFields(){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $this->telegramBotApi->deleteMessage(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id
            );
        }

    }

    public function getFormFieldValue(){

        if(isset($this->telegramBotApi->update['callback_query'])){
            $data = json_decode($this->telegramBotApi->update['callback_query']['data'],true);

            if($data and $data[$this->params['name']]){
                return $data[$this->params['name']];
            }
        }

        return false;
    }

    private function getKeyboard(){

        $date = new \DateTime($this->date);

        $this->lock = isset($this->params['lock']) and !empty($this->params['lock']);

        $keyboard = [];
        $count_days_of_week = 7;
        $default_callback = ['-'=>'-'];
        $lock = '🔒';

        $year = $date->format('Y');
        $month = $date->format('m');

        $keyboard[] = [['text'=>"{$year}-{$this->months[($month-1)]}",'callback_data'=>json_encode($default_callback)]];
        $keyboard[] = array_map(function ($day) use ($default_callback){
            return ['text'=>$day,'callback_data'=>json_encode($default_callback)];
        },$this->days);

        $row = count($keyboard);

        if(($first_q = date("N",strtotime("First day of {$year}-{$month}"))-1)%$count_days_of_week){
            for($i=0;$i<$first_q;$i++){
                $keyboard[$row][] = ['text'=>' ','callback_data'=>json_encode($default_callback)];
            }
        }

        $count_of_days = date("d",strtotime("Last day of {$year}-{$month}"));

        $lock_days = [];
        if(isset($this->params['lock']['days']) and !empty($this->params['lock']['days'])){
            $lock_days = array_map(function ($day){
                return strtotime($day);
            },$this->params['lock']['days']);
        }

        for($d=1;$d<=$count_of_days;$d++){

            $name = $d;
            $d = (strlen($d) == 1)?'0'.$d:$d;
            $callback = [$this->params['name'] => "{$year}-{$month}-{$d}"];

            if(!isset($keyboard[$row])){
                $keyboard[$row] = [];
            }

            $lock_day = false;

            if(isset($this->params['lock']['every'])){
                if(isset($this->params['lock']['every']['week']) and
                    !empty($this->params['lock']['every']['week']) and
                    ($l = in_array((count($keyboard[$row])+1),$this->params['lock']['every']['week']))
                ){
                    $lock_day = $l;
                }
                if(isset($this->params['lock']['every']['month']) and
                    !empty($this->params['lock']['every']['month']) and
                    ($l = in_array($d,$this->params['lock']['every']['month']))
                ){
                    $lock_day = $l;
                }
            }

            if((strtotime("{$year}-{$month}-{$d}") < strtotime('Today') and
                isset($this->params['lock']['beforeNow']) and $this->params['lock']['beforeNow'])){
                $lock_day = true;
            }

            if(in_array(strtotime("{$year}-{$month}-{$d}"),$lock_days)){
                $lock_day = true;
            }

            if($lock_day){
                $name = $lock;
                $callback = $default_callback;
            }

            $keyboard[$row][] = ['text'=>$name,'callback_data'=>json_encode($callback)];

            if(count($keyboard[$row])%$count_days_of_week == 0){
                $row++;
            }
        }

        $last_q = (($q = ($first_q+$count_of_days)%$count_days_of_week) != 0)?$count_days_of_week-$q:0;

        if($last_q){
            for($i=0;$i<$last_q;$i++){
                $keyboard[$row][] = ['text'=>' ','callback_data'=>json_encode($default_callback)];
            }
        }

        if(count($keyboard[$row]) > 0){
            $row++;
        }

        if(
            strtotime("23:59",strtotime("Last day of",strtotime("{$year}-{$month}"))) > strtotime("00:01",strtotime("First day of",time())) and
            strtotime("00:01",strtotime("First day of",time())) != strtotime("00:01",strtotime("First day of",strtotime("{$year}-{$month}")))
        ){
            $prev_callback = ['todate'=>date("Y-m",strtotime("First day of last month",strtotime("{$year}-{$month}")))];
            $keyboard[$row][] = ['text' => '⬅️', 'callback_data' => json_encode($prev_callback)];
        }

        $next_callback = ['todate'=>date("Y-m",strtotime("First day of next month",strtotime("{$year}-{$month}")))];
        $keyboard[$row][] = ['text'=>'➡️','callback_data'=>json_encode($next_callback)];

        return $keyboard;
    }

    public function render(Cache $cache){

        $update = $this->telegramBotApi->update;

        $is_inline_keyboard = $this->params['is_inline_keyboard'] ?? true;

        if((bool)$this->telegramBotApi->message and $is_inline_keyboard){
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

        $keyboard = $this->getKeyboard();

        $options = [
            'reply_markup' =>$this->telegramBotApi->makeInlineKeyboard($keyboard)
        ];

        if(isset($update['callback_query'])){
            $response = $this->telegramBotApi->editMessageText(
                $this->telegramBotApi->chat_id,
                $this->telegramBotApi->message_id,
                $this->params['text'],
                $options
            );
        }else{
            $response = $this->telegramBotApi->sendMessage(
                $this->telegramBotApi->chat_id,
                $this->params['text'],
                $options
            );
        }

        $cache->setValue('currentFormField.message_id',$response['result']['message_id']);
    }
}