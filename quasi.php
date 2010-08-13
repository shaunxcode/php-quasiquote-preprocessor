<?php

function valueAtIs($array, $index, $value, $default = false) {
	return isset($array[$index]) ? ($array[$index] == $value) : $default;
}

class QuasiPreprocessor
{
	private $phpCode;
	private $strings = array();

	public function __construct($code)
	{
		$this->phpCode = $this->replaceQuasiquotes($this->stripComments($this->stripFloats($this->stripStrings($code))));
	}

	public function stripStrings($code, $stringType = '__STRING__', $placeHolder = true) 
	{
		$max = strlen($code);

		$quoteType = $stringType == '__STRING__' ? "'" : '"';
		
		while(($start = strpos($code, $quoteType)) !== false){

			$end = false;
			$pos = $start;
			$skip = false;
	      
			while(!$end || $pos < $max){
	        
				$char = $code[++$pos];
				if($char == $quoteType && !$skip){
					$sub = substr($code, $start, ($pos-$start)+1);
					$key = $stringType . count($this->strings);
					$code = str_replace($sub, $placeHolder ? $key : '', $code);
					$this->strings[$key] = $sub;
					$end = $pos;
					break;
				}
				$skip = ($char =='\\');
			}
		}
		return $code;
	}

	private function stripComments($code)
	{
		return $code;
	}
	
	private function stripFloats($code)
	{
		return $code;
	}

	public function asPhp()
	{
		return str_replace(array_keys($this->strings), $this->strings, $this->phpCode);
	}

	private function replaceQuasiquotes($code)
	{
		$max = strlen($code);
		
		while(($start = strpos($code, '`')) !== false){

			$end = false;
			$pos = $start;
			$open = 0;	      

			while(!$end || $pos < $max) {
	        		
				$char = $code[++$pos];
				if($char == '(') {
					$open++;
					continue;
				}

				if($char == ')') {
					$open--;
					if($open == 0) {
						$sub = substr($code, $start, ($pos-$start)+1);
						$code = str_replace($sub, $this->parse($this->tokenize(substr($sub, 2, -1))), $code);
						$end = $pos;
						break;
					} 
				}
			}
		}
		return $code;
	}

	private function tokenize($code)
	{
		return explode(
			' ', 
			trim(
				ereg_replace(
					' + ', 
					' ', 
					str_replace(
						array('(', ')', '`', '@', ','), 
						array(' ( ', ' ) ', ' ` ', ' @ ', ' , '), 
						$code))));
	}

	private function parse($tokens, $isArray = false)
	{
		$arrayValue = array();
		$spliceList = array();
		$index = 0;
		foreach($tokens as $char)
		{
			if($char == '(') {
				$array = array();
				$parenCount = 1;
				continue;
			}
			
			if(isset($array)) {
				if($char == '(') {
					$parenCount++;
					if($parenCount > 1) {
						$array[] = $char;
					}
					continue;
				}
				
				if($char == ')') {
					$parenCount--;
					if($parenCount < 1) {
						$char = $this->parse($array, true);
						unset($array);
					} else {
						$array[] = $char;
						continue;
					}
				} else {
					$array[] = $char;
					continue;
				}
			}

			if($char == ',') {
				$unquote = true;
				continue;
			}

			if(isset($unquote) && $char == '@') {
				$splice = true;
				continue;
			}

			if(isset($unquote) || isset($splice)) {
				if($char[0] !== '$') {
					$char = '$' . $char;
				}
			}

			if(isset($unquote) && isset($splice)) {
				$spliceList[$index] = $char;
				$char = null;
				unset($unquote);
				unset($splice);
			} else if(isset($unquote)) {
				$char = array('type' => 'variable', 'value' => $char);
				unset($unquote);
			} else {
				$char = array('type' => 'scalar', 'value' => $char);
			}
	
			$arrayValue[$index++] = $char;	
		}

		foreach($arrayValue as $index => $node) {
			$arrayValue[$index] = is_null($node) ? 'null' : ($node['type'] == 'scalar' ? "'{$node['value']}'" : $node['value']);
		}

		$arrayValue = 'array(' . implode(',', $arrayValue) . ')';

		if(!empty($spliceList)) {
			$spliceList = array_reverse($spliceList, true);
			foreach($spliceList as $atIndex => $value) {
				$arrayValue = 'arraySplice(' . $arrayValue . ", $atIndex, 1, $value)";
			}
		}
	
		//echo json_encode($tokens) . "\n";	
		//echo $arrayValue . "\n";
		return $arrayValue;
	}
}

$p = new QuasiPreprocessor(file_get_contents($argv[1]));
echo $p->asPhp();


