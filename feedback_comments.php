<?php
include '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
$cmid = 0;
$sesskey = '';

try {
    require_login();
    require_sesskey();
    $cmid = required_param('id', PARAM_INT);
    $sesskey = required_param('sesskey', PARAM_RAW);
} catch (coding_exception $e) {
    error_log(print_r($e, TRUE));
} catch (require_login_exception $e) {
    error_log(print_r($e, TRUE));
} catch (moodle_exception $e) {
    error_log(print_r($e, TRUE));
}
if (sesskey() === $sesskey) {
    // Passing this do whatever you need to,
#    echo $OUTPUT->header();
    echo html_writer::start_div('success', ['font-weight' => 'bold']);
    $h1 = '<h1>';
    # echo "<h1>Course Module $cmid</h1>";
    try {
        list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'assign');
        if ($course) {
            $h1 .= $course->shortname . '&emsp;';
        }
        if ($cm) {
            $h1 .= $cm->name;
        }
        $h1 .= '</h1>';
        echo $h1;
        echo '<h2>' . get_string('feedback_comments_header', 'block_assign_get_feedback') . '</h2>';
        echo get_feedback_comments($cmid);
    } catch (Exception $exception) {
        error_log(print_r($exception, TRUE));
    }
    echo html_writer::end_div();
#    echo $OUTPUT->footer();
}


function get_feedback_comments(int $cmid): string
{
    global $DB;
    $html = '';
    if ($cmid > 0) {
        $assgn = $DB->sql_concat_join("'<br/>'", ['ag.id', 'cm.course', 'co.shortname', 'cm.id', 'ma.name', 'ac.assignment']);
        $stu = $DB->sql_concat_join("'<br/>'", ['stu.idnumber', 'stu.username', 'stu.firstname', 'stu.middlename', 'stu.lastname']);
        $tea = $DB->sql_concat_join("'<br/>'", ['tea.idnumber', 'tea.username', 'tea.firstname', 'tea.middlename', 'tea.lastname']);
        $sql = /** @lang TEXT */
            "
SELECT 
       $assgn as assignment, $stu as student, $tea as teacher, ag.grade, ac.commenttext 
FROM {course_modules} AS cm 
JOIN {assign} AS ma ON ma.id = cm.instance
JOIN {modules} AS mo ON mo.id = cm.module  
JOIN {course} AS co ON co.id = cm.course 
JOIN {assignfeedback_comments} AS ac ON ac.assignment = ma.id 
JOIN {assign_grades} AS ag ON ag.id = ac.grade 
JOIN {user} AS stu ON stu.id = ag.userid 
JOIN {user} AS tea ON tea.id = ag.grader 
WHERE mo.name  = :module AND cm.id = :cmid AND ac.commenttext > '' ";
        try {
            $records = $DB->get_records_sql($sql, ['module' => 'assign', 'cmid' => $cmid]);
            if ($records) {
                $header = 0;
                # $action = new moodle_url('/blocks/assign_get_feedback/feedback_comments.php', ['id' => $cmid, 'sesskey' => sesskey()]);
                # $html .= '<p>' . html_writer::link($action, get_string('feedback_comments', 'block_assign_get_feedback'),['target'=>'_blank']) . '</p>';
                $html .= '<table>';
                foreach ($records as $record) {
                    $html .= '<tr>';
                    foreach ($record as $name => $value) {
                        // Disallow scripts inside the feedback comments to be executed in the browser
                        $value = str_replace(array('<script', '</script'), array('<filtered', '</filtered'), $value);
                        $html .= '<td style="vertical-align:top;text-align: left;border-bottom: 1px solid #ddd;">';
                        if ($header === 0) {
                            $html .= "<h3>$name</h3><hr/>$value";
                        } else {
                            $html .= $value;
                        }
                        $html .= '</td>';
                    }
                    $header = 1;
                    $html .= '</tr>';
                }
                $html .= '</table>';
            } else {
                $html .= '<p>' . get_string('no_feedback_comments', 'block_assign_get_feedback') . '</p>';
            }
        } catch (Exception $exception) {
            if (isset($CFG->debug)) {
                error_log('COMMENTS_SQL_ERR ' . print_r($exception, TRUE));
            }
        }
    }
    return $html;
}



