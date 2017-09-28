<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/*
 * **************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
namespace mod_msocial\connector;

use mod_msocial\pki_info;
use questournament\GraphNodes\GraphEdge;
use questournament\questournament as questournament;
use msocial\msocial_plugin;
use mod_msocial\social_user;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/msocialconnectorplugin.php');
require_once($CFG->dirroot . '/mod/msocial/moodleactivityplugin.php');

/** library class for social network questournament plugin extending social plugin base class
 *
 * @package msocialconnector_questournament
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class msocial_connector_questournament extends msocial_connector_moodleactivity {
    const CONFIG_ACTIVITIES = 'activities';
    const CONFIG_ACTIVITY_NAMES = 'activitynames';

    // To remap them after a restore.

    /** Get the name of the plugin
     *
     * @return string */
    public function get_name() {
        return get_string('pluginname', 'msocialconnector_questournament');
    }

    public function get_subtype() {
        return 'questournament';
    }

    public function get_category() {
        return msocial_plugin::CAT_ANALYSIS;
    }

    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\connector\msocial_connector_plugin::get_icon() */
    public function get_icon() {
        return new \moodle_url('/mod/msocial/connector/questournament/pix/questournament_icon.gif');
    }

    protected function get_mod_name() {
        return 'forum';
    }

    public function get_pki_list() {
        $pkiobjs['qposts'] = new pki_info('qposts', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::POST, 'POST',
                social_interaction::DIRECTION_AUTHOR);
        $pkiobjs['qreplies'] = new pki_info('qreplies', null, pki_info::PKI_INDIVIDUAL, pki_info::PKI_CALCULATED, social_interaction::REPLY, '*',
                social_interaction::DIRECTION_RECIPIENT);
        $pkiobjs['max_qposts'] = new pki_info('max_qposts', null, pki_info::PKI_AGREGATED, pki_info::PKI_CALCULATED);
        $pkiobjs['max_qreplies'] = new pki_info('max_qreplies', null, pki_info::PKI_AGREGATED,  pki_info::PKI_CALCULATED);
        return $pkiobjs;
    }

    /** TODO
     * @param \stdClass $challenge questournament post.
     * @param array(\stdClass) $posts other posts for lookup. */
    protected function process_challenge($challenge, $users) {
        $challengeinteraction = new social_interaction();
        $challengeinteraction->uid = $challenge->id;
        $challengeinteraction->nativefrom = $challenge->userid;
        $challengeinteraction->nativefromname = fullname($users[$challenge->userid]);
        $challengeinteraction->fromid = $challenge->userid;
        $challengeinteraction->rawdata = json_encode($challenge);
        $time = new \DateTime();
        $time->setTimestamp($challenge->timecreated);
        $challengeinteraction->timestamp = $time;

        $challengeinteraction->type = social_interaction::POST;
        $challengeinteraction->nativetype = 'CHALLENGE';

        $message = $challenge->description;
        $challengeinteraction->description = $message == '' ? 'No text.' : $message;
        $this->register_interaction($challengeinteraction);
        return $challengeinteraction;
    }
    protected function process_answer($answer, $users) {
        $answerinteraction = new social_interaction();
        $answerinteraction->uid = $answer->id;
        $answerinteraction->nativefrom = $answer->userid;
        $answerinteraction->nativefromname = fullname($users[$answer->userid]);
        $answerinteraction->fromid = $answer->userid;
        $answerinteraction->rawdata = json_encode($answer);

        $challengeinteraction = $this->lastinteractions[$answer->submissionid];

        $answerinteraction->toid = $challengeinteraction->fromid;
        $answerinteraction->nativeto  = $challengeinteraction->fromid;
        $answerinteraction->nativetoname = fullname($users[$answerinteraction->nativeto]);

        $time = new \DateTime();
        $time->setTimestamp($answer->timecreated);
        $answerinteraction->timestamp = $time;

        $answerinteraction->type = social_interaction::REPLY;
        $answerinteraction->nativetype = 'ANSWER';

        $message = $answer->description;
        $answerinteraction->description = $message == '' ? 'No text.' : $message;
        $this->register_interaction($answerinteraction);
        return $answerinteraction;
    }
    /** Classify the text as too short to be relevant
     *
     * @param GraphEdge $comment
     * @return boolean $ok */
    public function is_short_comment($message) {
        $ok = false;
        // Cuento el numero de palabras del mensaje.
        $numwords = str_word_count($message, 0);
        // Si el mensaje tiene dos o menos palabras ignoramos dicho mensaje.
        if ($numwords <= $this->min_words) {
            $ok = true;
        }
        return $ok;
    }

    /**
     * @todo
     *
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        global $DB;

        $errormessage = null;
        $result = new \stdClass();
        $result->messages = [];
        $result->errors = [];

        $this->lastinteractions = [];
        $contextcourse = \context_course::instance($this->msocial->course);
        list($students, $nonstudents, $active, $users) = array_values(msocial_get_users_by_type($contextcourse));

        try {
            // Query questournament...
            $since = '';
            $lastharvest = $this->get_config(self::LAST_HARVEST_TIME);
            $activities = $this->get_config(self::CONFIG_ACTIVITIES);
            if ($lastharvest) {
                $since = "&since=$lastharvest";
            }
            // Read quest challenges.
            $params = [$this->msocial->course];
            if ($activities) {
                list($insql, $inparams) = $DB->get_in_or_equal($activities);
                $sql = 'select s.* from {quest_submissions} s left join {quest} q on q.id = s.questid ' .
                        'where q.course = ? and s.questid ' . $insql;
                $sqlanswers = 'select a.* from {quest_answerss} a left join {quest} q on q.id = a.questid ' .
                        'where q.course = ? and a.questid ' . $insql;
                $params = array_merge($params, $inparams);
            } else {
                $sql = 'select s.* from {quest_submissions} s left join {quest} q on q.id = s.questid ' .
                        'where q.course = ?';
                $sqlanswers = 'select a.* from {quest_answerss} a left join {quest} q on q.id = a.questid ' .
                        'where q.course = ?';
            }
            $challenges = $DB->get_records_sql($sql, $params);
            // Iterate the challenges.
            foreach ($challenges as $challenge) {
                $this->process_challenge($challenge, $users);
            }
            $answers = $DB->get_records_sql($sqlanswers, $params);
            // Iterate the $answers.
            foreach ($answers as $anwer) {
                $this->process_answer($anwer, $users);
            }
        } catch (\Exception $e) {
            $msocial = $this->msocial;
            $errormessage = "For module msocial\\connector\\questournament: $msocial->name  in course (id=$msocial->course) " . "ERROR:" .
                     $e->getMessage();
            $result->messages[] = $errormessage;
            $result->errors[] = (object) ['message' => $errormessage];
        }
        return $this->post_harvest($result);
    }
}