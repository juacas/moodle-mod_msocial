<?php
use mod_msocial\connector\social_interaction;
use Graphp\GraphViz\Dot;
require_once ('socialgraph.php');
require_once ('vendor/autoload.php');
$interactions = social_interaction::load_interactions($this->msocial->id);
$plugins = mod_msocial\plugininfo\msocialconnector::get_enabled_connector_plugins($this->msocial);
$socialgraph = new SocialMatrix();
$context = context_module::instance($this->cm->id);
list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
foreach ($interactions as $interaction) {
    if (!isset($plugins[$interaction->source]) || $plugins[$interaction->source]->is_enabled() == false) {
        continue;
    }
    if ($interaction->fromid == null) {
        $from = $interaction->nativefromname;
    } else {
        $from = fullname($users[$interaction->fromid]);
    }
    if ($interaction->toid == null) {
        $to = $interaction->nativetoname;
    } else {
        $to = fullname($users[$interaction->toid]);
    }
    $type = $interaction->type;
    $socialgraph->register_interaction( $interaction,
                                        ['graphviz.label' => $type],
                                        ['graphviz.label' => $from],
                                        ['graphviz.label' => $to]);
}
$dot = new Dot();
$graph = $socialgraph->get_graph();
$graph->getAttributeBag()->setAttribute('graphviz.graph.rankdir', 'LR');
$dotsource = $dot->getOutput($graph);

$reqs->js('/mod/msocial/view/graph/js/configuregraphvizrequire.js', false);
$reqs->js_call_amd('msocialview/graphviz', 'initview', ['#graph', '#dot_src']);
/* @var $OUTPUT \core_renderer */
echo $OUTPUT->container('', '', 'graph');
echo $OUTPUT->container($dotsource, 'hidden', 'dot_src');


