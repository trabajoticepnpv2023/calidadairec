<?php
require_once("../../config.php");

global $DB, $PAGE, $OUTPUT, $CFG;

$styles_url = new moodle_url('/blocks/monitorc/css/styles.css');
$PAGE->requires->css($styles_url);

require_once('monitorc_lib.php');

// Verificar si el usuario ha iniciado sesión
require_login();
$context = context_system::instance();

$url_pagina = new moodle_url('/blocks/monitorc/listar.php');
$url_personal = new moodle_url('/my');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/blocks/monitorc/listar.php'));
$PAGE->set_url($url_pagina);
$PAGE->set_title('Lista de Usuarios');

echo $OUTPUT->header();

echo $OUTPUT->box_start();

// Obtener los roles del usuario en el contexto del sistema
$system_context = context_system::instance();
$system_roles = get_user_roles($system_context, $USER->id);

// Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
$user_can_view = false;
foreach ($system_roles as $role) {
    if ($role->shortname == 'editingteacher' || $role->shortname == 'manager' || $role->shortname == 'adminaire') {
        $user_can_view = true;
        break;
    }
}
 echo '<br><a href="' . new moodle_url('/my') . '">Ir al área personal</a>';
if ($user_can_view) {
    // Obtener usuarios con fieldid=10
    $users = $DB->get_records_select('user_info_data', 'fieldid = ? AND data = ?', array(10, '1'), '', 'userid');


    if ($users) {
        echo '<table class="user-list">';
        echo '<tr><th>Sensor</th><th>Editar</th><th>Eliminar</th><th>Descargar Configuración</th></tr>';
        
        foreach ($users as $user) {
            $user_id = $user->userid;
            $user_info = $DB->get_field('user_info_data', 'data', array('userid' => $user->userid, 'fieldid' => 9));
            
            echo '<tr>';
            echo '<td>' . $user_info . '</td>';
            echo '<td><a href="' . new moodle_url('/blocks/monitorc/editar.php', array('userid' => $user_id)) . '">Editar</a></td>';
            echo '<td><a href="' . new moodle_url('/blocks/monitorc/eliminar.php', array('userid' => $user_id)) . '">Eliminar</a></td>';
            echo '<td><a href="' . new moodle_url('/blocks/monitorc/archivo_conf.php', array('userid' => $user_id)) . '">Descargar configuración</a></td>';

            echo '</tr>';
        }
        
        echo '</table>';
    } else {
        echo "No se encontraron sensores";
        echo '<br><a href="' . new moodle_url('/my') . '">Ir al área personal</a>';
    }
} else {
    echo "<span class='current'>Acceso denegado.</span> ";
   
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
