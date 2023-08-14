<?php
defined('MOODLE_INTERNAL') || die();



class block_monitorc_manager {
    public function __construct() {
       
    }

    public function download_monitorc($rows) {
        $headers = array('userid', 'fechahora', 'co', 'pm25', 'temp', 'hum');
        $dataRows = array();

         $index = 0;  // Inicializar el índice

        foreach ($rows as $row) {
            $dataRows[$index] = array(
                $row->usuario_id,
                $row->fechahora,
                $row->co,
                $row->pm25,
                $row->temp,
                $row->hum
            );

            $index++;  // Incrementar el índice para la siguiente fila
        }

        array_unshift($dataRows, $headers);

        return block_medicionc_utils::generate_download("mediciones", $dataRows);
    }
}


function agregar_nuevo_usuario($username, $email, $password, $nsensor, $coordx,$coordy) {
    global $DB;
    
    $user = new stdClass();
    $user->username = $username;
    $user->auth = 'manual';
    $user->password = password_hash($password, PASSWORD_DEFAULT);
    $user->firstname = $username; // Puedes cambiar esto a tu preferencia
    $user->lastname = '';
    $user->email = $email;
    
    // Si deseas asignar algún rol al nuevo usuario, puedes hacerlo aquí
    // $user->roles = array(); 
    
    $user->id = $DB->insert_record('user', $user);
    // Campos personalizados
    $custom_fields = array(
        array('fieldid' => 1, 'userid' => $user->id, 'data' => $coordx),
        array('fieldid' => 2, 'userid' => $user->id, 'data' => $coordy),
        array('fieldid' => 9, 'userid' => $user->id, 'data' => $nsensor),
        array('fieldid' => 10, 'userid' => $user->id, 'data' => 1)
    );
    
    // Insertar los valores de los campos personalizados en la tabla user_info_data
    foreach ($custom_fields as $field) {
        $DB->insert_record('user_info_data', $field);
    }
    
    return $user->id;
}
function descarga_conf($userid, $ssid, $pswd) {
    // Contenido de las partes del archivo esp_1raparte.ino y esp_2daparte.ino
    $part1_content = file_get_contents('esp_1raparte.ino');
    $part2_content = file_get_contents('esp_2daparte.ino');

    // Generar el contenido final del archivo
    $final_content = $part1_content . "\nString userid = \"$userid\";\n" . "\nconst char* ssid = \"$ssid\";\n" . "\nconst char* password = \"$pswd\";\n" . $part2_content;

// Generar un nombre único para el archivo basado en la fecha y hora actual
    $timestamp = date("Ymd_His");
    $output_filename = "generated_file_$timestamp.ino";
    // Nombre del archivo a generar

    // Crear el archivo y escribir el contenido
    file_put_contents($output_filename, $final_content);

    // Imprimir una etiqueta <script> para redirigir en JavaScript
    echo '<script>';
    echo 'window.open("conf_esp.php?filename=' . $output_filename . '", "_blank");';
    echo '</script>';

    // Finalizar el script después de la descarga para prevenir cualquier salida adicional
    return "Descarga exitosa"; // Puedes cambiar el mensaje de retorno según tus necesidades
}



class block_medicionc_utils {
    public static function generate_download($downloadname, $rows) {
        global $CFG;

        require_once($CFG->libdir . '/excellib.class.php');

        $workbook = new MoodleExcelWorkbook(clean_filename($downloadname));

        $myxls = $workbook->add_worksheet(get_string('pluginname', 'block_dedication'));

        $rowcount = 0;
        foreach ($rows as $row) {
            foreach ($row as $index => $content) {
                $myxls->write($rowcount, $index, $content);
            }
            $rowcount++;
        }

        $workbook->close();

        return $workbook;
    }
}


?>