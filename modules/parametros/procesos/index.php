<?php
    
    if(!Auth::hasPrivileges('AUTH_PARAMETROS_PROCESOS_INDEX')) throw new Exception('No autorizado - AUTH_PARAMETROS_PROCESOS_INDEX');

    $_T['maintitle']='Parámetros del sistema - Procesos';
    
    $_AM['proceso'] = AutoModel::getInstance('campanas','proceso',Db::getInstance());
    $_AM['campana'] = AutoModel::getInstance('campanas','campana',Db::getInstance());
    $_AM['udn'] = AutoModel::getInstance('estructura','udn',Db::getInstance());

    foreach($_AM['campana']->getAll() as $c){
        $campanas[$c->getData()['id_campana']]=$c->getData();
        $campanas[$c->getData()['id_campana']]['udn']=$_AM['udn']->getById($c->getData()['id_udn'])->getData()['udn'];
    }
    foreach($_AM['proceso']->getAll('id_campana,fecha_apertura') as $p){
        if ($p->getData()['status']=='1'){
            $procesos[$p->getData()['id_proceso']]=$p->getData();
            $procesos[$p->getData()['id_proceso']]['campana'] = $campanas[$p->getData()['id_campana']]['campana'];
            $procesos[$p->getData()['id_proceso']]['udn'] = $campanas[$p->getData()['id_campana']]['udn'];
        }
    }
    $_T['maincontent'].='
    <style>
        .table_pagos th{
            font-family: "Lucida Console";
            font-size: 95%;
            padding: 2px;
            border: 1px solid #98B3FC;
            background: #EBF0FF;
        }
        .table_pagos td{
            font-family: "Lucida Console";
            font-size: 90%;
            padding: 1px;
            border: 1px solid #B4C8FF;
        }
    </style>
    ';
    switch($_GET['step']) {
        case 'get_cargas':
            $obj_cargas = AutoModel::getInstance('cargas','carga',Db::getInstance());
            $cargas = array();
            foreach($obj_cargas->getByAndCond(array('id_proceso'=>$_GET['id_p']),'fecha_carga DESC') as $row){
                $cargas[] = $row->getData();
            }
            echo json_encode($cargas);
            die();
        break;
        default:
            $_T['maincontent'].='
            <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/procesos/nuevo_proceso')).'\'">Crear Proceso</button>
            <br><br>
            <div class="form-group row">
                <div class="col-sm-7">

                    <div class="card">
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Campaña</th><th>ID - Proceso</th><th>Fecha Creacion</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                    foreach ($procesos as $row){
                                        $_T['maincontent'].='<tr class="clickable" onclick="mostrar_cargas('.$row['id_proceso'].')">
                                            <td id="label_campana_'.$row['id_proceso'].'">'.$row['udn'].' - '.$row['campana'].'</td>
                                            <td id="label_proceso_'.$row['id_proceso'].'">'.$row['id_proceso'].' - '.$row['descripcion'].'</td>
                                            <td>'.date('d/m/Y H:i:s',strtotime($row['fecha_apertura'])).'</td>
                                            <td>'.($row['status']=='1'?'ACTIVA':'INACTIVA').'</td>
                                        </tr>';
                                    }
                                $_T['maincontent'].= '
                                </tbody>
                                <!--<tfoot>
                                    <tr>
                                    <th>Campaña</th><th>ID - Proceso</th><th>Fecha Creacion</th><th>Status</th>
                                    </tr>
                                </tfoot>-->
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-5">
                    <div id="cargas_x_proceso">Seleccione proceso...</div>
                </div>
            </div>';

        break;
    }

    $_T['bottom_jscript'] .='
        function mostrar_cargas(id_proceso){
            $("#cargas_x_proceso").empty();
            $.ajax({
                "url":"?mod=parametros/procesos/index&id_p="+id_proceso+"&step=get_cargas",
                "success":function(d) {
                    try {
                        d = $.parseJSON(d);
                        if (d.length!=0){
                            var html = "<b>"+$("#label_campana_"+id_proceso).html()+" ("+$("#label_proceso_"+id_proceso).html()+")</b>";
                            html += "<table width=\"100%\" class=\"table_pagos\"><tr><th>CARGA</th><th>FECHA DE CARGA</th><th>USUARIO</th><th>TIPO CARGA</th></tr>";
                            $.each(d,function(idx,carga){
                                html += "<tr><td>"+carga.id_carga+": "+carga.descripcion+"</td>";
                                html += "<td>"+carga.fecha_carga+"</td>";
                                html += "<td>"+carga.usuario+"</td>";
                                html += "<td>"+carga.tipo_carga+"</td>";
                                html += "</tr>";
                            })
                            $("#cargas_x_proceso").append(html);
                        }else{
                            $("#cargas_x_proceso").append("No existen cargas...");
                        }
                    }catch(err){
                        //$("#drawCanvas").html("Rand: "+Math.random().toString()+" - "+err+"<hr><pre>"+d+"</pre>");
                        console.log(err);
                    }
                    ajaxBusy=false;
                }
            });
        }

        $("#example1").DataTable({
            "responsive": true, 
            "lengthChange": false, 
            "autoWidth": false,
            "buttons": [
                "copy", 
                "csv", 
                "excel", 
                "pdf", 
                "print", 
                "colvis"
            ]
        }).buttons().container().appendTo(\'#example1_wrapper .col-md-6:eq(0)\');
    
        /*$("#example1 tbody").on("click", "tr", function(){
            var table = $("#example1").DataTable();
            var data = table.row( this ).data();
            alert( "You clicked on "+data[0]+" row" );
        });*/

    ';