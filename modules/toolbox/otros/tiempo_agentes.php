<?php
DB::connect('pgsql',array(
	'host'=>'10.1.210.103',
	'port'=>5432,
	'user'=>'postgres',
	'password'=>'postgres',
	'dbname'=>'dragontech',
),'dragon');


$dbDragon = DB::getInstance('dragon');

// determine active agents for today
$
$q0=$dbDragon->query('SELECT DISTINCT usr_logname FROM dd_user_log ulo JOIN dd_user USING (usr_id) WHERE DATE("ulo_date")=DATE(NOW())');
foreach($q0 as $qa0) {
	print_arr($qa0);
	die();
}
