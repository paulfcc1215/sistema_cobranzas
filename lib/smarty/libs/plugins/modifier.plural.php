<?php
function smarty_modifier_plural($array,$capital=false) {
	if($capital) {
		$s='S';
	}else{
		$s='s';
	}
	if(count($array)==1) return '';
	return $s;
}