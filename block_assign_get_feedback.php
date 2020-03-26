<?php /** @noinspection GlobalVariableUsageInspection */
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');
require_once __DIR__ . '/../../config.php';

class block_assign_get_feedback extends block_base
{
    private $page_url;
    private $cmid;
    private $cm;
    private $course;

    public final function init(): void
    {
        // set the title of this plugin
        try {
            $this->title = get_string('pluginname', 'block_assign_get_feedback');
            $this->page_url = $this->fullpageurl();
            $this->cmid = $this->get_cmid();
        } catch (coding_exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * @return array
     */
    public final function applicable_formats(): array
    {
        return array('all' => TRUE);
    }

    /**
     * @return string
     */
    private function fullpageurl(): string
    {
        if ($this->page_url === NULL) {
            global $_SERVER;
            $pageURL = 'http';
            if ($_SERVER["HTTPS"] === "on") {
                $pageURL .= "s";
            }
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            }
            $this->page_url = $pageURL;
        }
        return $this->page_url;
    }

    /**
     * UTF-8 aware parse_url() replacement.
     *
     * @param $url
     * @return array
     */
    private function mb_parse_url(string $url): array
    {
        $enc_url = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            function ($matches) {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($enc_url);

        if ($parts === FALSE) {
            throw new InvalidArgumentException('Malformed URL: ' . $url);
        }

        foreach ($parts as $name => $value) {
            $parts[$name] = urldecode($value);
        }

        return $parts;
    }

    /**
     * @return int
     */
    private function get_cmid(): int
    {
        $cmid = 0;
        $params = [];
        $page_url = $this->fullpageurl();
        $page_path = $this->mb_parse_url($page_url)[PHP_URL_PATH];
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        error_log(var_dump($page_path, TRUE));
        if (mb_strpos($page_path, '/mod/assign/view.php') !== FALSE) {
            $url_query = $this->mb_parse_url($page_url)[PHP_URL_QUERY];
            parse_str($url_query, $params);
            if (isset($params['id']) && (int)$params['id'] > 0) {
                $cmid = (int)$params['id'];
                try {
                    list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'assign');
                    if ($course) {
                        $this->course = $course;
                    }
                    if ($cm) {
                        $this->cm = $cm;
                    }
                } catch (Exception $exception) {
                    error_log($exception->getMessage());
                }
            }
        }
        return $cmid;
    }

    /**
     * Method               get_content
     *
     * Purpose              create all the block contents and present it
     *                      Subscriptions Block Contents creation function
     *
     * Parameters           N/A
     *
     * Returns
     * @return              string, as HTML content for the block
     *
     */
    public final function get_content(): ?stdClass
    {
        // define usage of global variables
        global $PAGE, $COURSE;// , $DB , $CFG ; // $USER, $SITE , $OUTPUT, $THEME, $OUTPUT ;

        // Check if the page is referring to a glossary module view activity
        if ('mod-assign-grading' !== $PAGE->pagetype) {
            if (NULL === $this->content) {
                $this->content = new stdClass();
                $this->content->text = $PAGE->pagetype;
            }
            return $this->content;
        }

        if (NULL !== $this->title) {
            try {
                $this->title = get_string('blockheader', 'block_assign_get_feedback');
            } catch (coding_exception $e) {
                error_log($e->getMessage());
            }
        }

        // if the contents are already set, just return them
        if ($this->content !== NULL) {
            return $this->content;
        }

        // this is only for logged in users
        try {
            if (!isloggedin() || isguestuser()) {
                return '';
            }
        } catch (coding_exception $e) {
            error_log($e->getMessage());
        }

        // get the current moodle configuration
        require_once __DIR__ . '/../../config.php';

        // this is only for logged in users
        try {
            require_login();
        } catch (coding_exception $e) {
            error_log($e->getMessage());
        } catch (require_login_exception $e) {
            error_log($e->getMessage());
        } catch (moodle_exception $e) {
            error_log($e->getMessage());
        }

        // get the module information
        try {
            $courseinfo = get_fast_modinfo($COURSE);
        } catch (moodle_exception $e) {
            error_log($e->getMessage());
        }

        // prapare for contents
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->text .= '<strong>' . $PAGE->title . '</strong>';

        // add a footer for the block
        try {
            $this->content->footer = '<hr style="display: block!important;"/><div style="text-align:center;">' . get_string('blockfooter', 'block_assign_get_feedback') . '</div>';
        } catch (coding_exception $e) {
            error_log($e->getMessage());
        }


        // get the id parameter if exists
        $cmid = $this->get_cmid();

        // check if there is a valid glossary view page
        if ($cmid > 0) {
            // set page context
            $PAGE->set_context(context_module::instance($cmid));
            try {
                if (isset($courseinfo) && $courseinfo->get_cm($cmid)) {
                    $cm = $courseinfo->get_cm($cmid);
                } else {
                    return $this->content;
                }
            } catch (Throwable $e) {
                error_log('ERROR: assign_get_feedback set_context ' . $e->getMessage());
                return $this->content;
            }

            // Check if the course module is available and it is visible and it is visible to the user and it is an assign module
            if (!(TRUE === $cm->available && '1' === $cm->visible && TRUE === $cm->uservisible && 'assign' === $cm->modname)) {
                return $this->content;
            }

            // get glossary ID
            $cmid = (int)$cm->instance;

            // show link to feedback messages
            $links = $this->show_links($cmid);

            // add the contents of the form to the block
            $this->content->text .= $links;
        }
        // Finish and return contents
        return $this->content;
    }

    private function show_feedback_comments_link(int $cmid): string
    {
        global $DB;
        $html = '';
        if ($cmid > 0) {
            $stu = $DB->sql_concat_join(' ', ['cm.course', 'co.shortname', 'cm.id', 'ma.name', 'ac.assignment']);
            $tea = $DB->sql_concat_join(' ', ['tea.idnumber', 'tea.username', 'tea.firstname', 'tea.lastname']);
            $sql = /** @lang TEXT */
                "
SELECT 
       ag.id, $stu as student, $tea as teacher, ag.grade, ac.commenttext 
FROM {course_modules} AS cm 
JOIN {assign} AS ma ON ma.id = cm.instance
JOIN {modules} AS mo ON mo.id = cm.module  
JOIN {course} AS co ON co.id = cm.course 
JOIN {assignfeedback_comments} AS ac ON ac.assignment = ma.id 
JOIN {assign_grades} AS ag ON ag.id = ac.grade 
JOIN {user} AS stu ON stu.id = ag.userid 
JOIN {user} AS tea ON tea.id = ag.grader 
WHERE mo.name  = :module AND cm.id = :cmid ";
            try {
                $records = $DB->get_records_sql($sql, ['module' => 'assign', 'cmid' => $cmid]);
                if ($records) {
                    $action = new moodle_url('/blocks/assign_get_feedback/feedback_comments.php', ['id' => $cmid, 'sesskey' => sesskey()]);
                    $html .= html_writer::link($action, get_string('feedback_comments', 'block_assign_get_feedback'));
                } else {
                    $html .= get_string('no_feedback_comments', 'block_assign_get_feedback');
                }
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
        return $html;
    }

    private function show_links(int $cmid): string
    {

        $html = '';
        $html .= $this->show_feedback_comments_link($cmid);
        return $html;
    }
}
