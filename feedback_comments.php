<?php
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
require_login();
require_sesskey();
$cmid = required_param('id', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_RAW);
if (sesskey() === $sesskey) {
    // Passing this do whatever you need to,
    echo $OUTPUT->header();
    echo html_writer::start_div('success',['font-weight'=>'bold']);
    echo "<h1>Course Module $cmid</h1>";
    echo html_writer::end_div();
    echo $OUTPUT->footer();
}