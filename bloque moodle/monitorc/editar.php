<?php
require_once("../../config.php");

global $DB, $PAGE, $OUTPUT, $CFG;

$styles_url = new moodle_url('/blocks/monitorc/css/styles.css');
$PAGE->requires->css($styles_url);

require_once('monitorc_lib.php');

// Verificar si el usuario ha iniciado sesión
require_login();
$context = context_system::instance();

$url_pagina = new moodle_url('/blocks/monitorc/editar.php');
$url_personal = new moodle_url('/my');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/blocks/monitorc/editar.php'));
$PAGE->set_url($url_pagina);
$PAGE->set_title('Editar usuario');

echo $OUTPUT->header();

echo $OUTPUT->box_start();

// Obtener los roles del usuario en el contexto del sistema
$system_context = context_system::instance();
$system_roles = get_user_roles($system_context, $USER->id);

// Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
$user_can_view = false;
foreach ($system_roles as $role) {
    if ($role->shortname == 'adminaire' || $role->shortname == 'manager') {
        $user_can_view = true;
        break;
    }
}
 
if ($user_can_view) {
    $user_id = required_param('userid', PARAM_INT);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nsensor = $_POST['nombre_sensor'];
        $coordx = $_POST['coordenada_x'];
        $coordy = $_POST['coordenada_y'];

        // Verificar si el nuevo valor de nombre_sensor ya existe en la tabla mdl_user_info_data
        $existing_sensor = $DB->record_exists_select('user_info_data', "data = ? AND fieldid = 9 AND userid != ?", array($nsensor, $user_id));

        if ($existing_sensor) {
            echo "El nombre del sensor ya existe. Por favor, ingrese otro valor.";
            echo '<br><a href="' . $url_pagina . '?userid=' . $user_id . '">Volver al formulario</a>';
        } else {
            // Actualizar información en la tabla mdl_user_info_data
            $user_info_data = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 9));
            if ($user_info_data) {
                $user_info_data->data = $nsensor;
                $DB->update_record('user_info_data', $user_info_data);
            }

            // Actualizar información en la tabla mdl_user_info_data para coordenada_x (fieldid=1)
            $user_info_data_x = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 1));
            if ($user_info_data_x) {
                $user_info_data_x->data = $coordx;
                $DB->update_record('user_info_data', $user_info_data_x);
            }

            // Actualizar información en la tabla mdl_user_info_data para coordenada_y (fieldid=2)
            $user_info_data_y = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 2));
            if ($user_info_data_y) {
                $user_info_data_y->data = $coordy;
                $DB->update_record('user_info_data', $user_info_data_y);
            }

            echo "Información actualizada correctamente.";
            echo '<br><a href="' . $url_personal . '">Ir al área personal</a>';
        }
    } else {
        // Obtener información de la tabla mdl_user_info_data
        $user_info_data = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 9));
        $user_info_data_x = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 1));
        $user_info_data_y = $DB->get_record('user_info_data', array('userid' => $user_id, 'fieldid' => 2));

        if ($user_info_data && $user_info_data_x && $user_info_data_y) {
            $nombre_sensor = $user_info_data->data;
            $coordenada_x = $user_info_data_x->data;
            $coordenada_y = $user_info_data_y->data;

            echo '<div class="sensor-form">';
            echo '<form method="post" action="' . $url_pagina . '?userid=' . $user_id . '">';

            echo '<label for="nombre_sensor">Nombre del módulo sensor:</label>';
            echo '<input type="text" id="nombre_sensor" name="nombre_sensor" value="' . $nombre_sensor . '" required>';

            echo '<label for="coordenada_x">Coordenada en X:</label>';
            echo '<input type="text" id="coordenada_x" name="coordenada_x" value="' . $coordenada_x . '" required>';

            echo '<label for="coordenada_y">Coordenada en Y:</label>';
            echo '<input type="text" id="coordenada_y" name="coordenada_y" value="' . $coordenada_y . '" required>';

            echo '<div style="margin-top: 10px;"></div>';

            echo '<input type="submit" value="Actualizar">';

            echo '</form>';
            echo '</div>';
        } else {
            echo "No se pudo obtener la información del usuario.";
        }
    }
} else {
    echo "<span class='current'>Acceso denegado.</span> ";
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
