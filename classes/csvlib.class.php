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
/* ***************************
 * Module developed at the University of Valladolid
 * Designed and directed by Juan Pablo de Castro at telecommunication engineering school
 * Copyright 2017 onwards EdUVaLab http://www.eduvalab.uva.es
 * @author Juan Pablo de Castro
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package msocial
 * *******************************************************************************
 */
class CSVWorkbook {
    protected $currentrow = [];
    protected $row = 0;
    protected $separator = ',';
    public function __construct($separator) {
        $this->separator = $separator;
    }
    public function send($filename) {
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");
    }
    public function add_worksheet($name) {
        return $this;
    }
    public function write_string($row, $col, $string) {
        if ($row != $this->row) {
            $this->flush_row($this->row);
            $this->row = $row;
        }
        $this->currentrow[$col] = '"' . str_replace('"', '""', $string) . '"';
    }
    private function flush_row($rownum) {
        $row = implode($this->separator, $this->currentrow) . "\n";
        echo $row;
        $this->currentrow = [];
    }
    public function close() {
        $this->flush_row($this->row);
    }
}