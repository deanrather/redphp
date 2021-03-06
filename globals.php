<?php

/**
 * returns true if isset() is true, and the value is true
 */
function isset_true(&$data)
{
	return (isset($data) && $data);
}

/**
 * returns the value if isset(), otherwise returns false.
 */
function isset_val(&$data)
{
	return ((isset($data) && $data) ? $data : false);
}

/**
 * If the value is set, returns the value. Else, returns default.
 * @param unknown_type $data
 * @param unknown_type $default
 */
function isset_default(&$data, $default)
{
	return (isset($data) ? $data : $default); 
}

/**
 * print_r's an array, wrapped in <pre> tags.
 */
function print_r_pre($array)
{
	print '<pre>'.print_r($array,true).'</pre>';
}

/**
 * print_r's an array, wrapped in <pre> tags, then dies.
 */
function print_r_pre_die($array)
{
	print_r_pre($array);
	die();
}