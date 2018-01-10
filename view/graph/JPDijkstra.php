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
use Fhaculty\Graph\Set\Edges;
defined('MOODLE_INTERNAL') || die();
/**
 * Description of JPDijkstra
 *
 * @author juacas
 */
class JPDijkstra extends \Graphp\Algorithms\ShortestPath\Dijkstra {

    /**
     *
     * @var Edges
     */
    protected $edges;

    public function __construct(\Fhaculty\Graph\Vertex $vertex) {
        parent::__construct($vertex);
        parent::calculate();
    }

    public function getDistanceMap()
    {
        $ret = array();
        foreach ($this->vertex->getGraph()->getVertices()->getMap() as $vid => $vertex) {
            try {
                if (isset($this->totalCostOfCheapestPathTo[$vid])) {
                    $ret[$vid] = $this->totalCostOfCheapestPathTo[$vid];
                }
            } catch (OutOfBoundsException $ignore) {
            } // ignore vertices that can not be reached
        }

        return $ret;
    }
    /**
     * get all edges on shortest path for this vertex
     *
     * @return Edges
     * @throws UnexpectedValueException when encountering an Edge with negative weight
     */
    public function getEdges()
    {
        if ($this->edges == null) {
            $this->edges = parent::getEdges();
        }
        // algorithm is done, return resulting edges
        return $this->edges;
    }
}
