<?php
class GestionesUploadable extends CargaModelo_Gestiones_Abstract {
	private $db;
	private $res;
	private $num_recs;
	private $ptr;
	private $tipif_map=array(
		// SISE - FALLECIDO
		'1'=>'8',
		// SISE - ABONO DEUDA
		'2'=>'25', // COBE - ABONO_DEUDA
		// SISE - NEGATIVA DE PAGO
		'3'=>'7', // COBE - NEGATIVA DE PAGO
		// SISE - MSJ TERCEROS
		'4'=>'10', // COBE - MSJ TERCEROS
		// SISE - BUZON DE VOZ
		'5'=>'29', // COBE - BUZON DE VOZ
		// SISE - VOLVER A LLAMAR
		'6'=>'27', // COBE - VOLVER A LLAMAR
		// SISE - PROMESA DE PAGO
		'7'=>'6', // COBE - PROMESA DE PAGO
		// SISE - SMS
		'8'=>'28', // COBE - SMS
		// SISE - CANCELADO
		'9'=>'3', // COBE - CANCELADO
		// SISE - VISITA
		'10'=>'11', // COBE - VISITA
		// SISE - IVR
		'11'=>'12', // COBE - IVR
		// SISE - WHATSAPP
		'12'=>'9', // COBE - WHATSAPP
		// SISE - NO CONTESTA
		'13'=>'19', // COBE - NO CONTESTA
		// SISE - EMAIL
		'14'=>'17', // COBE - EMAIL
		// SISE - NUMERO EQUIVOCADO
		'15'=>'18', // COBE - NUMERO EQUIVOCADO
		// SISE - ABONO_DEUDA
		'75'=>'25', // COBE - ABONO_DEUDA
		// SISE - WHATSAPP
		'80'=>'9', // COBE - WHATSAPP
		// SISE - INVESTIGACION
		'92'=>'24', // COBE - INVESTIGACION
		// SISE - ABONO
		'101'=>'20', // COBE - ABONO
		// SISE - FALLECIDO
		'104'=>'8', // COBE - FALLECIDO
		// SISE - INCUMPLIMIENTO DE COMPROMISO
		'105'=>'26', // COBE - INCUMPLIMIENTO DE COMPROMISO
		// SISE - ENVIO IVR
		'106'=>'13', // COBE - ENVIO IVR
		// SISE - MSJ TERCEROS
		'107'=>'10', // COBE - MSJ TERCEROS
		// SISE - NEGATIVA DE PAGO
		'108'=>'7', // COBE - NEGATIVA DE PAGO
		// SISE - NEGOCIACION
		'109'=>'14', // COBE - NEGOCIACION
		// SISE - NUMERO EQUIVOCADO
		'112'=>'18', // COBE - NUMERO EQUIVOCADO
	);
	
	
	function __construct($db) {
		$this->db=$db;
		
	}
	
	function processRecord(&$line) {
		$ret=new CargaModelo_Item_Gestion();
		$ret->fecha_inicio=$line['gestion_fecha'];
		$ret->fecha_fin=$line['gestion_fecha'];
		$ret->user_name=$line['gestion_user_name'];
		if($ret->user_name=='') $ret->user_name='SIN USUARIO';
		$ret->tel_number=$line['gestion_telefono'];
		if(!array_key_exists($line['tipificacion_id'],$this->tipif_map))
			throw new Exception('La tipificacion con id '.$line['tipificacion_id'].' no existe en el mapa!. '.print_arr($line,true));
		if($line['gestion_observacion']!='') {
			$ret->observacion=$line['gestion_observacion'];
		}
		$ret->id_tipificacion=$this->tipif_map[$line['tipificacion_id']];
		$ret->cuenta=$line['cuenta_numero'];
		$ret->servidor='no_disponible';
		
		if($ret->id_tipificacion==6) {
			$ret->fecha_compromiso=$line['gestion_fecha_compromiso'];
			$ret->monto_compromiso=$line['gestion_valor_compromiso'];
		}
		
		
		
		return $ret;
	}
	
	
	// Iterator
	function next() {
		$this->res->next();
		$this->ptr++;
	}
	
	function valid() {
		return $this->ptr<$this->num_recs;
	}
	
	function current() {
		return $this->processRecord($this->res->current());
	}

	function rewind() {
		if(!is_null($this->res)) pg_free_result($this->res);
		$query='SELECT
			con.concli AS cliente_identificacion,
			con.ncfcli AS cliente_nombre,
			g.feages AS gestion_fecha,
			g.obsgst AS gestion_observacion,
			g.login AS gestion_user_name,
			tip.nobge AS gestion_tipificacion,
			g.valcco AS gestion_valor_compromiso,
			g.feccco AS gestion_fecha_compromiso,
			g.telefe AS gestion_telefono,
			crd.crd_hsc_credito AS cuenta_numero,
			tip.carbge AS tipificacion_id
			/*
			\'----------------------------------\' AS sep,
			g.*
			*/
		FROM
			db_gestion g
			LEFT JOIN db_detalle_campana dc ON (dc.cdtcam=g.cdtcam)
			LEFT JOIN db_credito crd ON (crd.crd_id=dc.crd_id)
			LEFT JOIN db_contacto con ON (con.idecli=dc.idecli)
			LEFT JOIN db_arbol_gestion tip ON (tip.carbge=g.carbge)
			WHERE
			g.crd_id IN (
				SELECT crd_id FROM db_credito
					WHERE crd_codper :: TEXT = (
						SELECT
						dbCampana.codper :: TEXT
						FROM
						db_campana dbCampana
						WHERE
						dbCampana.codcam = db_credito.codcam
					)			
			
			)
			ORDER BY g.cogges
			DESC
		';
		
		$this->res=$this->db->query($query);
		$this->num_recs=$this->res->numRows();
		$this->ptr=0;
	}
	
}