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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */

/**
 * library class for msocial social_interaction
 *
 * @package msocialconnector_twitter
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_msocial\connector;

defined('MOODLE_INTERNAL') || die();

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
     * Interaction source.
     * @var string
     */
    const DIRECTION_AUTHOR = 'fromid';

    const DIRECTION_RECIPIENT = 'toid';

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

    public $score;

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
    static public function build($record) {
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

    /**
     *
     * @param unknown $msocialid
     * @param unknown $conditions
     * @param unknown $fromdate
     * @param unknown $todate
     * @param array(int) $users list of moodle identifiers
     * @return \mod_msocial\connector\social_interaction[]
     */
    static public function load_interactions($msocialid, $conditions = null, $fromdate = null, $todate = null, $users = null) {
        global $DB;
        $interactions = [];
        $select = "msocial=?";
        $params[] = $msocialid;
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
        if ($users) {
            list($inwhere, $paramsin) = $DB->get_in_or_equal($users);
            $select .= " AND ( fromid $inwhere OR toid $inwhere) ";
            $params = array_merge($params, $paramsin, $paramsin);
        }
        $records = $DB->get_records_select('msocial_interactions', $select, $params, 'timestamp');
        foreach ($records as $record) {
            $interactions[] = self::build($record);
        }
        return $interactions;
    }

    /**
     * Save the list of interactions in the database.
     *
     * @param array $interactions
     * @param int $msocialid
     */
    static public function store_interactions(array $interactions, $msocialid) {
        global $DB;

        $uids = array_map(function ($inter) {
            return $inter->uid;
        }, $interactions);
        if (count($uids) == 0) {
            return;
        }
        list($whereuids, $params) = $DB->get_in_or_equal($uids);
        $where = "uid $whereuids and msocial=?";
        $params[] = $msocialid;
        $DB->delete_records_select('msocial_interactions', $where, $params);
        $fields = ['uid', 'msocial', 'fromid', 'nativefrom', 'nativefromname', 'toid', 'nativeto', 'nativetoname',
                        'parentinteraction', 'source', 'timestamp', 'type', 'nativetype', 'description', 'rawdata'];
        $records = [];
        foreach ($interactions as $inter) {
            $record = new \stdClass();
            foreach ($fields as $field) {
                $record->$field = isset($inter->$field) ? $inter->$field : null;
            }
            if ($record->timestamp) {
                $record->timestamp = $inter->timestamp->getTimeStamp();
            }
            $record->msocial = $msocialid;
            $records[] = $record;
        }
        foreach ($records as $record) {
            $DB->insert_record('msocial_interactions', $record);
        }
        // $DB->insert_records('msocial_interactions', $records);
    }
}
