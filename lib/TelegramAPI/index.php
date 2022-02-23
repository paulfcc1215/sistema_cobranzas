<?php
require 'TelegramAPI.class.php';
function print_arr($arr) {
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}
$tg=new TelegramAPI('C3BPReportes',new aTGKnownEntities());

print_arr($tg->sendDocument(
'C3BPReportes_group',
'/img.jpg'
));

/*
$kb=new TGReplyKeyboardMarkup();
$kb->addButton('1');
$kb->addButton('2');
$kb->addButton('3');
$kb->addLineBreak();
$kb->addButton('4');
$kb->addButton('5');
$kb->addButton('6');
$kb->addLineBreak();
$kb->addButton('7');
$kb->addButton('8');
$kb->addButton('9');
$kb->addLineBreak();
$kb->addButton('0');
$kb->addButton('Cancelar');
*/


//print_r($tg->sendMessage('C3BPReportes_group','pruebax',null,null,null,null,$kb));
//sleep(5);
//print_r($tg->keyboardRemove('C3BPReportes_group','_'));
