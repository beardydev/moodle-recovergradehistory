<?php
define('CLI_SCRIPT', 1);

$configpos  = array_search('--config', $argv);
$coursepos  = array_search('--courseid', $argv);
$userpos    = array_search('--userid', $argv);
$forceall   = array_search('--forceall', $argv);

// Check params set and we're good to go.
if (array_search('--help', $argv) || !$configpos || !$coursepos || (!$userpos && !$forceall)) {
    echo 'Usage:
    --help                          Shows this
    --config /dir/config.php        Set the path to config.php
    --courseid id                   Set the id of course to update
    --userid id                     Set the id of user to update
    --forceall                      Attempt to update each user enrolled on the course

    Example:
        php recovergradehistory.php --config www/config.php --course 15 --userid 98
        php recovergradehistory.php --config www/config.php --course 4 --forceall';
} else {

    // Config file.
    if (isset($argv[$configpos + 1])) {
        if (!@include_once($argv[$configpos + 1])) {
            die('Invalid config.php location' . PHP_EOL);
        }
    }

    global $DB;

    // Course ID.
    if (isset($argv[$coursepos + 1])) {
        $courseid = $argv[$coursepos + 1];

        if (!is_number($courseid) || !$DB->record_exists('course', array('id' => $courseid))) {
            die('Course invalid or does not exist' . PHP_EOL);
        }
    }

    // User ID.
    if ($userpos || $forceall) {
        $userid = $argv[$userpos + 1];

        if (!(is_number($userid) || $forceall) && !$DB->record_exists('user', array('id' => $userid))) {
            die('User invalid or does not exist' . PHP_EOL);
        }
    }

    // Get users.
    if ($forceall) {
        $enrolids = $DB->get_fieldset_select('enrol', 'id', "courseid = {$courseid}");
        $enrolids = implode(',', $enrolids);
        $userids = $DB->get_fieldset_select('user_enrolments', 'userid', "enrolid IN ({$enrolids})");
    } else if (isset($userid)) {
        $userids = array($userid);
    } else {
        die;
    }
    unset($userid);

    // Attempt to restore historic grades.
    require_once($CFG->libdir . '/gradelib.php');
    foreach ($userids as $userid) {
        $result = grade_recover_history_grades($userid, $courseid);

        echo 'Recovering history for userid ' . $userid . ' ';
        echo $result ? 'successful' : 'failed';
        echo  PHP_EOL;
    }
}

echo PHP_EOL;
