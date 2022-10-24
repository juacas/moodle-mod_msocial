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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die;
/**
 * Class for integration into report_editdates.
 */
class mod_msocial_report_editdates_integration extends report_editdates_mod_date_extractor {
   
    public function __construct($course) {
        parent::__construct($course, 'msocial');
        parent::load_data();        
    }
        
    public function get_settings(cm_info $cm) {
        $msocial = $this->mods[$cm->instance];

        return array(
                'startdate' => new report_editdates_date_setting(
                        get_string('startdate', 'msocial'),
                        $msocial->startdate,
                        self::DATETIME, true),
                'enddate' => new report_editdates_date_setting(
                        get_string('enddate', 'msocial'),
                        $msocial->enddate,
                        self::DATETIME, true)
                );
    }

    public function validate_dates(cm_info $cm, array $dates) {
        $errors = array();
        if ($dates['startdate'] && $dates['enddate']
                && $dates['enddate'] < $dates['startdate']) {
            $errors['duedate'] = get_string('enddate_error', 'msocial');
        }
        return $errors;
    }

    public function save_dates(cm_info $cm, array $dates) {
        global $DB, $COURSE;

        $update = new stdClass();
        $update->id = $cm->instance;
        $update->enddate = $dates['enddate'];
        $update->startdate = $dates['startdate'];

        $result = $DB->update_record('msocial', $update);


        // Update gradebook
        $msocial = $this->mods[$cm->instance];
        // Update the calendar and grades.
        msocial_update_events($msocial);
        msocial_update_grades($msocial);
    }

}