<?php

namespace App\Modules;

use App\Models\Setting;
use DateTime;
use Illuminate\Support\Facades\Log;

class Functions {
    public static $jsdata = [];
    public static function randomString($length=10, $chartypes='all') {
        $chartypes_array=explode(",", $chartypes);
        // задаем строки символов. 
        //Здесь вы можете редактировать наборы символов при необходимости
        $lower = 'abcdefghijklmnopqrstuvwxyz'; // lowercase
        $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'; // uppercase
        $numbers = '1234567890'; // numbers
        $special = '-'; //special characters
        $chars = "";
        // определяем на основе полученных параметров, 
        //из чего будет сгенерирована наша строка.
        if (in_array('all', $chartypes_array)) {
            $chars = $lower . $upper. $numbers . $special;
        } else {
            if(in_array('lower', $chartypes_array))
                $chars = $lower;
            if(in_array('upper', $chartypes_array))
                $chars .= $upper;
            if(in_array('numbers', $chartypes_array))
                $chars .= $numbers;
            if(in_array('special', $chartypes_array))
                $chars .= $special;
        }
        // длина строки с символами
        $chars_length = strlen($chars) - 1;
        // создаем нашу строку,
        //извлекаем из строки $chars символ со случайным 
        //номером от 0 до длины самой строки
        $string = $chars[rand(0, $chars_length)];
        // генерируем нашу строку
        for ($i = 1; $i < $length; $i = strlen($string)) {
            // выбираем случайный элемент из строки с допустимыми символами
            $random = $chars[rand(0, $chars_length)];
            // убеждаемся в том, что два символа не будут идти подряд
            if ($random != $string[$i - 1]) $string .= $random;
        }
        // возвращаем результат
        return $string;
    }
    public static function comboOptions($models,$id,$value,$select_id=null,$jsdata = false,$value_prefix='',$value_sufix='',$option_class = null) {
        $str = "";
        if($jsdata) self::$jsdata = [];
        foreach($models as $model) {
            if(is_array($model)) $model = (object)$model;
            if($jsdata) self::$jsdata[] = (array)$model;
            $str .="<option value='{$model->$id}' ".($option_class ? "class='$option_class' " : '').($select_id == $model->$id ? 'selected' : '').">{$value_prefix}{$model->$value}{$value_sufix}</option>";
        }
        return $str;
    }
    public static function comboIerarhOptions($models,$id,$value,$select_id=null) {
        $str = "";  
        foreach($models as $model) {
            if(($children = $model->children())->toArray()) {
                $str .="<optgroup label='{$model->$value}'>";
                $str .=self::comboIerarhOptions($children,$id,$value,$select_id);
                $str .="</optgroup>";
            }
            else {
                $str .="<option value='{$model->$id}' ".($select_id == $model->$id ? 'selected' : '').">{$model->$value}</option>";            
            }            
        }
        return $str;     
    }
    public static function sendpost($params,$url) {        
        $ch = curl_init($url);
        $params = http_build_query($params);
        Log::info("sendpost: $url ".$params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
        curl_close($ch);	                         
        return $data; //string
    }
    public static function sendhook($params,$url,$token) {
        function shifr(array $params,string $token) 
        {             
            ksort($params);
            $hash = collect($params)->implode('&').'&'.$token;
            $params['hash'] = hash('sha256',$hash);
            return $params;
        }
        $params = shifr($params,$token);
        Log::info("sendhook: $url ");
        return self::sendpost($params,$url); //string
    }
    public static function sysToken($code) {
        /*
        $token = \App\Models\Token::where('t_key',$code)->first();
        return $token ? $token->t_value : null;
        */
    }
    public static function settingValue($code) {
        $res = Setting::where('set_key',$code)->first();
        if($res) return $res->set_value;
        return null;
    }
    public static function myFileGetContents($url) {
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );        
        $html = file_get_contents($url, false,stream_context_create($arrContextOptions));
        return $html;
    }
    public static function mb_ucfirst($string, $enc = 'UTF-8')
    {
        return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) . 
            mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }
    public static function rusDate($date) {
        if(is_int($date)) return date('d.m.Y',$date);
        $date = strtotime($date);
        return date('d.m.Y',$date);
    }
    public static function rusDateTime($date,$addHours=0) {
        if(!is_int($date)) $date = strtotime($date);       
        $date += $addHours*3600;
        return date('d.m.Y H:i:s',$date);
    }
    public static function numbers($n, $titles) {
        $cases = array(2, 0, 1, 1, 1, 2);
        return $titles[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]];
    }
    public static function time_ago($datetime) {
        if(is_string($datetime)) $datetime = new DateTime($datetime);
        $interval = date_create('now')->diff($datetime);
        
        if ($interval->y >= 1) {return $interval->y.' '.self::numbers($interval->y, ['год',"года","лет"]);}
        if ($interval->m >= 1) {return $interval->m.' '.self::numbers($interval->m, ['месяц',"месяца","месяцев"]);}
        if ($interval->d >= 1) {return $interval->d.' '.self::numbers($interval->d, ['день',"дня","дней"]);}
        if ($interval->h >= 1) {return $interval->h.' '.self::numbers($interval->h, ['час',"часа","часов"]);}
        if ($interval->i >= 1) {return $interval->i.' '.self::numbers($interval->i, ['минуту',"минуты","минут"]);}
        return $interval->s.' секунд';
    }
    public static function rus2translit($string) {
		$converter = array(
			'а' => 'a',   'б' => 'b',   'в' => 'v',
			'г' => 'g',   'д' => 'd',   'е' => 'e',
			'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
			'и' => 'i',   'й' => 'y',   'к' => 'k',
			'л' => 'l',   'м' => 'm',   'н' => 'n',
			'о' => 'o',   'п' => 'p',   'р' => 'r',
			'с' => 's',   'т' => 't',   'у' => 'u',
			'ф' => 'f',   'х' => 'h',   'ц' => 'c',
			'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
			'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
			'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

			'А' => 'A',   'Б' => 'B',   'В' => 'V',
			'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
			'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
			'И' => 'I',   'Й' => 'Y',   'К' => 'K',
			'Л' => 'L',   'М' => 'M',   'Н' => 'N',
			'О' => 'O',   'П' => 'P',   'Р' => 'R',
			'С' => 'S',   'Т' => 'T',   'У' => 'U',
			'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
			'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
			'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
			'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
		);
		return strtr($string, $converter);
	}
	public static function str2url($str) {
		// переводим в транслит
		$str = self::rus2translit($str);
		// в нижний регистр
		$str = strtolower($str);
		// заменям все ненужное нам на "-"
		$str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
		// удаляем начальные и конечные '-'
		$str = trim($str, "-");
		return $str;
	}
    public static function myRoute($routeName, $params) {
        $url = route($routeName, $params);
        return str_replace(env('BOT_DOMAIN'), env('WEBHOOK_DOMAIN'),$url);
    }
    public static function getSeason() {
        $m = date('m');
        if(in_array($m,[1,2,12])) return 1;
        if(in_array($m,[3,4,5])) return 2;
        if(in_array($m,[6,7,8])) return 3;
        if(in_array($m,[9,10,11])) return 4;
    }
}
