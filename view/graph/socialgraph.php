<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once ('vendor/autoload.php');
require_once ('JPDijkstra.php');
// require_once 'centralidad.php';

use \Fhaculty\Graph\Graph as Graph;
use mod_tcount\social\social_interaction;


class SocialMatrix {

    private $matriz_adyacencia = [];

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
    function get_graph() {
        return $this->graph;
    }

    /**
     * Crea un grafico a partir de un array de miembros
     * @param array $members_array
     */
    function generateSubGraph($members_array) {

        // Defino un grafico nuevo
        $CompactGraph = new Graph();
        // Crea los vertices
        foreach ($members_array as $value => $key) {
            $CompactGraph->createVertex($value);
        }
        // Creo los arcos entre los vertices con el peso que indica la matriz de adyacencia
        foreach ($members_array as $value1 => $key1) {
            foreach ($members_array as $value2 => $key2) {
                if (isset($this->matriz_adyacencia[$value1][$value2])) {
                    $FromVertex = $CompactGraph->getVertex($value2);
                    $ToVertex = $CompactGraph->getVertex($value1);
                    $FromVertex->createEdgeTo($ToVertex)->setWeight($this->matriz_adyacencia[$value1][$value2]);
                }
            }
        }
        return $CompactGraph;
    }

    /**
     *
     * @param unknown $from
     * @param unknown $to
     * @param string $type social_interaction::POST and other constants
     * @param unknown $edgeattrs Edge attrs array.
     * @param unknown $date
     */
    function register_interaction($from, $to, $type, $edgeattrs = [], $date = null) {
        if ($from == null || $to == null) {
            return;
        }
        $weight = $this->addScore($from, $type);
        if ((strcmp($to, $from) !== 0) && (strcmp($type, social_interaction::POST) !== 0)) {
            if (!isset($this->matriz_adyacencia[$to][$from])) {
                $this->matriz_adyacencia[$to][$from] = $weight;
            } else {
                $this->matriz_adyacencia[$to][$from] += $weight;
            }
            if (!$this->graph->hasVertex($from)) {
                $fromVertex = $this->graph->createVertex($from);
            } else {
                $fromVertex = $this->graph->getVertex($from);
            }
            if (!$this->graph->hasVertex($to)) {
                $toVertex = $this->graph->createVertex($to);
            } else {
                $toVertex = $this->graph->getVertex($to);
            }
            $edge = $fromVertex->createEdgeTo($toVertex);
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
    function calculateCentralities($compactgraph = null) {
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
            $ind_cercania = $this->centralidad_cercania($dmap, $id);
            if (!isset($results[$member->getId()])) {
                $results[$member->getId()] = new stdClass();
            }
            $results[$member->getId()]->cercania = $ind_cercania;

            // Calculo de todos los "vertex" que están entre medias de los caminos mas cortos
            $intermediacion_parcial = $this->centralidad_intermediacion($compactgraph, $dmap, $sp, $id);
            // añade a global
            foreach ($intermediacion_parcial as $key => $value) {
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
     * @param array $members_array
     * @return array vertex
     */
    function create_vertex($graph, $members_array) {
        $vertex = [];
        foreach ($members_array as $key) {
            $vertex[$key] = $graph->createVertex($key);
        }
        return $vertex;
    }

    /**
     * Calculate IN/OUT Interactions number done by a member
     * Calcula el numero de interacciones hechas "de salida" o recibidas "de entrada"
     * para cada miembro del grupo
     * @param array $members_array
     * @return array
     */
    function centralidad_grado($members_array) {
        $vector_entrada = [];
        $vector_salida = [];
        foreach ($members_array as $value => $key) {
            $vector_salida[$value] = $vector_entrada[$value] = 0;
        }
        foreach ($members_array as $value1 => $key1) {
            foreach ($members_array as $value2 => $key2) {
                if (isset($this->matriz_adyacencia[$value1][$value2])) {
                    $vector_entrada[$value1] += $this->matriz_adyacencia[$value1][$value2];
                }
                if (isset($this->matriz_adyacencia[$value2][$value1])) {
                    $vector_salida[$value1] += $this->matriz_adyacencia[$value2][$value1];
                }
            }
        }
        return [$vector_entrada, $vector_salida];
    }

    /**
     * Update the "score" for each member by Posts, Repalys, Rereplays
     * and by "Reactions" and/or "Likes" her/him does.
     * @param string $member
     * @param type $typeInteraction
     */
    function addScore($member, $typeInteraction) {
        $weigth = 1;
        if ($typeInteraction == social_interaction::POST || $typeInteraction == social_interaction::REPLY ||
                 $typeInteraction == "Rereply") {
            if (isset($this->score[$member])) {
                $this->score[$member] += $weigth;
            } else {
                $this->score[$member] = $weigth;
            }
        } else if ($typeInteraction == social_interaction::REACTION || $typeInteraction == "ReplyReaction" ||
                 $typeInteraction == "RereplyReaction" || $typeInteraction == social_interaction::MENTION) {
            $weigth = 0.1;
            if (isset($this->score[$member])) {
                $this->score[$member] += $weigth;
            } else {
                $this->score[$member] = $weigth;
            }
        } else {
            echo "Type Interaction is a $typeInteraction\n";
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
    function centralidad_cercania($dmap, $id) {

        // Inicializo la suma geodesica a 0 "suma de la menor distancia de un miembro a cada nodo
        // que esta conectado"
        $suma_geodesica = 0;
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
                $suma_geodesica += $key;
            }
        }

        // Add distance to unaccesible nodes. Para ello recuento el numero de nodos del grafo
        // completo
        $total = count($this->graph->getVertices());
        // Si la suma geodesica es 0 el nodo no tiene conexiones -> esta aislado, por tanto su
        // cercania a la red será 0
        if ($suma_geodesica == 0) {
            $indice_cercania = 0;
        } else {
            // Si no, será la suma geodesica que ya teniamos calculada más un incremento que
            // corresponde al numero de nodos
            // inaccesibles sin contarse a él mismo
            $suma_geodesica += (($total - 1) - $accesible) * (($total - 1) * 100);
            // El indice de cercania será menor cuanto mayor sea la suma geodesica
            $indice_cercania = (($total - 1) / $suma_geodesica);
        }
        return $indice_cercania;
    }

    /**
     * Devolver la lista de members que están en algún Path y actualizar su contador cada vez que
     * ocurra
     * @param \Fhaculty\Graph\Graph $CompactGrahp
     * @param array $dmap
     * @param JPDijkstra $sp
     * @param string $id
     * @return array $indice_proximidad
     */
    function centralidad_intermediacion($CompactGrahp, $dmap, $sp, $id) {

        // Obtengo los nodos por los que pasa en el camino mas corto a cada uno de los que está
        // conectado
        $indice_proximidad = array();
        // Por cada nodo al que el miembro en cuestión esta conectado:
        foreach ($dmap as $key => $value) {
            // Elimino bucles por si está conectado a él mismo mediante otros nodos
            if (strcmp($id, $key) != 0) {
                // Obtengo el nodo al que está conectado
                $vertex = $CompactGrahp->getVertex($key);
                // Obtengo el camino para llegar a ese nodo
                $path = $sp->getWalkTo($vertex);
                // Obtengo los Ids de los nodos que están en ese camino
                $ids = $path->getVertices()->getIds();
                // Sin contar al usuario que estamos analizando y sin contar el último nodo (ya que
                // no estaría en el medio del camino)
                for ($i = 1; $i < count($ids) - 1; $i++) {
                    // Si no existe el indice de proximidad para el nodo estudiado le asigno valor
                    // 1, si ya existe incremento en 1
                    $indice_proximidad[$ids[$i]] = isset($indice_proximidad[$ids[$i]]) ? $indice_proximidad[$ids[$i]] + 1 : 1;
                }
            }
        }
        return $indice_proximidad;
    }
}
