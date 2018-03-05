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
defined('MOODLE_INTERNAL') || die();

class tag_parser {
    protected $searchstruct = [];
    /**
     * Parse hashtag search string.
     * According twitter https://twitter.com/search-advanced a list of terms are ANDed, OR creates takes precedence to the left.
     * @param unknown $hashtagexpr
     */
    public function __construct($hashtagexpr) {
        if ($hashtagexpr == '*') {
            $this->searchstruct[] = '*';
            return;
        }
        preg_match_all("/(\".+\"|\S+)/", $hashtagexpr, $terms);
        $terms = $terms[0];
        $this->searchstruct = [];
        $orexpression = [];
        for ($i = 0; $i < count($terms); $i++) {
            $term = $terms[$i];
            $nextterm = isset($terms[$i + 1]) ? $terms[$i + 1] : null;
            if ($nextterm == 'OR') {
                // Start or add ORed expression.
                $orexpression[] = $term;
                $i++;
                continue;
            }
            // Building OR expression.
            if ( ($nextterm != 'OR' || $nextterm == 'AND') && count($orexpression) > 0) {
                // Finish OR clause.
                $orexpression[] = $term;
                $this->searchstruct[] = $orexpression;
                $orexpression = [];
                continue;
            }

            if ($term != 'AND') {
                // Trim quotes.
                $term = trim($term);
                $term = trim($term, '"');
                // Add ANDed expression.
                $this->searchstruct[] = $term;
            }
        }
    }
    /**
     * Check filter condition. Only a list of AND tags
     * TODO: implement more conditions.
     * @param unknown $text to search into
     */
    public function check_hashtaglist($text) {
        foreach ($this->searchstruct as $condition) {
            if (!$this->check_single_condition($text, $condition)) {
                return false;
            }
        }
        return true;
    }
    protected function check_single_condition($text, $condition) {
        if (is_array($condition)) {
            // ORed expression.
            return $this->check_ored_conditions($text, $condition);
        } else if ($condition == '*') {
            return true;
        } else if (strpos($condition, '-') === 0) {
            // Negative condition.
            return strpos($text, substr($condition, 1)) !== false;
        } else {
            return strpos($text, $condition) !== false;
        }
        return false;
    }
    protected function check_ored_conditions($status, $conditions) {
        foreach ($conditions as $condition) {
            if ($this->check_single_condition($status, $condition)) {
                return true;
            }
        }
        return true;
    }
}