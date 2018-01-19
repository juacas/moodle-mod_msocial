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
     * @return \mod_msocial\connector\social_interaction[] */
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
            // For databases that doesn't support 4bytes unicode chars.
            $record->description = self::clean_emojis($record->description);

            $record->msocial = $msocialid;
            $records[] = $record;
        }
        $DB->insert_records('msocial_interactions', $records);
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
            $regexemoticons = '/[\x{1F600}-\x{1F64F}]/u';
            $utftext = mb_convert_encoding($text, "UTF-8");
            $cleantext = preg_replace($regexemoticons, '', $utftext);
            // Match Miscellaneous Symbols and Pictographs
            $regexsymbols = '/[\x{1F300}-\x{1F5FF}]/u';
            $cleantext = preg_replace($regexsymbols, '', $cleantext);

            // Match Transport And Map Symbols
            $regextransport = '/[\x{1F680}-\x{1F6FF}]/u';
            $cleantext = preg_replace($regextransport, '', $cleantext);
            $cleantext = preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $cleantext);

            return $cleantext;
        }
    }
}
