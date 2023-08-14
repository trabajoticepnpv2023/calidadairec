<?php
require_once("../../config.php");

global $DB, $PAGE, $OUTPUT, $CFG;;

$styles_url = new moodle_url('/blocks/monitorc/css/styles.css');
$PAGE->requires->css($styles_url);

require_once('monitorc_lib.php');

// Verificar si el usuario ha iniciado sesión
require_login();
$context = context_system::instance();


// Parámetros del formulario y paginación
$accion = optional_param('accion', 'all', PARAM_ALPHANUM);
$id = optional_param('id', 0, PARAM_INT);
$descargar = optional_param('descargar', false, PARAM_BOOL);

$url_pagina = new moodle_url('/blocks/monitorc/monitorc.php');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/blocks/monitorc/monitorc.php'));
$PAGE->set_url($url_pagina);
$PAGE->set_title('mediciones');

// Configuración de la paginación
$registros_por_pagina = 25;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $registros_por_pagina;



$consulta_total = "SELECT COUNT(*) AS total FROM vista_promedios_hora_userid $condicion_fecha";
    $resultado_total = $DB->get_record_sql($consulta_total);
    $total_registros = $resultado_total->total;



// Obtener valores del formulario (rango de fechas)
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : (isset($_SESSION['fecha_desde']) ? $_SESSION['fecha_desde'] : '');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : (isset($_SESSION['fecha_hasta']) ? $_SESSION['fecha_hasta'] : '');
$hora_desde = isset($_GET['hora_desde']) ? $_GET['hora_desde'] : (isset($_SESSION['hora_desde']) ? $_SESSION['hora_desde'] : '');
$hora_hasta = isset($_GET['hora_hasta']) ? $_GET['hora_hasta'] : (isset($_SESSION['hora_hasta']) ? $_SESSION['hora_hasta'] : '');

$filtro_usuario = optional_param('filtro_usuario', '', PARAM_INT);


// Filtrar la consulta según el rango de fechas seleccionado
$condicion_fecha = '';
if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    // Convertir las fechas seleccionadas a formato de fecha y hora compatible con la base de datos
    $fecha_desde = date('Y-m-d', strtotime($fecha_desde));
    $fecha_hasta = date('Y-m-d', strtotime($fecha_hasta));

    // Establecer las variables de sesión para mantener los valores después del filtrado
    $_SESSION['fecha_desde'] = $fecha_desde;
    $_SESSION['fecha_hasta'] = $fecha_hasta;
    if( empty($hora_hasta)){
        $hora_hasta = '23:59'; // Hora final por defecto;
    }
    if( empty($hora_desde)){
        $hora_desde='00:00';
    }
    
     $condicion_fecha = " WHERE HoraInicio >= '" . $fecha_desde . " " . $hora_desde . ":00' AND HoraInicio <= '" . $fecha_hasta . " " . $hora_hasta . ":59'";
    
    // Agregar la condición del filtro de usuario si está seleccionado
    if (!empty($filtro_usuario)) {
        $condicion_fecha .= " AND userid = " . $filtro_usuario;
    }
} else {
    // Obtener la fecha de la primera medición existente en la base de datos
    $primera_medicion = $DB->get_field_sql("SELECT MIN(HoraInicio) FROM vista_promedios_hora_userid");
    $fecha_desde = date('Y-m-d', strtotime($primera_medicion));
    $fecha_hasta = date('Y-m-d');

    // Establecer las variables de sesión con las fechas por defecto
    $_SESSION['fecha_desde'] = $fecha_desde;
    $_SESSION['fecha_hasta'] = $fecha_hasta;

    $condicion_fecha = "WHERE HoraInicio >= '" . $fecha_desde . "' AND HoraInicio <= '" . $fecha_hasta . " 23:59:59'";
    // Agregar la condición del filtro de usuario si está seleccionado
    if (!empty($filtro_usuario)) {
        $condicion_fecha .= " AND userid = " . $filtro_usuario;
    }
}

// Obtener los UserIDs únicos para el filtro de usuario
$query_userids = "SELECT DISTINCT userid FROM vista_promedios_hora_userid $condicion_fecha";
$userids = $DB->get_records_sql($query_userids);

$vista = new stdClass();
$vista->cabecera = array();
$vista->tabla = new html_table();

if (!empty($condicion_fecha)) {
    // Consulta para obtener todos los registros filtrados
    $consulta = "SELECT * FROM vista_promedios_hora_userid $condicion_fecha ORDER BY HoraInicio ASC";
    $mediciones = $DB->get_records_sql($consulta);

    // Calcular el número total de registros para la paginación
    $consulta_total = "SELECT COUNT(*) AS total FROM vista_promedios_hora_userid $condicion_fecha";
    $resultado_total = $DB->get_record_sql($consulta_total);
    $total_registros = $resultado_total->total;
    $total_paginas = ceil($total_registros / $registros_por_pagina);

    // Consulta para obtener los registros de la página actual
    $consulta_pagina = "SELECT * FROM vista_promedios_hora_userid $condicion_fecha LIMIT $registros_por_pagina OFFSET $inicio";
    //$consulta_pagina = "SELECT * FROM vista_promedios_hora_userid ";
 
    $mediciones_pagina = $DB->get_records_sql($consulta_pagina);
    $filas_pagina = array();

    foreach ($mediciones_pagina as $medicion_pagina) {
        $usuario_id = $DB->get_field('user_info_data', 'data', array('userid' => $medicion_pagina->userid, 'fieldid' => 9));
        $fechahora = $medicion_pagina->horainicio;
        $temp = $medicion_pagina->promedio_temp;
        $pm25 = $medicion_pagina->promedio_pm25;
        $hum = $medicion_pagina->promedio_hum;
        $co_calculo = $medicion_pagina->promedio_co * (5.0 / 4095.0);
        
        if( $co_calculo == 0){
            $co = 0;
            }else{
            
            $Rs = ((5 - $co_calculo) / $co_calculo);
            $Ro = 71721.8207;
    
            $ratio= $Rs/$Ro;
            $x =2431.91*$ratio;
            $ppm = pow($x,-0.71428);
            $co = number_format($ppm, 3); 
        }

        $filas_pagina[] = (object) array(
            'usuario_id' => $usuario_id,
            'fechahora' => $fechahora,
            'co' => $co,
            'pm25' => $pm25,
            'temp' => $temp,
            'hum' => $hum
           
        );
    }
    

} else {
    
    $filas_pagina = array();
}

