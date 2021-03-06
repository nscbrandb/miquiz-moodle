<?php

require_once('../../config.php');
require_once("lib.php");

$id = required_param('id', PARAM_INT);  // Course Module ID

$url = new moodle_url('/mod/miquiz/view.php', array('id'=>$id));
$PAGE->set_url($url);
if (!$cm = get_coursemodule_from_id('miquiz', $id)) {
    print_error('Course Module ID was incorrect'); // NOTE this is invalid use of print_error, must be a lang string id
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');  // NOTE As above
}
if (!$miquiz = $DB->get_record('miquiz', array('id'=> $cm->instance))) {
    print_error('course module is incorrect'); // NOTE As above
}
require_login($course, false, $cm);

$url = get_config('mod_miquiz', 'instanceurl');
$context = context_module::instance($cm->id);

echo $OUTPUT->header();

echo '<h3>'.$miquiz->intro.'</h3></br>';
echo '<form action="'.$url.'" target="_blanc"><input class="btn btn-primary" id="id_tomiquizbutton" type="submit" value="'.get_string('miquiz_view_openlink', 'miquiz').'"></form>';

echo '<br/><b>'.get_string('miquiz_view_overview', 'miquiz').'</b><br/>';
echo get_string('miquiz_view_shortname', 'miquiz').': '.$miquiz->short_name.'<br/>';
$date = new DateTime("", core_date::get_server_timezone_object());
$date->setTimestamp($miquiz->assesstimestart);
echo get_string('miquiz_view_assesstimestart', 'miquiz').': '.date_format($date, 'd.m.Y H:i').'<br/>';
$date = new DateTime("", core_date::get_server_timezone_object());
$date->setTimestamp($miquiz->timeuntilproductive);
echo get_string('miquiz_view_timeuntilproductive', 'miquiz').': '.date_format($date, 'd.m.Y H:i').'<br/>';
$date = new DateTime("", core_date::get_server_timezone_object());
$date->setTimestamp($miquiz->assesstimefinish);
echo get_string('miquiz_view_assesstimefinish', 'miquiz').': '.date_format($date, 'd.m.Y H:i').'<br/>';
echo get_string('miquiz_view_scoremode', 'miquiz').': '.get_string('miquiz_create_scoremode_'.$miquiz->scoremode, 'miquiz').'<br/>';

if (has_capability('moodle/course:manageactivities', $context)) {
    echo '<br/><b>'.get_string('miquiz_view_questions', 'miquiz').'</b><br/>';

    $reports = [];
    if (has_capability('moodle/course:manageactivities', $context)) {
        $resp = miquiz::api_get("api/categories/" . $miquiz->miquizcategoryid . "/reports");
        foreach($resp as $report){
            if(!isset($reports[$report["questionId"]]))
                $reports[$report["questionId"]] = [];
            $reports[$report["questionId"]][] = $report;
        }
    }

    $quiz_questions = $DB->get_records('miquiz_questions', array('quizid' => $miquiz->id));
    echo '<ul class="list-group">';
    if(count($quiz_questions) > 0){
        $question_ids = "";
        foreach($quiz_questions as $quiz_question){
            if($question_ids != ""){
                $question_ids .= " OR ";
            }
            $question_ids .="id=".$quiz_question->questionid;
        }
        $questions = $DB->get_records_sql('SELECT * FROM {question} q WHERE '. $question_ids);

        foreach($questions as $question){
            $miquiz_question = $DB->get_record_sql('SELECT miquizquestionid FROM {miquiz_questions} WHERE questionid='. $question->id.' AND quizid='.$miquiz->id);
            echo '<li class="list-group-item">';
            $category = $DB->get_record('question_categories', array('id' => $question->category));
            echo $question->name.' <span class="badge">'.$category->name.'</span><ul class="list-group">';
            if (isset($reports[$miquiz_question->miquizquestionid])) {
            foreach($reports[$miquiz_question->miquizquestionid] as $report){
                echo '<li class="list-group-item"><u>'.$report['category'].'</u></br>';
                echo $report['message'];
                echo '</br><i>'.$report['author'].'</i></li>';
            }
            }
            echo "</ul></li>";
        }
    }
    echo '</ul>';
}

