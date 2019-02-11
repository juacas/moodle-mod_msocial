<?php
namespace view\graph;
global $CFG;
require_once($CFG->dirroot . '/mod/msocial/classes/msocialharvestplugin.php');
require_once($CFG->dirroot . '/mod/msocial/classes/kpi.php');

use mod_msocial\filter_interactions;
use mod_msocial\kpi_info;
use mod_msocial\msocial_harvestplugin;
use mod_msocial\users_struct;
use mod_msocial\connector\msocial_connector_plugin;
use mod_msocial\connector\social_interaction;
use mod_msocial\msocial_plugin;

/**
 *
 * @author juacas
 *        
 */
class graph_harvester_local implements msocial_harvestplugin
{

    /**
     * Instance for the harvest.
     * @var msocial_plugin
     */
    var $plugin;
    /**
     */
    public function __construct(msocial_plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \mod_msocial\msocial_harvestplugin::harvest()
     */
    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    public function harvest() {
        $contextcourse = \context_course::instance($this->plugin->msocial->course);
        $usersstruct = msocial_get_users_by_type($contextcourse);
        $config = $this->plugin->get_config();
        return $this->do_harvest($this->plugin->msocial, $usersstruct);
    }
    protected function do_harvest($msocial, $usersstruct) {
        $async = false;
        $result = (object) ['messages' => []];
       
        if ($async) {
            require_once('graphtask.php');
            $users = $usersstruct->userrecords;
            
            $task = new \mod_social\view\graph\graph_task();
            $task->set_custom_data((object)['msocial' => $msocial, 'users' => $users ]);
            $task->execute();
            $result->messages[] = "For module msocial: $msocial->name (id=$msocial->id) in course (id=$msocial->course)" .
            " asynchronously processing network topology.";
            return $result;
        } else {
            
            //TODO: move calculate and store to harvester
            $kpis = $this->calculate_kpis($usersstruct);         
            $result->messages[] = "For module msocial: $msocial->name (id=$msocial->id) in course (id=$msocial->course) " .
            "processing network topology.";
            $result->kpis = $kpis;
            return $result;
        }
    }
    /**
     * {@inheritdoc}
     *
     * @see \mod_msocial\view\msocial_view_plugin::calculate_kpis() */
    public function calculate_kpis(users_struct $users, $kpis = []) {
        $msocial = $this->plugin->msocial; // TODO: Use as param in proxy.
        require_once('socialgraph.php');
        $kpiinfos = $this->plugin->get_kpi_list();
        foreach ($users->userrecords as $user) {
            if (!isset($kpis[$user->id])) {
                $kpis[$user->id] = new \mod_msocial\kpi($user->id, $msocial->id);
                // Reset to 0 to avoid nulls.
                $kpi = $kpis[$user->id];
                foreach ($kpiinfos as $kpiinfo) {
                    $kpi->{$kpiinfo->name} = 0;
                }
            }
        }
        // Get Interactions of all users, both known and anonymous.
        $filter = new filter_interactions([filter_interactions::PARAM_STARTDATE => $msocial->startdate,
            filter_interactions::PARAM_ENDDATE => $msocial->enddate,
            filter_interactions::PARAM_UNKNOWN_USERS => true,
            filter_interactions::PARAM_RECEIVED_BY_TEACHERS => true,
            filter_interactions::PARAM_INTERACTION_MENTION => true,
            filter_interactions::PARAM_INTERACTION_POST => true,
            filter_interactions::PARAM_INTERACTION_REACTION => true,
            filter_interactions::PARAM_INTERACTION_REPLY => true,
        ],
            $msocial);
        $interactions = social_interaction::load_interactions_filter($filter);
        // Socialmatrix analyzer.
        $social = new \SocialMatrix();
        foreach ($interactions as $interaction) {
            $social->register_interaction($interaction);
        }
        $results = $social->calculate_centralities($users->userrecords);
        list($degreein, $degreeout) = $social->degree_centrality(array_keys($kpis));
        
        foreach ($results as $userid => $result) {
            if (isset($kpis[$userid])) {
                $kpis[$userid]->closeness = isset($result->cercania) ? $result->cercania : 0;
                $kpis[$userid]->degreeout = isset($degreeout[$userid]) ? $degreeout[$userid] : 0;
                $kpis[$userid]->degreein = isset($degreein[$userid]) ? $degreein[$userid] : 0;
                $kpis[$userid]->betweenness = isset($result->intermediacion) ? $result->intermediacion : 0;
            }
        }
        $kpis = $this->calculate_aggregated_kpis($kpis);
        return $kpis;
    }
    
    /**
     * Calculates aggregated kpis from existent kpis.
     * @param array $kpis
     * TODO: currently aggregatios are database-computed. Check and redesign.
     */
    protected function calculate_aggregated_kpis(array $kpis) {
        $kpiinfos = $this->plugin->get_kpi_list();
        
        foreach ($kpiinfos as $kpiinfo) {
            if ($kpiinfo->individual == kpi_info::KPI_AGREGATED) {
                // Calculate aggregation.
                $parts = explode('_', $kpiinfo->name);
                $operation = $parts[0];
                $kpiname = $parts[1];
                $values = [];
                $aggregated = 0;
                foreach ($kpis as $kpi) {
                    $values[]  = $kpi->{$kpiname};
                }
                if ($operation == 'max') {
                    $aggregated = max($values);
                } else {
                    print_error('unsuported');
                }
                // Copy to all users.
                foreach ($kpis as $kpi) {
                    $kpi->{$kpiinfo->name}  = $aggregated;
                }
            }
        }
        return $kpis;
    }
}