$gestor_monitorc = new block_monitorc_manager();

if ($descargar && !empty($condicion_fecha)) {
    
    $gestor_monitorc->download_monitorc($filas_pagina);
    exit;
}

echo $OUTPUT->header();

echo $OUTPUT->box_start();

// Obtener los roles del usuario en el contexto del sistema
        $system_context = context_system::instance();
        $system_roles = get_user_roles($system_context, $USER->id);

        // Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
        $user_can_view = false;
        foreach ($system_roles as $role) {
            if ($role->shortname == 'editingteacher' || $role->shortname == 'manager'|| $role->shortname == 'adminaire') {
                $user_can_view = true;
                break;
            }
        }

if ($user_can_view) {
// Sección de selección de fechas
echo '<div class="date-selection">';
echo '<form method="get" action="' . $url_pagina . '">';
echo '<label for="fecha_desde">Desde:</label>';
echo '<input type="date" id="fecha_desde" name="fecha_desde" value="' . $fecha_desde . '">';
echo '<input type="time" id="hora_desde" name="hora_desde" value="' . $hora_desde . '">';

echo '<label for="fecha_hasta">Hasta:</label>';
echo '<input type="date" id="fecha_hasta" name="fecha_hasta" value="' . $fecha_hasta . '">';
echo '<input type="time" id="hora_hasta" name="hora_hasta" value="' . $hora_hasta . '">';

echo '<label for="filtro_usuario">Usuario:</label>';
echo '<select id="filtro_usuario" name="filtro_usuario">';
echo '<option value="">Todos los usuarios</option>';

foreach ($userids as $useridt) {
    $username = $DB->get_field('user_info_data', 'data', array('userid' => $useridt->userid, 'fieldid' => 9));
    echo '<option value="' . $useridt->userid . '">' . $username . '</option>';
}

echo '</select>';

echo '<input type="submit" value="Filtrar">';

echo '</form>';
echo '</div>';


foreach ($vista->cabecera as $cabecera) {
        echo $OUTPUT->heading($cabecera, 4);
    }
    
    echo html_writer::start_tag('div', array('class' => 'download-medicion'));
    echo html_writer::start_tag('p');
    echo $OUTPUT->single_button(new moodle_url($url_pagina, array('descargar' => true)), get_string('downloadexcel'), 'get');
    echo html_writer::end_tag('p');
    echo html_writer::end_tag('div');
    
    $vista->tabla->head = array('Usuario', 'Fecha y hora', 'CO [ppm]', 'PM25 [µg/m3]', 'Temperatura [°C]', 'Humedad [%]');
    $vista->tabla->data = array();
    
    foreach ($filas_pagina as $fila) {
        $vista->tabla->data[] = array(
            $fila->usuario_id,
            $fila->fechahora,
            $fila->co,
            $fila->pm25,
            $fila->temp,
            $fila->hum
        );
    }

echo html_writer::table($vista->tabla);

    echo '<div class="pagination">';
    // Páginas a mostrar en la paginación
    $paginas_mostradas = 7;
    $mitad_paginas_mostradas = floor($paginas_mostradas / 2);
    
    // Página inicial y final
    $pagina_inicio = max(1, $pagina_actual - $mitad_paginas_mostradas);
    $pagina_final = min($total_paginas, $pagina_inicio + $paginas_mostradas - 1);
    
    // Asegurarse de mostrar el número correcto de páginas
    $pagina_inicio = max(1, $pagina_final - $paginas_mostradas + 1);
    
    // Enlace a la página anterior
    if ($pagina_actual > 1) {
        echo "<a href='$url_pagina?pagina=" . ($pagina_actual - 1) . "&fecha_desde=$fecha_desde&fecha_hasta=$fecha_hasta&hora_desde=$hora_desde&hora_hasta=$hora_hasta'>&laquo; Anterior</a> ";
    }
    
    // Enlaces a las páginas
    for ($i = $pagina_inicio; $i <= $pagina_final; $i++) {
        if ($i === $pagina_actual) {
            echo "<span class='current'>$i</span> ";
        } else {
            echo "<a href='$url_pagina?pagina=$i&fecha_desde=$fecha_desde&fecha_hasta=$fecha_hasta&hora_desde=$hora_desde&hora_hasta=$hora_hasta'>$i</a> ";
        }
    }
    
    // Enlace a la página siguiente
    if ($pagina_actual < $total_paginas) {
        echo "<a href='$url_pagina?pagina=" . ($pagina_actual + 1) . "&fecha_desde=$fecha_desde&fecha_hasta=$fecha_hasta&hora_desde=$hora_desde&hora_hasta=$hora_hasta'>Siguiente &raquo;</a> ";
    }
    
    echo '</div>';
} else {
            echo "<span class='current'>Acceso denegado.</span> ";

        }
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
