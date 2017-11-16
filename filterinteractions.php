<?php
use mod_msocial\connector\social_interaction;
use mod_msocial\plugininfo\msocialconnector;

// This file is part of MSocial activity for Moodle http://moodle.org/
//
// MSocial for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// MSocial for Moodle is distributed in the hope that it will be useful,
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
defined('MOODLE_INTERNAL') || die();

class filter_interactions {
    const INFINITY_DATE  = 8640000000000000;
    const PARAM_INTERACTION_POST = 'interaction_post';
    const PARAM_INTERACTION_REPLY = 'interaction_reply';
    const PARAM_INTERACTION_REACTION = 'interaction_reaction';
    const PARAM_INTERACTION_MENTION = 'interaction_mention';
    const PARAM_RECEIVED_BY_TEACHERS = 'hideteachers';
    const PARAM_UNKNOWN_USERS = 'unknownusers';
    const PARAM_PURE_EXTERNAL = 'external';
    const PARAM_INTERACTIONS = 'interactions';
    const PARAM_DATERANGE = 'daterange';

    const PARAM_SOURCES = 'sources';
    const PARAM_STARTDATE = 'startdate';
    const PARAM_ENDDATE = 'enddate';

    public $msocial;
    public $users_struct = null;
    protected $sources = [];
    protected $interactions = [];
    protected $extraparams = [];
    public $startdate = 0;
    public $enddate = 0;
    public $receivedbyteachers = false;
    public $unknownusers = false;
    public $pureexternal = false;

    public function __construct(array $formparams, $msocial) {
        $this->msocial = $msocial;
        // Default dates.
        $this->startdate = $this->msocial->startdate;
        $this->enddate = $this->msocial->enddate;
        $rangepresent = false;
        foreach ($formparams as $name => $param) {
            // Sources.
            if ($name == self::PARAM_INTERACTION_POST) {
                $this->interactions[] = social_interaction::POST;
            } else if ($name == self::PARAM_INTERACTION_REPLY) {
                $this->interactions[] = social_interaction::REPLY;
            } else if ($name == self::PARAM_INTERACTION_REACTION) {
                $this->interactions[] = social_interaction::REACTION;
            } else if ($name == self::PARAM_INTERACTION_MENTION) {
                $this->interactions[] = social_interaction::MENTION;
            } else if ($name == self::PARAM_RECEIVED_BY_TEACHERS) {
                $this->receivedbyteachers = ($param == 'true' || $param == 'on' || $param == '1' || $param === true);
            } else if ($name == self::PARAM_UNKNOWN_USERS) {
                $this->unknownusers = ($param == 'true' || $param == 'on' || $param == '1' || $param === true);
            } else if ($name == self::PARAM_PURE_EXTERNAL) {
                $this->pureexternal = ($param == 'true' || $param == 'on' || $param == '1' || $param === true);
            } else if (substr($name, 0, 7) == 'source_') {
                $this->sources[] = substr($name, 7);
            } else if ($name == 'sources') {
                if ($param) {
                    $this->sources = explode(',', $param);
                }
            } else if ($name == self::PARAM_INTERACTIONS) {
                if ($param) {
                    $this->interactions = explode(',', $param);
                }
            } else if ($name == 'startdate' && !$rangepresent) {
                    $this->startdate = (int) $param;
            } else if ($name == 'enddate') {
                    $this->enddate = (int) $param;
            } else if ($name == self::PARAM_DATERANGE && !$rangepresent) {
                $dates = json_decode($param);
                if ($dates) {
                    $rangepresent = true;
                    $startdate = new DateTime($dates->start);
                    $enddate = new DateTime($dates->end);
                    $this->startdate = $startdate->getTimestamp();
                    $this->enddate = $enddate->getTimestamp();
                }
            } else {
                $this->extraparams[$name] = $param;
            }
        }
    }
    public function param_if_absent($name, $default) {
        if (!array_key_exists($name, $this->extraparams)) {
            $this->extraparams[$name] = $default;
        }
    }
    /**
     *
     * @param object[] $users struct obtained from msocial_get_users_by_type
     */
    public function set_users($users) {
        $this->users_struct = $users;
    }

