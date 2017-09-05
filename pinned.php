<?php
//

require_once('../../config.php');

require_login();

if(!isset($_GET['id'])) {
    echo 'No course specified.';
}

switch($_GET['action']) {
    case 'pin':
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->courseid = $_GET['id'];
        $DB->insert_record('block_gu_dashboard_pinned', $record);
        break;
    case 'unpin':
        $record = Array(
            'userid'=>$USER->id,
            'courseid'=>$_GET['id']
        
        );
        $DB->delete_records('block_gu_dashboard_pinned', $record);
        break;
}

redirect(new moodle_url('/my/index.php'));

?>