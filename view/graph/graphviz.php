<?php
use mod_tcount\social\social_interaction;
use Graphp\GraphViz\Dot;
require_once ('socialgraph.php');
require_once ('vendor/autoload.php');
$interactions = social_interaction::load_interactions($this->tcount->id);
$plugins = mod_tcount\plugininfo\tcountsocial::get_enabled_social_plugins($this->tcount);
$socialgraph = new SocialMatrix();
$context = context_module::instance($this->cm->id);
list($students, $nonstudents, $active, $users) = eduvalab_get_users_by_type($contextcourse);
foreach ($interactions as $interaction) {
    if (!isset($plugins[$interaction->source]) || $plugins[$interaction->source]->is_enabled()==false){
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

    $socialgraph->register_interaction($from, $to, $type, ['graphviz.label' => $type]);
}
$dot = new Dot();
$dotsource = $dot->getOutput($socialgraph->get_graph());
// Inject formatting.
$formatdot = "	rankdir=LR;";
$dotsource = str_replace('digraph G {', "digraph G {\n$formatdot\n", $dotsource);
$reqs->js('/mod/tcount/view/graph/js/configuregraphvizrequire.js', false);
$reqs->js_call_amd('tcountview/graphviz', 'initview', ['#graph', '#dot_src']);
/* @var $OUTPUT \core_renderer */
echo $OUTPUT->container('', '', 'graph');
echo $OUTPUT->container($dotsource, 'hidden', 'dot_src');