    public function get_checked_interaction($type) {
        if (count($this->interactions) == 0 ) {
            return true;
        } else {
            return (array_search($type, $this->interactions) !== false);
        }
    }
    public function get_checked_source($source) {
        if (count($this->sources) == 0 ) {
            return true;
        } else {
            return (array_search($source, $this->sources) !== false);
        }
    }
    public function get_checked_receivedbyteachers() {
        return $this->receivedbyteachers;
    }
    public function get_extra_params() {
        return $this->extraparams;
    }
    public function render_form(moodle_url $targeturl, $plugins = null) {
        if ($plugins == null) {
            $plugins = msocialconnector::get_enabled_connector_plugins($this->msocial);
        }

        $out = '';
        $out .= '<form id="msocialfilter" action="' . $targeturl->out_omit_querystring() . '" method="GET">';
        $extraparams = $this->get_extra_params();
        foreach ($extraparams as $paramname => $paramvalue) {
            $out .= "<input type=\"hidden\" name=\"$paramname\" value=\"$paramvalue\"/>\n";
        }
        // Students only.
        $out .= "<b>" . get_string('interactionstoshow', 'msocial') . "</b>";

        $checked = $this->get_checked_receivedbyteachers() ? 'checked' : '';
        $out .= "<input type=\"checkbox\" name=\"". self::PARAM_RECEIVED_BY_TEACHERS . "\" $checked value=\"true\">" .
                get_string("receivedbyteacher", "msocial") . "</input> ";
        $checked = $this->unknownusers ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_UNKNOWN_USERS ."\" $checked value=\"true\">" .
                get_string("unknownusers", "msocial") . "</input> ";
        $checked = $this->pureexternal ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_PURE_EXTERNAL ."\" $checked value=\"true\">" .
        get_string("pureexternal", "msocial") . "</input> ";
        $checked = $this->get_checked_interaction(social_interaction::POST) ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_INTERACTION_POST . "\" $checked value=\"true\">" .
                get_string("posts", "msocial") . "</input> ";
        $checked = $this->get_checked_interaction(social_interaction::REPLY) ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_INTERACTION_REPLY . "\" $checked value=\"true\">" .
                get_string('replies', 'msocial') . "</input> ";
        $checked = $this->get_checked_interaction(social_interaction::REACTION) ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_INTERACTION_REACTION . "\" $checked value=\"true\">" .
                get_string('reactions', 'msocial') . "</input> ";
        $checked = $this->get_checked_interaction(social_interaction::MENTION) ? 'checked="checked"' : '';
        $out .= "<input type=\"checkbox\" name=\"" . self::PARAM_INTERACTION_MENTION . "\" $checked value=\"true\">" .
                get_string('mentions', 'msocial') . "</input> ";
        $out .= " <br/> <b>". get_string('socialnetworktoshow', 'msocial') . "</b>";
        foreach ($plugins as $plugin) {
            $sourcename = $plugin->get_subtype();
            $checked = $this->get_checked_source($sourcename) ? 'checked="checked"' : '';
            $out .= "<input type=\"checkbox\" name=\"source_$sourcename\" $checked >$sourcename</input> ";
        }
        $out .= ' <div><b>' . get_string('datesrange', 'msocial') . '</b>:';
        $out .= '<input id="daterange" type="text" class="daterange" name="daterange" />';
        $out .= " <input type=\"submit\"></div>";
        $out .= '</form>';
        $today = get_string('today');
        $initialtext = 'Selecciona un rango';
        $yesterday = get_string('yesterday', 'msocial');
        $last7days = get_string('last7days', 'msocial');
        $lastweekmosu = get_string('lastweekmosu', 'msocial');
        $monthtodate = get_string('monthtodate', 'msocial');
        $prevmonth = get_string('prevmonth', 'msocial');
        $yeartodate = get_string('yeartodate', 'msocial');
        $activitystart = get_string('fromactivitystart', 'msocial');
        $startdate = $this->startdate ? $this->startdate : -self::INFINITY_DATE / 1000;
        $enddate = $this->enddate ? $this->enddate : self::INFINITY_DATE / 1000;
        $activitystartdate = $this->msocial->startdate ? $this->msocial->startdate : -self::INFINITY_DATE / 1000;
        $activityenddate = $this->msocial->enddate ? $this->msocial->enddate : self::INFINITY_DATE / 1000;
        $apply = get_string('ok');
        $clear = get_string('clear');
        $cancel = get_string('cancel');
        $out .= <<< SCRIPT

<script>
var options= {
			// presetRanges: array of objects; each object describes an item in the presets menu
			// and must have the properties: text, dateStart, dateEnd.
			// dateStart, dateEnd are functions returning a moment object
			presetRanges: [
			{text: '$activitystart',    dateStart: function() { return moment.unix($activitystartdate);},
                                        dateEnd: function() { return moment.unix($activityenddate); } },
				{text: '$today', dateStart: function() { return moment() }, dateEnd: function() { return moment() } },
				{text: '$yesterday', dateStart: function() { return moment().subtract('days', 1) },
                                    dateEnd: function() { return moment().subtract('days', 1) } },
				{text: '$last7days', dateStart: function() { return moment().subtract('days', 6) },
                                    dateEnd: function() { return moment() } },
				{text: '$lastweekmosu', dateStart: function() { return moment().subtract('days', 7).isoWeekday(1) },
                                    dateEnd: function() { return moment().subtract('days', 7).isoWeekday(7) } },
				{text: '$monthtodate', dateStart: function() { return moment().startOf('month') },
                                        dateEnd: function() { return moment() } },
				{text: '$prevmonth', dateStart: function() { return moment().subtract('month', 1).startOf('month') },
                                    dateEnd: function() { return moment().subtract('month', 1).endOf('month') } },
				{text: '$yeartodate', dateStart: function() { return moment().startOf('year') },
                                        dateEnd: function() { return moment() } }
			],
			initialText: '$initialtext', // placeholder text - shown when nothing is selected
			icon: 'ui-icon-triangle-1-s',
			applyButtonText: '$apply', // use '' to get rid of the button
			clearButtonText: 'Clear', // use '' to get rid of the button
			cancelButtonText: 'Cancel', // use '' to get rid of the button
			rangeSplitter: ' - ', // string to use between dates
// 			dateFormat: 'd M yy', // displayed date format. Available formats: http://api.jqueryui.com/datepicker/#utility-formatDate
			altFormat: 'yy-mm-dd', // submitted date format - inside JSON {"start":"...","end":"..."}
			verticalOffset: 0, // offset of the dropdown relative to the closest edge of the trigger button
			mirrorOnCollision: true, // reverse layout when there is not enough space on the right
			autoFitCalendars: true, // override datepicker's numberOfMonths option in order to fit widget width
			applyOnMenuSelect: true, // whether to auto apply menu selections

			datepickerOptions: { // object containing datepicker options. See http://api.jqueryui.com/datepicker/#options
				numberOfMonths: 2,
//				showCurrentAtPos: 1 // bug; use maxDate instead
				maxDate: 0 // the maximum selectable date is today (also current month is displayed on the last position)
			}
    };
$("#daterange").daterangepicker(options);
var range = {};
range.start = new Date($startdate * 1000);
range.end = new Date($enddate* 1000);

$("#daterange").daterangepicker("setRange", range);
</script>
SCRIPT;
        return $out;
    }
    public function get_filter_params_url() {
        $paramlist = $this->get_filter_params();
        $paramsurl = array_map(function ($param) {
            return is_array($param) ? implode(',', $param) : $param;
        }, $paramlist);
        return $paramsurl;
    }
    public function get_filter_params() {
        $params = [
                        self::PARAM_INTERACTIONS => $this->interactions,
                        self::PARAM_RECEIVED_BY_TEACHERS => $this->receivedbyteachers,
                        self::PARAM_UNKNOWN_USERS => $this->unknownusers,
                        self::PARAM_PURE_EXTERNAL => $this->pureexternal,
                        self::PARAM_SOURCES => $this->sources,
                        self::PARAM_STARTDATE => $this->startdate,
                        self::PARAM_ENDDATE => $this->enddate,
        ];
        return $params;
    }
    public function get_interactions_query() {
        if (count($this->interactions) == 0) {
            $sql = null;
        } else {
            $sql = "type = '". implode("' OR type = '", $this->interactions) . "'";
        }
        return $sql;
    }
    public function get_sources_query() {
        if (count($this->sources) == 0) {
            $sql = null;
        } else {
            $sql = "source = '". implode("' OR source = '", $this->sources) . "'";
        }
        return $sql;
    }
    public function get_sqlquery() {
        $andedqueries = [];
        $sourcesquery = $this->get_sources_query();
        $interactionsquery = $this->get_interactions_query();
        if ($sourcesquery != null) {
            $andedqueries[] = '(' . $sourcesquery . ')';
        }
        if ($interactionsquery != null) {
            $andedqueries[] = '(' . $interactionsquery . ')';
        }
        $select = "msocial=?";
        $params[] = $this->msocial->id;
        $andedqueries[] = $select;
        $userquery = [];
        if (!$this->receivedbyteachers) {
            if ($this->users_struct == null) { // Select only students.
                $contextcourse = \context_course::instance($this->msocial->course);
                $this->users_struct = msocial_get_users_by_type($contextcourse);
            }
            $filterusers = $this->users_struct['student_ids'];
        } else {
            $filterusers = null;
        }
        if ($filterusers) {
            global $DB;
            list($inwhere, $paramsin) = $DB->get_in_or_equal($filterusers);
            $userquery[] = "( fromid $inwhere OR toid $inwhere)";
            $params = array_merge($params, $paramsin, $paramsin);
        }
        if ($filterusers && $this->pureexternal) { // Include ORed pure external interactions.
            $userquery[] = '(fromid IS NULL AND toid IS NULL)';
        }
        if ($filterusers && $this->unknownusers) { // Include ORed unknown users' interactions.
            $userquery[] = 'fromid IS NULL';
            $userquery[] = 'toid IS NULL';
        }
        if (count($userquery) > 0) {
            $andedqueries[]  = '(' . implode(' OR ', $userquery) . ')';
        }
        if (!$this->pureexternal) { // Exclude pure external interactions.
            $andedqueries[] = 'NOT (fromid IS NULL AND toid IS NULL)';
        }
        if (!$this->unknownusers) { // Exclude unknown users' interactions.
            $andedqueries[] = 'NOT (fromid IS NULL OR toid IS NULL)';
        }
        if ($this->startdate && $this->startdate != 0) {
            $andedqueries[] = "(timestamp >= ? OR timestamp IS NULL)"; // TODO: Format date.
            $params[] = $this->startdate;
        }
        if ($this->enddate && $this->enddate != 0) {
            $andedqueries[] = "(timestamp <= ? OR timestamp IS NULL)"; // TODO: Format date.
            $params[] = $this->enddate;
        }
        $query = implode(' AND ', $andedqueries);

        return [$query, $params];
    }
}