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

/** library class for msocial social_interaction
 *
 * @package msocialconnector
 * @copyright 2017 Juan Pablo de Castro {@email jpdecastro@tel.uva.es}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
namespace mod_msocial\connector;

defined('MOODLE_INTERNAL') || die();

class social_interaction {

    /** Message published initially, with to recipient.
     *
     * @var string */
    const POST = 'post';

    /** TODO: To be defined.
     *
     * @var string */
    const MESSAGE = 'message';

    /** Message published in response to other.
     *
     * @var string */
    const REPLY = 'reply';

    /** Mention to an user inside a message.
     *
     * @var string */
    const MENTION = 'mention';

    /** Mark on a message indicating attention.
     *
     * @var string */
    const REACTION = 'reaction';

    /** Interaction source.
     * @var string */
    const DIRECTION_AUTHOR = 'fromid';
    const DIRECTION_RECIPIENT = 'toid';

    /** Name of the subplugin that generated this
     *
     * @var string */
    public $source;
    public $icon;

    /** Unique identifier of this interaction.
     *
     * @var string */
    public $uid;

    /** moodle userid
     *
     * @var string */
    public $fromid;

    /** Social network native userid.
     *
     * @var string */
    public $nativefrom;

    /** Social network native username.
     *
     * @var string */
    public $nativefromname;

    /**
     * @var string moodle userid */
    public $toid;

    /**
     * @var string social network userid */
    public $nativeto;

    /** Social network native username.
     *
     * @var string */
    public $nativetoname;

    /** social_interaction uid that originated this interaction.
     *
     * @var social_interaction */
    public $parentinteraction;

    /**
     * @var \DateTime time of creation of the interaction */
    public $timestamp;

    /** Type of interaction: post, reply, share, etc.
     *
     * @var string */
    public $type;

    /** Type of the interacion as defined by the social service.
     *
     * @var string */
    public $nativetype;
    public $description;
    public $shortdescription;
    public $score;

    /** native JSON representation of the item
     *
     * @var string */
    public $rawdata;

    /** Construct an instance from a database record
     *
     * @param unknown $record */
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
        // POSTs are modelled as a self-interaction. TODO: Evaluate this.
        if ($inter->type == self::POST) {
            $inter->toid = $inter->fromid;

            $inter->nativeto = $inter->nativefrom;
            $inter->nativetoname = $inter->nativefromname;
        }
        return $inter;
    }
    static public function load_interactions_filter(\filter_interactions $filter) {
        global $DB;
        $interactions = [];

        list($select, $params) = $filter->get_sqlquery();
        $records = $DB->get_records_select('msocial_interactions', $select, $params, 'timestamp');
        foreach ($records as $record) {
            $interactions[] = self::build($record);
        }
        return $interactions;
    }
    /**
     * @param unknown $msocialid
     * @param unknown $conditions
     * @param unknown $fromdate
     * @param unknown $todate
     * @param array(int) $users list of moodle identifiers
     * @return \mod_msocial\connector\social_interaction[]
     * @deprecated
     */
    static public function load_interactions($msocial, $conditions = null, $fromdate = null, $todate = null, $users = null) {
        $filter = new \filter_interactions(['fromdate' => $fromdate, 'todate' => $todate ], $msocial);
        $filter->set_users($users);
        return self::load_interactions_filter($filter);
    }

    /** Save the list of interactions in the database.
     *
     * @param array $interactions
     * @param int $msocialid */
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
            // For databases that doesn't support 4bytes unicode chars.
            $record->description = self::clean_emojis($record->description);

            $record->msocial = $msocialid;
            $records[] = $record;
        }
        $tr = $DB->start_delegated_transaction();
        try {
            $DB->delete_records_select('msocial_interactions', $where, $params);
            $DB->insert_records('msocial_interactions', $records);
            $tr->allow_commit();
        } catch (\Exception $e) {
            mtrace("Error " . $e->getMessage() . " uids: <p>" . implode(', ', $uids) . " <p> keys:" . implode(', ', array_keys($interactions)));
            $tr->rollback($e);
        }
    }
    /**
     * Remove emojis in utf8mb4 format.
     * https://stackoverflow.com/questions/12807176/php-writing-a-simple-removeemoji-function
     * @param string $text
     * @return NULL|string the filtered text.
     */
    static private function clean_emojis($text) {
        if ($text == null) {
            return null;
        } else {
            $utftext = mb_convert_encoding($text, "UTF-8");

            $regexemoticons = '/[\x{1F000}-\x{FFFFF}]/u';
            $cleantext = preg_replace($regexemoticons, '', $utftext);
            return $cleantext;
        }
    }
}
