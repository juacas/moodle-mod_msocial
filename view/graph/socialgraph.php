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
defined('MOODLE_INTERNAL') || die();

require_once('vendor/autoload.php');
require_once('JPDijkstra.php');

use Fhaculty\Graph\Graph;
use Fhaculty\Graph\Exception\OutOfBoundsException;
use Graphp\Algorithms\BidirectionalGraph;
use Graphp\Algorithms\Degree;
use Graphp\Algorithms\SimpleGraph;
use mod_msocial\connector\social_interaction;
use Fhaculty\Graph\Vertex;

class SocialMatrix {
    private $adjacencymatrix = [];

    /**
     * @var Graph $graph */
    private $graph;

    public function __construct() {
        $this->graph = new Graph();
        $this->notdirected_graph = new Graph();
    }

    /** Devuelve el grafico
     * @return \Fhaculty\Graph\Graph */
    public function get_graph() {
        return $this->graph;
    }

    /** Crea un grafico a partir de un array de miembros
     * @param array $membersarray */
    public function generate_subgraph($membersarray) {

        // Defino un grafico nuevo.
        $compactgraph = new Graph();
        // Crea los vertices.
        foreach ($membersarray as $value => $key) {
            $compactgraph->createVertex($value);
        }
        // Creo los arcos entre los vertices con el peso que indica la matriz de adyacencia.
        foreach ($membersarray as $value1 => $key1) {
            foreach ($membersarray as $value2 => $key2) {
                if (isset($this->adjacencymatrix[$value1][$value2])) {
                    $fromvertex = $compactgraph->getVertex($value2);
                    $tovertex = $compactgraph->getVertex($value1);
                    $fromvertex->createEdgeTo($tovertex)->setWeight($this->adjacencymatrix[$value1][$value2]);
                }
            }
        }
        return $compactgraph;
    }

    /**
     * @param social_interaction $interaction
     * @param string[] $edgeattrs Edge attrs array.
     * @param DateTime $date
     * @return Vertex $from, Edge $edge, Vertex $to */
    public function register_interaction(social_interaction $interaction, $edgeattrs = [], $fromattrs = [], $toattrs = [], $date = null) {
        $from = $interaction->fromid ? $interaction->fromid : $interaction->nativefrom;
        $to = $interaction->toid ? $interaction->toid : $interaction->nativeto;
        $type = $interaction->type;

        $score = $this->get_score($interaction);
        $weight = 1 / $score; // the distance is closer for more relevant interactions.
        if ($to == null) {
            $to = 'Community';
        }
        $edge = null;
        // if ($from != null && $to != null && $to != $from && $type != social_interaction::POST) {
        // if ($from != null && $to != null && $to != $from) {
        if ($from != null && $to != null) {

            if (!isset($this->adjacencymatrix[$to][$from])) {
                $this->adjacencymatrix[$to][$from] = $weight;
            } else {
                $this->adjacencymatrix[$to][$from] += $weight;
            }
            if (!$this->graph->hasVertex($from)) {
                $fromvertex = $this->graph->createVertex($from);
                $fromvertex->getAttributeBag()->setAttributes($fromattrs);
            } else {
                $fromvertex = $this->graph->getVertex($from);
            }
            if (!$this->graph->hasVertex($to)) {
                $tovertex = $this->graph->createVertex($to);
                $tovertex->getAttributeBag()->setAttributes($toattrs);
            } else {
                $tovertex = $this->graph->getVertex($to);
            }

            // Add directional relation from author to recipient.
            $edge = $fromvertex->createEdgeTo($tovertex);
            $edge->setAttribute('type', $type);
            $edge->setWeight($weight);
            $edge->getAttributeBag()->setAttributes($edgeattrs);
        }

        // Add score to author.
        if (isset($fromvertex)) {
            $fromvertex->setAttribute('score', $score + $fromvertex->getAttribute('score', 0));
        } else {
            $fromvertex = null;
        }
        if (isset($tovertex)) {
            // Add score to recipient.
            $tovertex->setAttribute('score', $score + $tovertex->getAttribute('score', 0));
        } else {
            $tovertex = null;
        }
        return [$fromvertex, $edge, $tovertex];
    }

