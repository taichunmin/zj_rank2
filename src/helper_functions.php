<?php

if(!function_exists('get')) {
	function get(&$var, $default=null) {
    return isset($var) ? $var : $default;
	}
}

if(!function_exists('encode_json')) {
	function encode_json( $var ) {
		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
			return json_encode($var, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		else if(version_compare(PHP_VERSION, '5.3.3') >= 0)
			return json_encode($var, JSON_NUMERIC_CHECK);
		return json_encode($var);
	}
}
