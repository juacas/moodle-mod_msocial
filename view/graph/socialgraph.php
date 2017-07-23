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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with MSocial for Moodle.  If not, see <http://www.gnu.org/licenses/>.
defined('MOODLE_INTERNAL') || die();

require_once ('vendor/autoload.php');
require_once ('JPDijkstra.php');

use \Fhaculty\Graph\Graph as Graph;
use mod_msocial\connector\social_interaction;

class SocialMatrix {

    private $adjacencymatrix = [];

    /**
     *
     * @var Graph $graph
     */
    private $graph;

    private $score = [];

    public function __construct() {
        $this->graph = new Graph();
    }

    /**
     * Devuelve el grafico
     * @return Graph
     */
    public function get_graph() {
        return $this->graph;
    }

    /**
     * Crea un grafico a partir de un array de miembros
     * @param array $membersarray
     */
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
     *
     * @param unknown $from
     * @param unknown $to
     * @param string $type social_interaction::POST and other constants
     * @param unknown $edgeattrs Edge attrs array.
     * @param unknown $date
     */
    public function register_interaction($from, $to, $type, $edgeattrs = [], $date = null) {
        if ($from == null || $to == null) {
            return;
        }
        $weight = $this->add_score($from, $type);
        if ((strcmp($to, $from) !== 0) && (strcmp($type, social_interaction::POST) !== 0)) {
            if (!isset($this->adjacencymatrix[$to][$from])) {
                $this->adjacencymatrix[$to][$from] = $weight;
            } else {
                $this->adjacencymatrix[$to][$from] += $weight;
            }
            if (!$this->graph->hasVertex($from)) {
                $fromvertex = $this->graph->createVertex($from);
            } else {
                $fromvertex = $this->graph->getVertex($from);
            }
            if (!$this->graph->hasVertex($to)) {
                $tovertex = $this->graph->createVertex($to);
            } else {
                $tovertex = $this->graph->getVertex($to);
            }
            $edge = $fromvertex->createEdgeTo($tovertex);
            $edge->setAttribute('type', $type);
            $edge->setWeight($weight);
            $edge->getAttributeBag()->setAttributes($edgeattrs);
        }
    }

    /**
     * Calcula la suma de pesos por el camino más corto de un miembro al resto de miembros
     * a los que esté conectado (cercania), y cuantas veces van apareciendo los "vertex" en el medio
     * de esos caminos (proximidad).
     * @return \stdClass $results
     */
    public function calculate_centralities($compactgraph = null) {
        if ($compactgraph == null) {
            $compactgraph = $this->get_graph();
        }
        // global $dot;
        $results = [];

        $members = $compactgraph->getVertices();
        foreach ($members as $member) {

            // Obtiene el camino mas corto a cada uno de los vertex que esta conectado el miembro
            $sp = new JPDijkstra($member);
            // Array que contiene como clave los "ids" y como valor el "peso" total (por el camino
            // mas corto)
            // a cada uno de los "Vertex" que está conectado
            $dmap = $sp->getDistanceMap();
            // Calculo de la suma de todos los caminos a todos los vertices que está conectado.
            $id = $member->getId();
            $indcercania = $this->centralidad_cercania($dmap, $id);
            if (!isset($results[$member->getId()])) {
                $results[$member->getId()] = new stdClass();
            }
            $results[$member->getId()]->cercania = $indcercania;

            // Calculo de todos los "vertex" que están entre medias de los caminos mas cortos
            $intermediacionparcial = $this->centralidad_intermediacion($compactgraph, $dmap, $sp, $id);
            // añade a global
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
        }
        return $results;
    }

    /**
     * Crea los vertices del grafico
     * @param Graph $graph
     * @param array $membersarray
     * @return array vertex
     */
    public function create_vertex($graph, $membersarray) {
        $vertex = [];
        foreach ($membersarray as $key) {
            $vertex[$key] = $graph->createVertex($key);
        }
        return $vertex;
    }

    /**
     * Calculate IN/OUT Interactions number done by a member
     * Calcula el numero de interacciones hechas "de salida" o recibidas "de entrada"
     * para cada miembro del grupo
     * @param array $membersarray
     * @return array
     */
    public function centralidad_grado($membersarray) {
        $vectorentrada = [];
        $vectorsalida = [];
        foreach ($membersarray as $value => $key) {
            $vectorsalida[$value] = $vectorentrada[$value] = 0;
        }
        foreach ($membersarray as $value1 => $key1) {
            foreach ($membersarray as $value2 => $key2) {
                if (isset($this->adjacencymatrix[$value1][$value2])) {
                    $vectorentrada[$value1] += $this->adjacencymatrix[$value1][$value2];
                }
                if (isset($this->adjacencymatrix[$value2][$value1])) {
                    $vectorsalida[$value1] += $this->adjacencymatrix[$value2][$value1];
                }
            }
        }
        return [$vectorentrada, $vectorsalida];
    }

