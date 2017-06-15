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

/**
 * library class for tcount social_interaction
 *
 * @package tcountsocial_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_tcount\social;

defined('MOODLE_INTERNAL') || die();
require_once ($CFG->dirroot . '/mod/tcount/tcountplugin.php');


class social_interaction {

    /**
     * Message published initially, with to recipient.
     *
     * @var string
     */
    const POST = 'post';

    /**
     * TODO: To be defined.
     *
     * @var string
     */
    const MESSAGE = 'message';

    /**
     * Message published in response to other.
     *
     * @var string
     */
    const REPLY = 'reply';

    /**
     * Mention to an user inside a message.
     *
     * @var string
     */
    const MENTION = 'mention';

    /**
     * Mark on a message indicating attention.
     *
     * @var string
     */
    const REACTION = 'reaction';

    /**
     * Name of the subplugin that generated this
     *
     * @var string
     */
    public $source;

    public $icon;

    /**
     * Unique identifier of this interaction.
     *
     * @var string
     */
    public $uid;

    /**
     * moodle userid
     *
     * @var string
     */
    public $fromid;

    /**
     * Social network native userid.
     *
     * @var string
     */
    public $nativefrom;
    /**
     * Social network native username.
     *
     * @var string
     */
    public $nativefromname;
    /**
     *
     * @var string moodle userid
     */
    public $toid;

    /**
     *
     * @var string social network userid
     */
    public $nativeto;
    /**
     * Social network native username.
     *
     * @var string
     */
    public $nativetoname;
    /**
     * social_interaction uid that originated this interaction.
     *
     * @var social_interaction
     */
    public $parentinteraction;

    /**
     *
     * @var \DateTime time of creation of the interaction
     */
    public $timestamp;

    /**
     * Type of interaction: post, reply, share, etc.
     *
     * @var string
     */
    public $type;

    /**
     * Type of the interacion as defined by the social service.
     *
     * @var string
     */
    public $nativetype;

    public $description;

    public $shortdescription;

    public $weight;

    /**
     * native JSON representation of the item
     *
     * @var string
     */
    public $rawdata;

    /**
     * Construct an instance from a database record
     *
     * @param unknown $record
     */
    static function build($record) {
        $inter = new social_interaction();
        foreach ($record as $key => $value) {
            if ($key == 'timestamp') {
                if ($value == null) {
                    $timestamp = null;
                } else {
                    $date = new \DateTime();
                    $timestamp = $date->setTimestamp($record->timestamp);
                }
                $inter->timestamp = $timestamp;
            } else {
                $inter->$key = $value;
            }
        }
        
        return $inter;
    }

    static function load_interactions($tcountid, $conditions=null, $fromdate=null, $todate=null,$users=null) {
        global $DB;
        $interactions = [];
        $select = "tcount=?";
        $params[] = $tcountid;
        if ($conditions != '') {
            $select .= " AND " . $conditions;
        }
        if ($fromdate) {
            $select .= " AND timestamp >= ? "; // TODO: Format date
            $params[] = $fromdate;
        }
        if ($fromdate) {
            $select .= " AND timestamp <= ? "; // TODO: Format date
            $params[] = $todate;
        }
        if ($users){
            list($inwhere,$paramsin) = $DB->get_in_or_equal($users);
            $select .= " AND ( fromid $inwhere OR toid $inwhere) ";
            $params = array_merge($params, $paramsin, $paramsin);
        }
        $records = $DB->get_records_select('tcount_interactions', $select, $params, 'timestamp');
        foreach ($records as $record) {
            $interactions[] = self::build($record);
        }
        return $interactions;
    }

    /**
     * Save the list of interactions in the database.
     *
     * @param array $interactions
     * @param int $tcountid
     */
    static function store_interactions(array $interactions, $tcountid) {
        global $DB;
        $uids = array_map(function ($inter) {
            return $inter->uid;
        }, $interactions);
        list($whereuids, $params) = $DB->get_in_or_equal($uids);
        $where = "uid $whereuids and tcount=?";
        $params[] = $tcountid;
        $DB->delete_records_select('tcount_interactions', $where, $params);
        $fields = ['uid', 'tcount', 'fromid', 'nativefrom','nativefromname', 'toid', 'nativeto','nativetoname', 'parentinteraction', 'source', 'timestamp', 
                        'type', 'nativetype', 'description', 'rawdata'];
        $records = [];
        foreach ($interactions as $inter) {
            $record = new \stdClass();
            foreach ($fields as $field) {
                $record->$field = isset($inter->$field) ? $inter->$field : null;
            }
            if ($record->timestamp) {
                $record->timestamp = $inter->timestamp->getTimeStamp();
            }
            $record->tcount = $tcountid;
            $records[] = $record;
        }
        foreach ($records as $record) {
            $DB->insert_record('tcount_interactions', $record);
        }
        // $DB->insert_records('tcount_interactions', $records);
    }
}