if (has_capability('moodle/course:manageactivities', $context)) {
    echo '<br/><b>'.get_string('miquiz_view_statistics', 'miquiz').'</b><br/>';
    $score_training = 0;
    $score_duel = 0;
    $score_training_correct = 0;
    $score_duel_correct = 0;
    $user_stats = miquiz::api_get("api/categories/" . $miquiz->miquizcategoryid . "/user-stats");
    $user_obj = miquiz::api_get("api/users");

    //teacher area
    //https://docs.moodle.org/dev/Roles
    $resp = miquiz::api_get("api/categories/" . $miquiz->miquizcategoryid . "/stats");
    $answeredQuestions_training_total = $resp["answeredQuestions"]["training"]["total"];
    $answeredQuestions_training_correct = $resp["answeredQuestions"]["training"]["correct"];
    $answeredQuestions_duel_total = $resp["answeredQuestions"]["duel"]["total"];
    $answeredQuestions_duel_correct = $resp["answeredQuestions"]["duel"]["correct"];

    $answeredQuestions_total = number_format($answeredQuestions_training_total+$answeredQuestions_duel_total,0);
    $answeredQuestions_correct = number_format($answeredQuestions_training_correct+$answeredQuestions_duel_correct,0);
    $answeredQuestions_wrong = number_format($answeredQuestions_total-$answeredQuestions_correct,0);

    $eps = pow(10000000, -1);
    $rel_answeredQuestions_total = number_format($answeredQuestions_total/($answeredQuestions_total+$eps),2);
    $rel_answeredQuestions_correct = number_format($answeredQuestions_correct/($answeredQuestions_total+$eps),2);
    $rel_answeredQuestions_wrong = number_format($answeredQuestions_wrong/($answeredQuestions_total+$eps),2);

    $answered_abs = "(".$answeredQuestions_total."/".$answeredQuestions_correct."/".$answeredQuestions_wrong.")";
    $answered_rel = "(".$rel_answeredQuestions_total."/".$rel_answeredQuestions_correct."/".$rel_answeredQuestions_wrong.")";

    echo get_string('miquiz_view_statistics_answeredquestions', 'miquiz').': '.$answered_abs.' '.$answered_rel.'<br/>';

    echo '<br/><b>'.get_string('miquiz_view_statistics_user', 'miquiz').'</b><br/>';
    foreach($user_stats as $user_score){
        $score_training = $user_score["score"]["training"]["total"];
        $score_duel = $user_score["score"]["duel"]["total"];
        $score_training_possible = $user_score["score"]["training"]["possible"];
        $score_duel_possible = $user_score["score"]["duel"]["possible"];
        $answeredQuestions_training_total = $user_score["answeredQuestions"]["training"]["total"];
        $answeredQuestions_training_correct = $user_score["answeredQuestions"]["training"]["correct"];
        $answeredQuestions_duel_total = $user_score["answeredQuestions"]["duel"]["total"];
        $answeredQuestions_duel_correct = $user_score["answeredQuestions"]["duel"]["correct"];

        $score = $score_training+$score_duel;
        $score_possible = $score_training_possible+$score_duel_possible;

        $answeredQuestions_total = number_format($answeredQuestions_training_total+$answeredQuestions_duel_total,0);
        $answeredQuestions_correct = number_format($answeredQuestions_training_correct+$answeredQuestions_duel_correct,0);
        $answeredQuestions_wrong = number_format($answeredQuestions_total-$answeredQuestions_correct,0);
        $rel_answeredQuestions_total = number_format($answeredQuestions_total/($answeredQuestions_total+$eps),2);
        $rel_answeredQuestions_correct = number_format($answeredQuestions_correct/($answeredQuestions_total+$eps),2);
        $rel_answeredQuestions_wrong = number_format($answeredQuestions_wrong/($answeredQuestions_total+$eps),2);

        $answered_abs = "(".$answeredQuestions_total."/".$answeredQuestions_correct."/".$answeredQuestions_wrong.")";
        $answered_rel = "(".$rel_answeredQuestions_total."/".$rel_answeredQuestions_correct."/".$rel_answeredQuestions_wrong.")";

        $username = miquiz::get_username($user_score["userId"], $user_obj);
        echo '<div class="well"><u>'.$username.'</u><ul>';
        echo '<li>'.get_string('miquiz_view_statistics_answeredquestions', 'miquiz').': '.$answered_abs.' '.$answered_rel.'</li>';
        echo '<li>'.get_string('miquiz_view_statistics_totalscore', 'miquiz').': '.$score.'/'.$score_possible.'</li>';
        echo "</ul></div>";
    }
}

echo $OUTPUT->footer();
