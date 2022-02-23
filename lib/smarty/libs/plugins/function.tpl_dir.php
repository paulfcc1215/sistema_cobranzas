<?php
function smarty_function_tpl_dir($params, $template) {
	$aux=($template->source->resource);
	$aux=explode(':',$aux);
	return dirname($aux[1]);
}