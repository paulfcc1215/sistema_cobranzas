<?php
class Benchmark {
    //  Libreria benchmarking
    private $_BM=array();
    /**
    * Funcion para hacer benchmarking
    * Recibe una etiqueta con la cual nombrar el punto
    *
    * Se debe llamar dos veces. La primera para marcar el inicio de una tarea ($tag)
    * Y luego se llama con el mismo $tag para marcar el fin.
    *
    * La funcion devuelve la diferencia de tiempo entre la primera llamada y la segunda
    *
    * Ejemplo:
    * bm_mark('tarea_a_medir');
    * <tarea muy larga>
    * $tiempo = bm_mark('tarea_a_medir');
    * echo 'Me tarde '.$tiempo.' segundos';
    *
    * @param mixed $tag Nombre de la tarea
    */
    function mark($tag) {
        if(isset($this->_BM[$tag][1])) {
            unset($this->_BM[$tag][0]);
            unset($this->_BM[$tag][1]);
        }

        if(isset($this->_BM[$tag][0])) {
            $this->_BM[$tag][1] = microtime(true);
            $aux = $this->_BM[$tag][1]-$this->_BM[$tag][0];
            $this->_BM[$tag]['avgs'][] = $aux;
            return $aux;
        }else{
            $this->_BM[$tag][0] = microtime(true);
        }
    }

    /**
    * Si se llama a bm_mark multiples veces con el mismo $tag
    * se puede utiliza bm_avg para obtener un promedio de tardanza
    * entre todas las llamadas.
    *
    * @param mixed $tag Nombre de la tarea
    */
    function avg($tag) {
        $acum = 0;
        $count = 0;
        foreach($this->_BM[$tag]['avgs'] as $t) {
            $acum+=$t;
            $count++;
        }
        return $acum/$count;
    }
    
    function get_data() {
        return $this->_BM;
    }
    
    function get_avgs($tag) {
        return $this->_BM[$tag]['avgs'];
    }
    
    function resume($type='html') {
       $acum=0;
       foreach($this->_BM as $tag=>$data) {
           $avg=$this->avg($tag);
           $rows[$tag]['samples']=count($data['avgs']);
           $rows[$tag]['avg']=$avg;
           $rows[$tag]['time_used']=($avg*$rows[$tag]['samples']);
           $acum+=$rows[$tag]['time_used'];
       }

       
       
       
       $html='<table border="1">
       <tr style="background-color: #878787;"><th>TAG</th><th>SAMPLES</th><th>AVERAGE</th><th>TOTAL</th><th>PROFILE</th></tr>
       ';
       foreach($rows as $tag=>$data) {
          $html.='<tr>';
          $html.='<th style="background-color: #878787;">'.$tag.'</th>';
          $html.='<td style="text-align: center;">'.$data['samples'].'</td>';
          $html.='<td>'.$data['avg'].'</td>';
          $html.='<td>'.$data['time_used'].'</td>';
          $percent=round((($data['time_used']*100)/$acum),2);
          $html.='<td style="text-align: center !important;">'.$percent.' %</td>';
          $html.='</tr>';
          $acums['samples']+=$data['samples'];
          $acums['avg']+=$data['avg'];
          $acums['time_used']+=$data['time_used'];
          $acums['percent']+=$percent;
           
       }
	   $html.='<tr>';
	   $html.='<th style="background-color: #878787;">TOTALS</th>';
	   $html.='<td style="text-align: center;">'.$acums['samples'].'</td>';
	   $html.='<td>'.$acums['avg'].'</td>';
	   $html.='<td>'.$acums['time_used'].'</td>';
	   $html.='<td style="text-align: center !important;">'.$acums['percent'].' %</td>';
	   $html.='</tr>';
       $html.='</table>';
       return $html;
    }
    
    
    
}