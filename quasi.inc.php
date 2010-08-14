<?php

function arraySplice($array, $pos, $len, $value) {
	array_splice($array, $pos, $len, $value);
	return $array;
}

function arrayAt($array, $key) {
	return $array[$key];
}