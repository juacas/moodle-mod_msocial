<?php
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
namespace mod_msocial\view\graph;

use mod_msocial\connector\msocial_connector_plugin;
use mod_msocial\view\msocial_view_graph;
defined('MOODLE_INTERNAL') || die();

class graph_task extends \core\task\adhoc_task {
    public function execute() {
        $data = $this->get_custom_data();
        $msocial = $data->msocial;
        $users = (array) $data->users;
        $usersreindex = [];
        foreach ($users as $key => $val) {
            $usersreindex[(int)$key] = $val;
        }
        $plugin = new msocial_view_graph($msocial);
        $kpis = $plugin->calculate_kpis($usersreindex);
        $plugin->store_kpis($kpis, true);
        $plugin->set_config(msocial_connector_plugin::LAST_HARVEST_TIME, time());
    }
    public function set_custom_data($customdata) {
        $this->set_custom_data_as_string(json_encode($customdata, 4));
    }
    public function get_custom_data() {
        return json_decode($this->get_custom_data_as_string());
    }
}