    /** Calcula la suma de pesos por el camino m??s corto de un miembro al resto de miembros
     * a los que est?? conectado (cercania), y cuantas veces van apareciendo los "vertex" en el medio
     * de esos caminos (proximidad).
     * @param \stdClass[] $users records of the users that will be calculated
     * @return \stdClass $results */
    public function calculate_centralities($users) {
        require_once('BidirectionalGraph.php');
        require_once('SimpleGraph.php');

        // Invert graph.
//         $transposer = new TransposeGraph($this->graph);
//         $transposedgraph = $transposer->createGraph();
        // Merge parallel edges.
        $timestamp = microtime(true);
        $simplifier = new SimpleGraph($this->graph);
        $simplegraph = $simplifier->createGraph();
        mtrace('<li>Graph simplified ' . $this->graph->getVertices()->count() .
                ' vertices. From '. $this->graph->getEdges()->count() .
                ' to '. $simplegraph->getEdges()->count(). ' edges. In' . round(microtime(true) - $timestamp, 4) . ' secs.' );
        $timestamp = microtime(true);
//         $bidirectionalgraph = (new BidirectionalGraph($this->graph))->createGraph(false);
        $bidirectionalgraph = (new BidirectionalGraph($simplegraph))->createGraph(false);
        mtrace('<li>Graph undirected: '. $bidirectionalgraph->getEdges()->count(). ' edges. In' . round(microtime(true) - $timestamp, 4) . ' secs.' );

        $results = [];
        $analysisgraph = $bidirectionalgraph;
        // $communityvertex = $analysisgraph->getVertex('Community');
        // $vertices = $analysisgraph->getVertices()->getMap();
        // unset($vertices['Community']);
        // $vertices = new Vertices($vertices);
        // $analysisgraph = $analysisgraph->createGraphCloneVertices($vertices);
        $vertices = $analysisgraph->getVertices();
        // For centralities all links are weighted 1.
        $edges = $analysisgraph->getEdges();
        foreach ($edges as $edge) {
            $edge->setWeight(1);
        }
        foreach ($vertices as $vertex) {
            $id = $vertex->getId();
            if (!isset($users[$id])) {
                continue;
            }
            // Obtiene el camino mas corto a cada uno de los vertex que esta conectado el miembro.
            $timestamp = microtime(true);
            $sp = new JPDijkstra($vertex);
            mtrace('<li>Calculated shortest paths for ' . $users[$id]->id . ' in ' . round(microtime(true) - $timestamp, 4) . ' secs.' );

            // Array que contiene como clave los "ids" y como valor el "peso" total (por el camino
            // mas corto)
            // a cada uno de los "Vertex" que est?? conectado.
            $timestamp = microtime(true);
            $dmap = $sp->getDistanceMap();
            mtrace('<li>Calculated distance map for ' . fullname($users[$id]) . ' in ' .   round(microtime(true) - $timestamp, 4) . ' secs.' );

            // Calculo de la suma de todos los caminos a todos los vertices que est?? conectado.
            $timestamp = microtime(true);
            $indcercania = $this->centralidad_cercania($dmap, $id);
            mtrace('<li>Calculated nearness for ' . fullname($users[$id]) .' in ' .   round(microtime(true) - $timestamp, 4) . ' secs.' );
            if (!isset($results[$id])) {
                $results[$id] = new stdClass();
            }
            $results[$id]->cercania = $indcercania;
            // Calculo de todos los "vertex" que est??n entre medias de los caminos mas cortos.
            $timestamp = microtime(true);
            $intermediacionparcial = $this->centralidad_intermediacion($analysisgraph, $dmap, $sp, $id);
            mtrace('<li>Calculated betweeness for ' . fullname($users[$id]) . ' in ' .  round(microtime(true) - $timestamp, 4) . ' secs.' );

            foreach ($intermediacionparcial as $key => $value) {
                if (!isset($results[$key])) {
                    $results[$key] = new stdClass();
                    $results[$key]->intermediacion = $value;
                } else if (!isset($results[$key]->intermediacion)) {
                    $results[$key]->intermediacion = $value;
                } else {
                    $results[$key]->intermediacion += $value;
                }
            }
            unset($sp);
        }
        return $results;
    }

    /** Crea los vertices del grafico
     * @param Graph $graph
     * @param array $membersarray
     * @return array vertex */
    public function create_vertex($graph, $membersarray) {
        $vertex = [];
        foreach ($membersarray as $key) {
            $vertex[$key] = $graph->createVertex($key);
        }
        return $vertex;
    }

    /** Calculate IN/OUT Interactions number done by a member
     * Calcula el numero de interacciones hechas "de salida" o recibidas "de entrada"
     * para cada miembro del grupo
     * @param int[] $membersarray usersid to calculate for
     * @return array */
    public function degree_centrality($membersarray) {
        $degreealg = new Degree($this->graph);
        $indegree = [];
        $outdegree = [];
        foreach ($membersarray as $vertexid) {
            $outdegree[$vertexid] = $indegree[$vertexid] = 0;
        }
        foreach ($membersarray as $userid) {
            if ($this->graph->hasVertex($userid)) {
                $vertex = $this->graph->getVertex($userid);
                $indegree[$vertex->getId()] = $degreealg->getDegreeInVertex($vertex);
                $outdegree[$vertex->getId()] = $degreealg->getDegreeOutVertex($vertex);
            }
        }
        return [$indegree, $outdegree];
    }

    /** Calculate the "score" for each member by Posts, Repalys, Rereplays
     * and by "Reactions" and/or "Likes" her/him does.
     * @param social_interaction $interaction */
    public function get_score(social_interaction $interaction) {
        $score = 0;
        if ($interaction->type == social_interaction::POST || $interaction->type == social_interaction::REPLY ||
                 $interaction->type == "Rereply") {
            $score = 1;
        } else if ($interaction->type == social_interaction::REACTION || $interaction->type == "ReplyReaction" ||
                 $interaction->type == "RereplyReaction" || $interaction->type == social_interaction::MENTION) {
            $score = 0.1;
        } else {
            echo "Unknown Interaction type is a $interaction->type\n";
        }
        return $score;
    }

