<?php
/*  DOCUMENTATION
    .............

    The actual display of your block is block_monitor.php

    init() method is essential part to pass the class variables:
    $this->title: to display the title in the header of your block.
    $this->version (optional unless you need Moodle to perform automatic updates) and there is no return value to be expected
    from init().

    $CFG stands for Configuration. CFG is a global variable can be used in any moodle page, contains Moodle's
    root, data(moodledata) and database configuration settings and other config values.

    get_string converts an array of string names to localised strings for a specific plugin. It looks formal when you code
    with language strings instead of manual text. It's a good habit of writing manual text to strings.

    has_config() method states that the block has a settings.php file. This method specifies whether your block wants to
    present additional configuration settings.

    get_content method should define $this->content variable of your block.
    If $this->content_type is BLOCK_TYPE_TEXT, then $this->content is expected to have the following member variables:
    text - a string of arbitrary length and content displayed inside the main area of the block, and can contain HTML.
    footer - a string of arbitrary length and content displayed below the text, using a smaller font size.
    It can also contain HTML.

    instance_allow_multiple() method indicates whether you want to allow multiple block instances in the same page or not.
    If you do allow multiple instances, it is assumed that you will also be providing per-instance configuration for the
    block.

*/
defined('MOODLE_INTERNAL') || die();
// Class name must be named exactly the block folder name.
class block_monitorc extends block_base {
    
    function init() {
        global $CFG;
        $this->title = get_string('monitorc', 'block_monitorc'); // (Title of your block).
    }

    function has_config() {
        return true;
    }

public function get_content() {
    if ($this->content !== null) {
        return $this->content;
    }

    global $OUTPUT, $USER;

    $this->content = new stdClass;

    // Obtener los roles del usuario en el contexto del sistema
    $system_context = context_system::instance();
    $system_roles = get_user_roles($system_context, $USER->id);

    // Verificar si el usuario tiene el rol de 'editingteacher' o 'manager'
    $user_can_view = false;
    foreach ($system_roles as $role) {
        if($role->shortname == 'editingteacher' || $role->shortname == 'manager' || $role->shortname == 'adminaire') {
            $user_can_view = true;
            break;
        }
    }

    if ($user_can_view) {
        // Botón que abre la página con registros
        $this->content->text .= '<div class="centered-button">';
        $url = new moodle_url('/blocks/monitorc/monitorc.php');
        $this->content->text .= $OUTPUT->single_button($url, "Registro mediciones", 'get');
        
        if ($role->shortname == 'manager' || $role->shortname == 'adminaire') {
            $url2 = new moodle_url('/blocks/monitorc/agregarc.php');
            $this->content->text .= $OUTPUT->single_button($url2, "Agregar sensor", 'get');
            $url3 = new moodle_url('/blocks/monitorc/listar.php');
            $this->content->text .= $OUTPUT->single_button($url3, "Listar sensores", 'get');
        }
         $this->content->text .= '</div>';
    } else {
        $this->content->text .= "Solo disponible para el rol profesor";
    }

    return $this->content;
}


// Create multiple instances on a page.
public function instance_allow_multiple() {
    return false;
}

}

?>

<script src="js/jquery-3.2.1.min.js"></script>
<script src="js/bootstrap.js"></script>