    /**
     * Update the "score" for each member by Posts, Repalys, Rereplays
     * and by "Reactions" and/or "Likes" her/him does.
     * @param string $member
     * @param type $typeinteraction
     */
    public function add_score($member, $typeinteraction) {
        $weigth = 1;
        if ($typeinteraction == social_interaction::POST || $typeinteraction == social_interaction::REPLY ||
                 $typeinteraction == "Rereply") {
            if (isset($this->score[$member])) {
                $this->score[$member] += $weigth;
            } else {
                $this->score[$member] = $weigth;
            }
        } else if ($typeinteraction == social_interaction::REACTION || $typeinteraction == "ReplyReaction" ||
                 $typeinteraction == "RereplyReaction" || $typeinteraction == social_interaction::MENTION) {
            $weigth = 0.1;
            if (isset($this->score[$member])) {
                $this->score[$member] += $weigth;
            } else {
                $this->score[$member] = $weigth;
            }
        } else {
            echo "Type Interaction is a $typeinteraction\n";
        }
        return $weigth;
    }

    /**
     * Calcula un indice de "cercania" que representa lo cercano que está un miembro al resto de la
     * red
     * @param array $dmap
     * @param string $id
     * @return float $indice_cercania
     */
    public function centralidad_cercania($dmap, $id) {

        // Inicializo la suma geodesica a 0 "suma de la menor distancia de un miembro a cada nodo
        // que esta conectado"
        $sumageodesica = 0;
        // Nodos a los que el miembro es accesible, es decir; a los que está conectado, inicializo a
        // 0
        $accesible = 0;
        // Por cada nodo al que esta conectado:
        foreach ($dmap as $value => $key) {
            // Elimino bucles en los que el miembro esta conectado a si mismo a través de otros
            // usuarios
            if (strcmp($value, $id) != 0) {
                // Aumento en 1 el numero de nodos accesibles desde el miembro
                $accesible += 1;
                // Incremento la suma geodesica con el valor del camino más corto a cada miembro
                $sumageodesica += $key;
            }
        }

        // Add distance to unaccesible nodes. Para ello recuento el numero de nodos del grafo
        // completo
        $total = count($this->graph->getVertices());
        // Si la suma geodesica es 0 el nodo no tiene conexiones -> esta aislado, por tanto su
        // cercania a la red será 0
        if ($sumageodesica == 0) {
            $indicecercania = 0;
        } else {
            // Si no, será la suma geodesica que ya teniamos calculada más un incremento que
            // corresponde al numero de nodos
            // inaccesibles sin contarse a él mismo
            $sumageodesica += (($total - 1) - $accesible) * (($total - 1) * 100);
            // El indice de cercania será menor cuanto mayor sea la suma geodesica
            $indicecercania = (($total - 1) / $sumageodesica);
        }
        return $indicecercania;
    }

    /**
     * Devolver la lista de members que están en algún Path y actualizar su contador cada vez que
     * ocurra
     * @param \Fhaculty\Graph\Graph $compactgraph
     * @param array $dmap
     * @param JPDijkstra $sp
     * @param string $id
     * @return array $indice_proximidad
     */
    public function centralidad_intermediacion($compactgraph, $dmap, $sp, $id) {

        // Obtengo los nodos por los que pasa en el camino mas corto a cada uno de los que está
        // conectado
        $indiceproximidad = array();
        // Por cada nodo al que el miembro en cuestión esta conectado:
        foreach ($dmap as $key => $value) {
            // Elimino bucles por si está conectado a él mismo mediante otros nodos
            if (strcmp($id, $key) != 0) {
                // Obtengo el nodo al que está conectado
                $vertex = $compactgraph->getVertex($key);
                // Obtengo el camino para llegar a ese nodo
                $path = $sp->getWalkTo($vertex);
                // Obtengo los Ids de los nodos que están en ese camino
                $ids = $path->getVertices()->getIds();
                // Sin contar al usuario que estamos analizando y sin contar el último nodo (ya que
                // no estaría en el medio del camino)
                for ($i = 1; $i < count($ids) - 1; $i++) {
                    // Si no existe el indice de proximidad para el nodo estudiado le asigno valor
                    // 1, si ya existe incremento en 1
                    $indiceproximidad[$ids[$i]] = isset($indiceproximidad[$ids[$i]]) ? $indiceproximidad[$ids[$i]] + 1 : 1;
                }
            }
        }
        return $indiceproximidad;
    }
}
