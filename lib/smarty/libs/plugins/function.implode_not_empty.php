<?php
function smarty_function_implode_not_empty($params,$template) {
	if(!array_key_exists('sep',$params)) $params['sep']=' ';
	$aux=array();
	foreach($params['what'] as $v) {
		$v=trim($v);
		if($v!='') $aux[]=$v;
	}
	return implode($params['sep'],$aux);
}