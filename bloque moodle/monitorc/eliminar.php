<?php
require_once('../../config.php'); // Asegúrate de que la ruta sea correcta para incluir el archivo de configuración

global $DB;

// Obtén el ID del usuario a eliminar
$user_id = required_param('userid', PARAM_INT);

try {
    $transaction = $DB->start_delegated_transaction();

    // Eliminar registros relacionados en la tabla mdl_mediciones
    $DB->delete_records('mediciones', array('userid' => $user_id));

    // Eliminar registros relacionados en la tabla mdl_user_info_data
    $DB->delete_records('user_info_data', array('userid' => $user_id));

    // Eliminar usuario de la tabla mdl_user
    $DB->delete_records('user', array('id' => $user_id));

    $transaction->allow_commit();

    echo "Usuario eliminado correctamente y registros relacionados borrados.";
    echo '<br><a href="' . new moodle_url('/my') . '">Ir al área personal</a>';

} catch (Exception $e) {
    // Si ocurre un error, cancela la transacción
    $transaction->rollback();
    echo "Error al eliminar el usuario: " . $e->getMessage();
    echo '<br><a href="' . new moodle_url('/my') . '">Ir al área personal</a>';
}
?>


