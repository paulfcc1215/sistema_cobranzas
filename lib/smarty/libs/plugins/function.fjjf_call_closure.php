<?php
function smarty_function_fjjf_call_closure($params,&$template) {

    if(!is_callable($params['fn']))
        return '';
    
    if(!array_key_exists('params',$params))
        $params['params']=array();
    $fn=$params['fn'];
    unset($params['fn']);
    $ret = call_user_func_array($fn,array($params,$template));
    return $ret;
}