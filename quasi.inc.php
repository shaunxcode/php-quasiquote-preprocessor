<?php

function arraySplice($array, $pos, $len, $value) {
	echo "replace at $pos for $len with " . json_encode($value) . "\n";
	echo "before: " . json_encode($array) . "\n";
	array_splice($array, $pos, $len, $value);
	echo "after: " . json_encode($array) . "\n\n";
	return $array;
}