    /** Calcula un indice de "cercania" que representa lo cercano que est?? un miembro al resto de la
     * red
     * @param array $dmap
     * @param string $id
     * @return float $indice_cercania */
    public function centralidad_cercania($dmap, $id) {

        // Inicializo la suma geodesica a 0 "suma de la menor distancia de un miembro a cada nodo
        // que esta conectado".
        $sumageodesica = 0;
        // Nodos a los que el miembro es accesible, es decir; a los que est?? conectado, inicializo a
        // 0
        $accesible = 0;
        // Por cada nodo al que esta conectado.
        foreach ($dmap as $targetvertex => $distance) {
            // Elimino bucles en los que el miembro esta conectado a si mismo a trav??s de otros
            // usuarios
            if ($targetvertex != $id) {
                // Aumento en 1 el numero de nodos accesibles desde el miembro.
                $accesible += 1;
                // Incremento la suma geodesica con el valor del camino m??s corto a cada miembro.
                $sumageodesica += $distance;
            }
        }

        // Add distance to unaccesible nodes. Para ello recuento el numero de nodos del grafo
        // completo.
        $total = count($this->graph->getVertices());
        // Si la suma geodesica es 0 el nodo no tiene conexiones -> esta aislado, por tanto su
        // cercania a la red ser?? 0.
        if ($sumageodesica == 0) {
            $indicecercania = 0;
        } else {
            // Si no, ser?? la suma geodesica que ya teniamos calculada m??s un incremento que
            // corresponde al numero de nodos
            // inaccesibles sin contarse a ??l mismo.
            $unlinkeddistancefactor = 100; // Previouly we used (($total - 1) * 100);
            $sumageodesica += (($total - 1) - $accesible) * $unlinkeddistancefactor; // TODO: Check
                                                                                     // geodesic
                                                                                     // calc for
                                                                                     // inaccesible
                                                                                     // vertex.
                                                                                     // El indice de
                                                                                     // cercania
                                                                                     // ser?? menor
                                                                                     // cuanto mayor
                                                                                     // sea la suma
                                                                                     // geodesica.
            $indicecercania = (($total - 1) / $sumageodesica);
        }
        return $indicecercania;
    }

    /** Devolver la lista de members que est??n en alg??n Path y actualizar su contador cada vez que
     * ocurra
     * @param \Fhaculty\Graph\Graph $compactgraph
     * @param array $dmap
     * @param JPDijkstra $sp
     * @param string $id
     * @return array $indice_proximidad */
    public function centralidad_intermediacion($compactgraph, $dmap, $sp, $id) {
        // Obtengo los nodos por los que pasa en el camino mas corto a cada uno de los que est??
        // conectado.
        $indiceproximidad = array();
        // Por cada nodo al que el miembro en cuesti??n esta conectado.
        mtrace('<li>Betweenness of ' . count($dmap) . " nodes in distance map.</li>");
        foreach ($dmap as $key => $value) {
            // Elimino bucles por si est?? conectado a ??l mismo mediante otros nodos.
                try {
                if ($id != $key) {
                    if ($value > 1) {
                        $timestamp = microtime(true);
                        // Obtengo el nodo al que est?? conectado.

                        // Obtengo el camino para llegar a ese nodo.
                        $ids = [];
                        $previd = $key;
                        do {
                            $prevvertex = $sp->predecesVertexOfCheapestPathTo[$previd];
                            $previd = $prevvertex->getId();
                            if ($previd != $id) {
                                $ids[] = $previd;
                            }
                        } while ($previd != $id);

//                         $vertex = $compactgraph->getVertex($key);
//                         $path = $sp->getWalkTo($vertex);
                        // Obtengo los Ids de los nodos que est??n en ese camino.
//                         $ids = $path->getVertices()->getIds();
                        // Sin contar al usuario que estamos analizando y sin contar el ??ltimo nodo (ya que
                        // no estar??a en el medio del camino).
                        for ($i = 0; $i < count($ids); $i++) {
                            // Si no existe el indice de proximidad para el nodo estudiado le asigno valor
                            // 1, si ya existe incremento en 1.
                            $indiceproximidad[$ids[$i]] = isset($indiceproximidad[$ids[$i]]) ? $indiceproximidad[$ids[$i]] + 1 : 1;
                        }
//                         mtrace('<li>Got ' . count($ids) . ' intermediate nodes from ' . $id . ' to ' . $key . ' dmap=' . $value . ' in ' . round(microtime(true) - $timestamp, 4) . ' secs.' );
                    }
                }
            } catch ( OutOfBoundsException $ex) {
                    // ignore.
                mtrace('<li>No path found from ' . $id . ' to ' . $key . ' Distancemap says: ' . $value );

            }
        }
        return $indiceproximidad;
    }
}
