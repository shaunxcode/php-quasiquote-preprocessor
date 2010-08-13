<?php

function arraySplice($array, $pos, $len, $value) {
	array_splice($array, $pos, $len, $value);
	return $array;
}

