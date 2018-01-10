<?php

namespace Graphp\Algorithms;

use Graphp\Algorithms\BaseGraph;
use Fhaculty\Graph\Exception\UnexpectedValueException;

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Edge\Base as Edge;
use Fhaculty\Graph\Edge\Directed as EdgeDirected;

class SimpleGraph extends Parallel
{

    /**
     * create simple graph
     *
     * @return Graph
     * @uses Graph::createGraphCloneEdgeless()
     * @uses Graph::createEdgeClone()
     * @uses Graph::createEdgeCloneInverted()
     */
    public function createGraph()
    {
        $newgraph = $this->graph->createGraphCloneEdgeless();

        foreach ($this->graph->getEdges() as $edge) {
            $newedge = $newgraph->createEdgeClone($edge);
            $this->mergeParallelEdges($newedge);
        }
        return $newgraph;
    }

    /**
     * Will merge all edges that are parallel to to given edge
     *
     * @param Edge $newedge
     */
    private function mergeParallelEdges(Edge $newedge) {
        $paralleledges = $this->getEdgesParallelEdge($newedge);
        if ($paralleledges && $paralleledges->count() > 0) {
            $minweight = $newedge->getWeight();
            $mergedcapacity = $newedge->getCapacity();
            $mergedflow = $newedge->getFlow();
            foreach ($paralleledges as $paralleledge) {
                $mergedcapacity += $paralleledge->getCapacity();
                $mergedflow += $paralleledge->getFlow();
                $minweight = min($minweight, $paralleledge->getWeight());
            }
            $newedge->setCapacity($mergedcapacity);
            $newedge->setFlow($mergedflow);
            $newedge->setWeight($minweight);
            foreach ($paralleledges as $paralleledge) {
                $paralleledge->destroy();
            }
        }
    }
}
