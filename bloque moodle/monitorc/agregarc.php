<?php
require_once("../../config.php");

global $DB, $PAGE, $OUTPUT, $CFG;

$styles_url = new moodle_url('/blocks/monitorc/css/styles.css');
$PAGE->requires->css($styles_url);

require_once('monitorc_lib.php');

// Verificar si el usuario ha iniciado sesión
require_login();
$context = context_system::instance();

$url_pagina = new moodle_url('/blocks/monitorc/agregarc.php');
$url_personal = new moodle_url('/my');

$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->navbar->add(get_string('pluginname', 'block_dedication'), new moodle_url('/blocks/monitorc/agregarc.php'));
$PAGE->set_url($url_pagina);
$PAGE->set_title('Agregar sensor');

echo $OUTPUT->header();

echo $OUTPUT->box_start();

// Obtener los roles del usuario en el contexto del sistema
$system_context = context_system::instance();
$system_roles = get_user_roles($system_context, $USER->id);

// Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
$user_can_view = false;
foreach ($system_roles as $role) {
    if ( $role->shortname == 'adminaire' || $role->shortname == 'manager') {
        $user_can_view = true;
        break;
    }
}
 
if ($user_can_view) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nsensor = $_POST['nombre_sensor'];
        $coordx = $_POST['coordenada_x'];
        $coordy = $_POST['coordenada_y'];
        $password = "xDUJU#2Qp3rGU";
        
        // Verificar si el nombre del sensor ya existe en la tabla mdl_user_info_data
        $sensor_exists = $DB->record_exists_select('user_info_data', "data = ? AND fieldid = 9", array($nsensor));
        
        if ($sensor_exists) {
            echo "El nombre del sensor ya existe. Por favor, ingrese otro valor.";
             echo '<br><a href="' . $url_pagina . '">Volver al formulario</a>';
        } else {
            $last_user_id = $DB->get_field_sql('SELECT MAX(id) FROM {user}');
            $nuevo_usuario = "sensor" . ($last_user_id + 1);
            $correo_usuario = "actualizar" . ($last_user_id + 1) . "@calidadairec.es";
            
            // Llamada a la función para agregar nuevo usuario
            $nuevo_user_id = agregar_nuevo_usuario($nuevo_usuario, $correo_usuario, $password, $nsensor, $coordx, $coordy);
    
        echo "Nuevo usuario y sensor agregados exitosamente. El número de usuario creado es: " . $nuevo_user_id;
        
        echo '<br><a href="' . $url_personal . '">Ir al área personal</a>';
        }
        }else {
            
            echo '<br><a href="' . new moodle_url('/my') . '">Ir al área personal</a>';
        // Sección de formulario para agregar sensor y usuario
        echo '<div class="sensor-form">';
        echo '<form method="post" action="' . $url_pagina . '">';
        
        echo '<label for="nombre_sensor">Nombre del módulo sensor:</label>';
        echo '<input type="text" id="nombre_sensor" name="nombre_sensor" required>';
        
        echo '<label for="coordenada_x">Coordenada en X:</label>';
        echo '<input type="text" id="coordenada_x" name="coordenada_x" required>';
        
        echo '<label for="coordenada_y">Coordenada en Y:</label>';
        echo '<input type="text" id="coordenada_y" name="coordenada_y" required>';
        
        echo '<div style="margin-top: 10px;"></div>';
        
        echo '<input type="submit" value="Agregar">';
        
        echo '</form>';
        echo '</div>';
    }
} else {
    echo "<span class='current'>Acceso denegado.</span> ";
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
?>
