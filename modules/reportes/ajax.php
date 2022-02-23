<?php
try{
    $result=array(
        'status'=>'success',
        'data'=>null,
        'message'=>'',
    );
    $db = DB::getInstance();
    switch($_POST['action']){
        case 'getStatsMovistar':
            if (empty($_POST['id_proceso'])) throw new exception('id_proceso undefined');
            // get cuentas por proceso
            $q = 'SELECT 
                    t1.id_proceso,
                    t2.descripcion,
                    t1.cuentas,
                    t2.gestiones,
                    t1.cuentas-t2.gestiones as sin_gestion 
                FROM (SELECT id_proceso,count(*) cuentas FROM cuentas.cuenta WHERE id_proceso IN ('.implode(',',$_POST['id_proceso']    ).') GROUP BY id_proceso) AS t1
                JOIN (
                    SELECT 
                        c.id_proceso,p.descripcion,count(g.*) as gestiones
                    FROM cuentas.cuenta c
                        JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
                        LEFT JOIN gestiones.gestion g ON(c.id_cuenta=g.id_cuenta)
                        JOIN gestiones.tipificacion t ON(t.id_tipificacion=g.id_tipificacion)
                    WHERE 
                        c.id_proceso IN ('.implode(',',$_POST['id_proceso']).') AND 
                        g.user_name<>\'gramos\' AND
                        DATE(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_POST['desde']).'\' AND \''.Helpers::dmy2ymd($_POST['hasta']).'\'
                    GROUP BY c.id_proceso,p.descripcion
                ) as t2
                ON t1.id_proceso=t2.id_proceso';
            $q0 = $db->query($q);
            $result['data'] = $db->fetchAll($q0);
        break;
        case 'getStatsDetail':
            if (empty($_POST['id_proceso'])) throw new exception('id_proceso undefined');
            if ($_POST['tipo_reporte']=='') throw new exception('tipo_reporte undefined');
            // get gestriones por usuario y proceso
            if ($_POST['tipo_reporte']=='A'){
                $q = 'SELECT c.id_proceso,g.user_name as parametro,count(g.*) as gestiones
                FROM gestiones.gestion g
                JOIN gestiones.tipificacion t ON(t.id_tipificacion=g.id_tipificacion)
                JOIN cuentas.cuenta c on (c.id_cuenta=g.id_cuenta)
                WHERE 
                    c.id_proceso in ('.implode(',',$_POST['id_proceso']).') AND 
                    g.user_name<>\'gramos\' AND
                    DATE(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_POST['desde']).'\' AND \''.Helpers::dmy2ymd($_POST['hasta']).'\'
                GROUP BY c.id_proceso,g.user_name';
            }else{
                $q = 'SELECT c.id_proceso,t.descripcion as parametro,count(g.*) as gestiones
                FROM gestiones.gestion g
                JOIN gestiones.tipificacion t ON(t.id_tipificacion=g.id_tipificacion)
                JOIN cuentas.cuenta c on (c.id_cuenta=g.id_cuenta)
                WHERE 
                    c.id_proceso in ('.implode(',',$_POST['id_proceso']).') AND 
                    g.user_name<>\'gramos\' AND
                    DATE(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_POST['desde']).'\' AND \''.Helpers::dmy2ymd($_POST['hasta']).'\'
                GROUP BY c.id_proceso,t.descripcion';
            }
            $q0 = $db->query($q);
            $res = array();
            while($qa0 = $db->fetchOne($q0)){
                $res[$qa0['id_proceso']][$qa0['parametro']]=$qa0['gestiones'];
            }
            $result['data'] = $res;
        break;
        default:
            throw new exception ('action undefined');
        break;
    }
}catch(Exception $ex){
    $result['status']='error';
    $result['message']=$ex->getMessage();
}
echo json_encode($result);
die();