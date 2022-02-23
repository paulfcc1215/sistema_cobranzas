<?php

    if(!Auth::hasPrivileges('AUTH_REPORTES_INDEX')) throw new Exception('No Autorizado - AUTH_REPORTES_INDEX');
    set_time_limit(5);
    function _r_tree_add_reporte(&$tree,$path,&$reporte) {
        if(count($path)>0) {
            _r_tree_add_reporte($tree[array_shift($path)],$path,$reporte);        
        }else{
            $tree['__#r#__'][]=$reporte;
        }    
    }

    function tree_add_reporte($reporte,&$tree) {
        $path=explode('/',$reporte['display_path']);
        if($reporte['display_path']=='') {
            $tree['-SIN RUTA-']['__#r#__'][]=$reporte;
        }else{
            _r_tree_add_reporte($tree,$path,$reporte);
        }
    }

    function build_jtree(&$tree,&$json,$parent_id) {
        foreach($tree as $node_name=>&$node) {
            if($node_name!='__#r#__') {
                $parent_id_nl='p'.uniqid();
                if($parent_id=='') $parent_id='#';
                $json[]='{ "id" : "'.$parent_id_nl.'", "parent" : "'.$parent_id.'", "text" : "'.$node_name.'" }';
                build_jtree($node,$json,$parent_id_nl);
            }else{
                foreach($node as $r) {
                    $descripcion='<table border=\'1\' class="descripcion_reporte">'
                    .'<tr><th>Id Reporte:</th><td>'.$r['id_reporte'].'</td></tr>'
                    .'<tr><th>Versión:</th><td>'.$r['version'].'</td></tr>';
                    
                    $descripcion.='<tr><th>Descripción:</th><td>'.str_replace("\r",'',str_replace("\n",'',$r['descripcion'])).'</td></tr>';
                    if(trim($r['observaciones'])!='') {
                        $descripcion.='<tr><th>Observaciones:</th><td style="color: red;">'.str_replace("\r",'',str_replace("\n",'',$r['observaciones'])).'</td></tr>';
                    }
                    
                    $descripcion.='</table>';
                    $descripcion=str_replace('"','\'',$descripcion);
                    
                    
                    $json[]='{ "id" : "n'.uniqid().'", "parent" : "'.$parent_id.'", "text" : "'.$r['nombre_reporte'].'", "icon":"template/assets/report_icon.png", "isLeaf":true, "reportId":"'.$r['id_reporte'].'", "descripcion":"'.$descripcion.'"}';
                    
                }
            }
        }
    }

    $_T['css'].='
    .descripcion_reporte td {
        padding: 5px;
    }
    .descripcion_reporte th {
        padding: 5px;
        background-color: #C3C3C3;
        text-align: right;
    }

    ';

    $_T['bottom_js_files'][]='template/assets/jstree/jstree.min.js';
    $_T['css_files'][]='template/assets/jstree/themes/default/style.min.css';
    $db=DB::getInstance();
    $query='SELECT
    r.*
    FROM "public".reportes r
    WHERE r.status=\'1\'
    ';
    $q0=$db->query($query);
    $tree=array();
    while($qa0=$db->fetchOne($q0)) {
        tree_add_reporte($qa0,$tree);    
    }

    $json=array();
    $json[]='{ "id" : "proot", "parent" : "#", "text" : "Reportes", "state":{"opened":true} }';
    build_jtree($tree,$json,'proot');
    $json=implode(',',$json);
    $_T['bottom_jscript']='
    $("#jtree")
    .on(\'changed.jstree\', function (e, data) {
        if(data.node.original.isLeaf) {
            window.location="?mod=reportes/dispatcher&id_reporte="+data.node.original.reportId
        }
    })
    .on(\'hover_node.jstree\',function (e, data) {
        if(data.node.original.isLeaf) {
            if(typeof data.node.original.descripcion != \'undefined\') {
                $("#rep_desc").html("<b>"+data.node.original.text+"</b><br><br>"+data.node.original.descripcion);
            }else{
                $("#rep_desc").html("<b>"+data.node.original.text+"</b><br><br>Sin Descripción");
            }
        }
    
    })
    .jstree(
        {
            \'core\' : {
                \'data\' : ['.$json.']
            }
        }
    );

    ';

    try {

        switch($_GET['step']) {
            default:
                $_T['maintitle']='REPORTES';
                $_T['maincontent']='<b>Seleccione un reporte</b><br><br>
                <table width="100%">
                <tr>
                <td valign="top" width="50%">
                    <div id="jtree"></div>
                </td>
                <td valign="top" style="padding-left: 10px; padding-right: 10px; border-left: solid 1px #ccc;">
                    <div id="rep_desc"></div>
                </td>
                </tr>
                </table>
                ';

            break;
        }
    }catch(Exception $e) {
        $_T['maintitle']='REPORTES';
        $_T['maincontent'].='
        <h2 style="color: maroon;">'.$e->getMessage().'</h2>
        <hr>
        <a href="javascript:history.go(-1)">Regresar</a>
        ';
        
        
        
    }
