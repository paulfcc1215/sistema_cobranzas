<?php

    //get tipos_ubicacion
    $q = 'SELECT * FROM medios_contacto.tipo_ubicacion WHERE status=\'1\' ORDER BY id_tipo_ubicacion';
    $q0 = $db->query($q);
    $tipos_ubicacion = array();
    while($qa0 = $db->fetchOne($q0)){
        $tipos_ubicacion[$qa0['id_tipo_ubicacion']] = $qa0['descripcion'];
    }
    //get tipos direccion
    $tipo_direccion=array();
    foreach($db->query('SELECT * FROM pg_enum WHERE enumtypid=(SELECT oid FROM pg_type WHERE typname = \'enum_tipo_direccion\')') as $d){
        $tipo_direccion[$d['enumlabel']]=$d['enumlabel'];
    }
    $_T['maincontent'] .= '
    <script src="/cobranzas/modules/parametros/direcciones/googleMap.js"></script>
    <form style="width:100%;" method="post" "?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/direcciones/index','op'=>'registro')).'">
        <input type="hidden" name="save" value="1"/>
        <div id="agregarDireccionModalError" style="color: maroon; font-weight: bold;"></div><br>
        
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="identificacion">Identificación:</label>
                <input type="text" id="identificacion" name="identificacion" class="form-control" placeholder="Ingrese cédula" value="'.$_POST['identificacion'].'" required/>
                <label for="tipo_direccion">Tipo de dirección:</label>
                <select id="tipo_direccion" name="tipo_direccion" class="form-control" required>
                <option value="">Seleccione...</option>';
                foreach ($tipo_direccion as $id => $td){
                    $selected = '';
                    if ($_POST['tipo_direccion']==$id){
                        $selected = 'selected';
                    }
                    $_T['maincontent'] .= '<option value="'.$id.'" '.$selected.'>'.$td.'</option>';
                }
                $_T['maincontent'] .= '
                </select>';
                foreach($tipos_ubicacion as $id_tu => $tu){
                    $name = str_replace(' ','_',$tu);
                    $_T['maincontent'] .= '<label>'.$tu.'<input type="text" class="form-control" name="'.$name.'" id="'.$name.'" value="'.$_POST[$name].'" /></label>';
                }
                $_T['maincontent'] .= '
            </div>
            <div class="form-group col-md-8">
                <input id="btn_geolocalizar" class="btn btn-success" type="button" value="GeoLocalizar" /><br><br>
                <div id="map" style="width:100%; height:600px;"></div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCcAIM1m0ReHC8DS7dslCfAdxTgp1Na_QA&callback=initMap&libraries=&v=weekly" async></script>
    ';

    $_T['bottom_jscript'] .= '';