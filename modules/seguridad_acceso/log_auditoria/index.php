<?php
    
    if(!Auth::hasPrivileges('AUTH_SEGURIDADES_LOG_AUDITORIA')) throw new Exception('No autorizado - AUTH_SEGURIDADES_LOG_AUDITORIA');
    
    //$_T['navigation'] = get_navigation(__FILE__);
    $_T['maintitle']='Seguridad y acceso - log de acciones';
    $_T['maincontent'].='
    <table>
    <tr>
        <td style="padding:20px;" valign="top">
            <b>FEBRERO 2021</b>
            <ul>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-08</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-09</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-10</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-11</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-12</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-13</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-14</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-15</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-16</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-17</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-18</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-19</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-20</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-21</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-22</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-23</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-24</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-25</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-26</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-27</a></li>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-02-28</a></li>
            </ul>
            <b>MARZO 2021</b>
            <ul>
                <li><a href="?mod=seguridad_acceso/log_auditoria/index">2021-03-01</a></li>
            </ul>
        </td>
        <td style="padding:20px; border-left:1px solid blue;" valign="top">
            <button class="btn btn-primary mb-3">Descargar Log</button>
            <br><br>
            <b>Fecha: 2021-03-01</b><br><br>
            <table class="simple_table2">
                <tr>
                    <th>usuario</th>
                    <th>fecha_hora</th>
                    <th>accion</th>
                </tr>
                <tr>
                    <td>gramos</td>
                    <td>2021-03-01 07:50</td>
                    <td>login</td>
                </tr>
                <tr>
                    <td>pcedeno</td>
                    <td>2021-03-01 07:52</td>
                    <td>login</td>
                </tr>
                <tr>
                    <td>mpala</td>
                    <td>2021-03-01 07:55</td>
                    <td>login</td>
                </tr>
                <tr>
                    <td>gramos</td>
                    <td>2021-03-01 07:51</td>
                    <td>acceso modulo carga de datos</td>
                </tr>
                <tr>
                    <td>gramos</td>
                    <td>2021-03-01 07:53</td>
                    <td>acceso modulo verificación de datos</td>
                </tr>
                <tr>
                    <td>mpala</td>
                    <td>2021-03-01 07:59</td>
                    <td>acceso modulo reporteria</td>
                </tr>
                <tr>
                    <td>mpala</td>
                    <td>2021-03-01 08:01</td>
                    <td>descarga de reporteria parametros [udn:movistar-fecha_inicio:2021-02-28-fecha_fin:2021-02-28]</td>
                </tr>
                <tr>
                    <td>pcedeno</td>
                    <td>2021-03-01 07:53</td>
                    <td>Accesos a módulo de seguridad y acceso</td>
                </tr>
                <tr>
                    <td>pcedeno</td>
                    <td>2021-03-01 07:53</td>
                    <td>Asignación de permisos usuario: C-BMARCALLA - Acción: Asignación de acceso a gestión - UDN: BANECUADOR</td>
                </tr>
            </table>
        </td>
    </tr>';