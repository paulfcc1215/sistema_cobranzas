<?php
function smarty_function_get_by_index($params, $template)
{
	$target=&$params['from'];
	$aux=array_keys($target);
	$index=$params['index'];
	if(!array_key_exists($index,$aux))
		throw new Exception('Invalid Index');
	if($params['to']=='')
		throw new Exception('Invalid to');
	$ret=&$target[$aux[$index]];
	$template->assignByRef($params['to'],$ret);
}
