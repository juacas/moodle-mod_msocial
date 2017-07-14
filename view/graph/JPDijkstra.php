<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use Fhaculty\Graph\Set\Edges;
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
        $this->edges=parent::getEdges();
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
