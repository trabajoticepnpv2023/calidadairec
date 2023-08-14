<?php
// Recuperar el nombre del archivo de la consulta
if (isset($_GET['filename'])) {
    $output_filename = $_GET['filename'];

    // Establecer los encabezados para evitar la caché
    header("Expires: 0");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

    // Establecer los encabezados para la descarga
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=$output_filename");
    header("Content-Length: " . filesize($output_filename));

    // Leer el archivo y enviarlo al navegador
    readfile($output_filename);

    // Eliminar el archivo generado después de la descarga
    unlink($output_filename);

    // Finalizar el script después de la descarga para prevenir cualquier salida adicional
    exit;
} else {
    echo "Nombre de archivo no proporcionado.";
}
?>

