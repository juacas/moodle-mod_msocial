<?php
// This file is part of MSocial activity for Moodle http://moodle.org/
//
// Questournament for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Questournament for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
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
        $this->edges = parent::getEdges();
    }

    /**
     * get all edges on shortest path for this vertex
     *
     * @return Edges
     * @throws UnexpectedValueException when encountering an Edge with negative weight
     */
    public function getEdges() {
        return $this->edges;
    }
}
