<?php
	
require_once("CryptAES.php");	

class REST 
{
		
	public $_allow = [];
	public $_content_type = "application/json";
	public $_request = [];
		
	private $_method = "";		
	private $_code = 200;
		
	public function __construct()
	{
		$this->inputs();
	}
		
	public function get_referer()
	{
		return $_SERVER['HTTP_REFERER'];
	}

	public function response($data, $status)
	{
		//$this->_code = ($status) ? $status : 200;
		$this->set_headers();
		echo $data;		
		exit;
	}
		
	private function get_status_message()
	{
		$status = [
			100 => 'Continue',  
			101 => 'Switching Protocols',  
			200 => 'OK',
			201 => 'Created',  
			202 => 'Accepted',  
			203 => 'Non-Authoritative Information',  
			204 => 'No Content',  
			205 => 'Reset Content',  
			206 => 'Partial Content',  
			300 => 'Multiple Choices',  
			301 => 'Moved Permanently',  
			302 => 'Found',  
			303 => 'See Other',  
			304 => 'Not Modified',  
			305 => 'Use Proxy',  
			306 => '(Unused)',  
			307 => 'Temporary Redirect',  
			400 => 'Bad Request',  
			401 => 'Unauthorized',  
			402 => 'Payment Required',  
			403 => 'Forbidden',  
			404 => 'Not Found',  
			405 => 'Method Not Allowed',  
			406 => 'Not Acceptable',  
			407 => 'Proxy Authentication Required',  
			408 => 'Request Timeout',  
			409 => 'Conflict',  
			410 => 'Gone',  
			411 => 'Length Required',  
			412 => 'Precondition Failed',  
			413 => 'Request Entity Too Large',  
			414 => 'Request-URI Too Long',  
			415 => 'Unsupported Media Type',  
			416 => 'Requested Range Not Satisfiable',  
			417 => 'Expectation Failed',  
			500 => 'Internal Server Error',  
			501 => 'Not Implemented',  
			502 => 'Bad Gateway',  
			503 => 'Service Unavailable',  
			504 => 'Gateway Timeout',  
			505 => 'HTTP Version Not Supported'
		];

		return ($status[$this->_code]) ? $status[$this->_code] : $status[500];
	}
		
	public function get_request_method()
	{
		return $_SERVER['REQUEST_METHOD'];
	}
		
	private function inputs()
	{
		switch($this->get_request_method()) {
			case "POST":
				$this->_request = $this->cleanInputs($_POST);
				break;
			case "GET":
			case "DELETE":
				$this->_request = $this->cleanInputs($_GET);
				break;
			case "PUT":
				parse_str(file_get_contents("php://input"),$this->_request);
				$this->_request = $this->cleanInputs($this->_request);
				break;
			default:
				$this->response('',406);
				break;
		}
	}		
		
	private function cleanInputs($data)
	{
		$clean_input = [];
		if (is_array($data)) {
			foreach($data as $k => $v) {
				$clean_input[$k] = $this->cleanInputs($v);
			}
		} else  {
			if(get_magic_quotes_gpc()) {
				$data = trim(stripslashes($data));
			}
			$data = strip_tags($data);
			$clean_input = trim($data);
		}
		return $clean_input;
	}		
			
	function multid_sort($arr, $index)
	{
		$b = [];
		$c = [];
		foreach ($arr as $key => $value) {
			$b[$key] = $value[$index];
		}
		asort($b);
		foreach ($b as $key => $value) {
			$c[] = $arr[$key];
		}
		return $c;
	}
		
	function cmp_function($a, $b) 
	{
		if ($a == $b) return 0;
		 return ($a > $b) ? -1 : 1;
	}
			
	function nicetime($date) 
	{
		if(empty($date)) {
			return "No date provided";
		}
			
		$periods         = array("second", "minute", "h", "d", "w", "month", "year", "decade");
		$lengths         = array("60","60","24","7","4.35","12","10");
		$now             = time();
		$unix_date       = strtotime($date);
			
		// check validity of date
		if(empty($unix_date)) {    
			return "Bad date";
		}
		// is it future date or past date
		if($now > $unix_date) {    
			$difference     = $now - $unix_date;
			$tense         = "";	
		} else {
			$difference     = $unix_date - $now;
			$tense         = "";
		}
		for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
			$difference /= $lengths[$j];
		}
		$difference = round($difference);
		if($difference != 1) {
			$periods[$j].= "";
		}
		return "$difference $periods[$j] {$tense}";
	}
		
	function time_elapsed_string($datetime, $full = false) 
	{
		$currentTime = gmdate("Y-m-d H:i:s");
		echo $datetime;
		$now = new DateTime;
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);
		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;
		$string = [
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		];
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}
		
		if (!$full) $string = array_slice($string, 0, 1);
			return $string ? implode(', ', $string) . ' ago' : 'just now';
	}	
	
		
	function strafter($string, $substring)
	{
		$pos = strpos($string, $substring);
		if ($pos === false)
		   return $string;
		else  
		   return(substr($string, $pos+strlen($substring)));
	}

	private function set_headers()
	{
		header("HTTP/1.1 ".$this->_code." ".$this->get_status_message());
		header("Content-Type:".$this->_content_type);
	}
}	
?>