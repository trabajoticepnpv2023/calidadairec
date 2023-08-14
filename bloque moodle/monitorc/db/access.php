<?php
/*  DOCUMENTATION
    .............

    Creating 'db' folder with access.php file will hold the new capabilities created by the block. addinstance and
    myaddinstance are the basic capabilities to control the use of individual blocks.

    captype: Read or write capability type, for security reasons system prevents all write capabilities for guest account and
    not-logged-in users.

    archetypes - specifies defaults for roles with standard archetypes, this is used in installs, upgrades and when
                 resetting roles (it is recommended to use only CAP_ALLOW here). Archetypes are defined in mdl_role table.

    clonepermissionsfrom - when you add a new capability, you can tell Moodle to copy the permissions for each role from the
    current settings. ex: 'clonepermissionsfrom' => 'moodle/my:manageblocks';

    There are eight roles (manager, coursecreator, teacher, editingteacher, student, guest, authenticated user,
    authenticated user on site home) with six context levels.

    A context level is a context(space) where the roles can be assigned:
    site/global (CONTEXT_SYSTEM),
    user (CONTEXT_USER),
    coursecategory (CONTEXT_COURSCAT),
    course (CONTEXT_COURSE),
    block (CONTEXT_BLOCK) and
    activity module (CONTEXT_MODULE)

    and more than 449 capabilities (each capability has four access levels: Allow, Prohibit, Not set and Prevent).

    Basic risks:
    RISK_SPAM - user can add visible content to site, send messages to other users; originally protected by !isguest()
    RISK_PERSONAL - access to private personal information - ex: backups with user details, non public information in
                    profile (hidden email), etc.; originally protected by isteacher()
    RISK_XSS - user can submit content that is not cleaned (both HTML with active content and unprotected files);
               originally protected by isteacher()
    RISK_CONFIG - user can change global configuration, actions are missing sanity checks
    RISK_MANAGETRUST - manage trust bitmasks of other users
    RISK_DATALOSS - can destroy large amounts of information that cannot easily be recovered.

*/

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/monitorc:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/monitorc:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/monitorc:viewlist' => array(

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'guest' => CAP_PREVENT,
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);