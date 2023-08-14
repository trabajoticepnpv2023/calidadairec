<?php
require_once("../../config.php");

global $DB, $PAGE, $OUTPUT, $CFG;

$styles_url = new moodle_url('/blocks/monitorc/css/styles.css');
$PAGE->requires->css($styles_url);

require_once('monitorc_lib.php');

// Verificar si el usuario ha iniciado sesión
require_login();
$context = context_system::instance();

$url_pagina = new moodle_url('/blocks/monitorc/archivo_conf.php');
$url_imagen = new moodle_url('/blocks/monitorc/DiagramaConeccion.png');
$url_personal = new moodle_url('/my');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/blocks/monitorc/archivo_conf.php'));
$PAGE->set_url($url_pagina);
$PAGE->set_title('Archivo Conf');

echo $OUTPUT->header();

echo $OUTPUT->box_start();

// Obtener los roles del usuario en el contexto del sistema
$system_context = context_system::instance();
$system_roles = get_user_roles($system_context, $USER->id);

// Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
$user_can_view = false;
foreach ($system_roles as $role) {
    if ($role->shortname == 'manager' || $role->shortname == 'adminaire') {
        $user_can_view = true;
        break;
    }
}

if ($user_can_view) {
    // Obtener el valor del parámetro 'userid' (que ya proviene de otra página)
    $user_id = required_param('userid', PARAM_INT);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $ssid = $_POST['ssid'];
        $pswd = $_POST['pswd'];
        
        descarga_conf($user_id, $ssid, $pswd);
    
        echo "Se ha descargado tu archivo de configuración. Por favor, considera la siguiente figura para conectar los componentes. En caso de que tu archivo no se haya descargado, es probable que tu navegador esté bloqueando las ventanas emergentes.";
        
        echo '<br><img src="' . $url_imagen . '" alt="Diagrama de Conexión">';
        
        echo '<br><a href="' . $url_personal . '">Ir al área personal</a>';
        
    } else {
        echo '<div class="sensor-form">';
        echo '<form method="post" action="' . $url_pagina . '?userid=' . $user_id . '">';

        echo '<label for="ssid">SSID:</label>';
        echo '<input type="text" id="ssid" name="ssid" required>';

        echo '<label for="pswd">Contraseña:</label>';
        echo '<input type="password" id="pswd" name="pswd" required>';

        echo '<div style="margin-top: 10px;"></div>';

        echo '<input type="submit" value="Obtener archivo de configuración">';

        echo '</form>';
        echo '</div>';
    }
} else {
    echo "<span class='current'>Acceso denegado.</span> ";
}
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
