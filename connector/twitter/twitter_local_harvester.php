<?php
namespace connector\twitter;

use mod_msocial\filter_interactions;
use mod_msocial\kpi;
use mod_msocial\kpi_info;
use mod_msocial\msocial_harvestplugin;
use mod_msocial\msocial_plugin;
use mod_msocial\social_user;
use mod_msocial\users_struct;
use mod_msocial\connector\TwitterAPIExchange;
use mod_msocial\connector\social_interaction;
use mod_msocial\social_user_cache;
global $CFG;
require_once('TwitterAPIExchange.php');
require_once($CFG->dirroot . '/mod/msocial/classes/tagparser.php');
require_once($CFG->dirroot . '/mod/msocial/classes/socialinteraction.php');
require_once($CFG->dirroot . '/mod/msocial/classes/filterinteractions.php');
require_once($CFG->dirroot . '/mod/msocial/classes/social_user_cache.php');


/**
 *
 * @author juacas
 *
 */
class twitter_local_harvester implements msocial_harvestplugin
{
    /**
     * Instance for the harvest.
     * @var msocial_plugin
     */
    var $plugin;
    var $lastinteractions = [];
    var $connectiontoken;
    var $msocial;
    var $hashtag;
    var $mappedusers;
    /**
     * @var social_user_cache
     */
    var $socialusercache;
    var $cm;
    var $config;
    /**
     */
    public function __construct(msocial_plugin $plugin, $mappedusers) {
        global $DB;
        $this->plugin = $plugin;
        $this->cmid = $plugin->get_cmid();
        $this->msocial = $plugin->msocial;
        $this->connectiontoken = $plugin->get_connection_token();
        $this->config = $plugin->get_config();
        $this->hashtag = $this->plugin->get_config('hashtag');
        $this->mappedusers = $mappedusers;
        $this->socialusercache = new social_user_cache($mappedusers);
        
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
       
        return $this->do_harvest($this->plugin->msocial, $usersstruct);
    }
    protected function do_harvest($msocial, $usersstruct) {
        $resultusers = $this->harvest_users($this->connectiontoken, $this->hashtag, $usersstruct);
        $resulttags = $this->harvest_hashtags($this->connectiontoken, $this->hashtag, $usersstruct);
        $interactions = $this->merge_interactions($resultusers->interactions, $resulttags->interactions);        
 
        // Store Interactions and reload full collection.
        social_interaction::store_interactions($interactions, $msocial->id);
        $filter = new filter_interactions([
                                    filter_interactions::PARAM_SOURCES => $this->plugin->get_subtype(),
                                    ], $msocial);
        $filter->set_users($usersstruct);
        $interactions = social_interaction::load_interactions_filter($filter);
        $likeinteractions = $this->refresh_likes($interactions);
        if (count($likeinteractions) > 0) {
            $interactions = $this->merge_interactions($interactions, $likeinteractions);
            social_interaction::store_interactions($likeinteractions, $msocial->id);
        }
        $retweetinteractions = $this->refresh_retweets($interactions);
        if (count($retweetinteractions) > 0) {
            $interactions = $this->merge_interactions($interactions, $retweetinteractions);
            social_interaction::store_interactions($retweetinteractions, $msocial->id);
        }
        $result = new \stdClass();
        $result->interactions = $interactions;
        $result->statuses = array_merge($resultusers->statuses, $resulttags->statuses);
        $result->errors = array_merge($resultusers->errors, $resulttags->errors);
        $result->messages = array_merge($resultusers->messages, $resulttags->messages);
        // Calculate metrics not modelled as interactions.
        $kpis = $this->calculate_kpis($usersstruct);
        $result->kpis = $kpis;
        
        // TODO: obtain token errors.
        $result->badtokens = []; //array_merge($resultusers->badtokens, $resulttags->badtokens);
        return $result;
    }
    protected function harvest_users($token, $hashtag, $usersstruct) {
        global $DB;
        $targetusers = [];
        foreach ($this->mappedusers as $socialuser) {
            if (array_key_exists($socialuser->userid, $usersstruct->userrecords)) {
                $targetusers[] = $socialuser;                
            }
        }
        $result = $this->get_users_statuses($token, $targetusers, $hashtag);
        $errormessage = null;
        
        if (isset($result->errors)) {
            // TODO: generate best error message.
            if ($token) {
                $info = "UserToken for: $token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = implode('. ', $result->errors);
            $msocial = $this->msocial;
            $cmid = $this->cmid;
            $errormessage = "ERROR:" . $errormessage;
            $result->messages[] = "Searching by users. For module msocial\connector\twitter: $msocial->name (id=$cmid) " .
            " in course (id=$msocial->course) searching: $hashtag $info";
            $result->errors[0] = (object) ['message' => $errormessage];
        } else {
            $result->messages = [];
            $result->errors = [];
        }
        if (isset($result->statuses)) {
            $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
            $msocial = $this->msocial;
            
            $processedstatuses = $this->process_statuses($statuses, $this->msocial, $usersstruct);
            $studentstatuses = array_filter($processedstatuses,
                function ($status) {
                    return isset($status->userauthor);
                });
            $this->store_status($processedstatuses);
            $interactions = $this->build_interactions($processedstatuses);
            $errormessage = null;
            $result->interactions = $interactions;          
            $result->messages[] = "Searching by users. For module msocial\\connector\\twitter by users: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) searching: $hashtag  ";
        } else {
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[0]->message = $errormessage;
            $msocial = $this->msocial;
            $result->messages[] = "Searching by users. For module msocial\\connector\\twitter by users: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) searching: $hashtag  ";
            $result->statuses = [];
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token); // TODO: mark outside
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
            }
        }
        return $result;
    }
    /**
     * Calculate KPIs not calculable from interations.
     * retweets are not modeled in the interactions.
     * @param users_struct $user struct of arrays @see msocial_get_users_by_type().
     * @param social_interaction[] $interactions list of interactions to calculate on.
     * @para kpi[] $kpis previous model to be complemented.
     * @return array[kpi] */
    public function calculate_kpis(users_struct $users, $kpis = []) {
//         $stats = $this->calculate_stats($users->studentids);
//         $stataggregated = $stats->maximums;
        // Convert stats to KPI.
//         foreach ($stats->users as $userid => $stat) {
//             $kpi = isset($kpis[$userid]) ? $kpis[$userid] : null;
//             $kpis[$userid] = $this->kpi_from_stat($userid, $stat, $stataggregated, $this, $kpi);
//         }
        return $kpis;
    }
    /**
     * @param \stdClass $user
     * @param \stdClass $stat
     * @param msocial_plugin $msocialplugin
     * @param kpi $kpi existent kpi. For chaining calls. Assumes user and msocialid are coherent.
     * @return kpi_info[]
     * @deprecated
     */
    private function kpi_from_stat($user, $stat, $stataggregated, $msocialplugin, $kpi = null) {
        $kpi = $kpi == null ? new kpi($user, $msocialplugin->msocial->id) : $kpi;
        foreach ($stat as $propname => $value) {
            $kpi->{$propname} = $value;
        }
        foreach ($stataggregated as $propname => $value) {
            $kpi->{$propname} = $value;
        }
        
        return $kpi;
    }
    
    /** Statistics for grading
     * @deprecated
     *
     * @param array[]integer $users array with the userids to be calculated, null not filter by
     *        users.
     * @return array[string]object object->userstats with KPIs for each user object->maximums max
     *         values for normalization. */
    private function calculate_stats($users) {
        global $DB;
        $cm = get_coursemodule_from_instance('msocial', $this->msocial->id, 0, false, MUST_EXIST);
        $stats = $DB->get_records_sql(
            'SELECT userid as id, sum(retweets) as retweets ' .
            'FROM {msocial_tweets} where msocial = ? and userid is not null group by userid', array($this->msocial->id));
        $userstats = new \stdClass();
        $userstats->users = array();
        
        $favs = array();
        $retweets = array();
        if ($users == null) {
            $users = array_keys($stats);
        }
        foreach ($users as $userid) {
            $stat = new \stdClass();
            
            if (isset($stats[$userid])) {
                $retweets[] = $stat->retweets = $stats[$userid]->retweets;
                $favs[] = $stat->favs = $stats[$userid]->favs;
            } else {
                $stat->retweets = 0;
                $stat->favs = 0;
            }
            $userstats->users[$userid] = $stat;
        }
        $stat = new \stdClass();
        $stat->max_favs = count($favs) == 0 ? 0 : max($favs);
        $stat->max_retweets = count($retweets) == 0 ? 0 : max($retweets);
        $userstats->maximums = $stat;
        
        return $userstats;
    }
    /** Execute a Twitter API query with auth tokens and the hashtag configured in the module
     *
     * @global type $DB
     * @param \stdClass[] tokens records.
     * @param string $hashtag search pattern.
     * @return mixed object report of activity. $result->statuses $result->messages[]string
     *         $result->errors[]->message */
    protected function get_statuses($tokens, $hashtag) {
        return $this->search_twitter($tokens, $hashtag); // Twitter API depends on letter cases.
    }
    
    /**
     *
     * @param \stdClass[] $tokens
     * @param \stdClass[] $users records from mdl_msocial_mapusers
     * @param string $hashtag
     * @throws \ErrorException
     * @return mixed
     */
    protected function get_users_statuses($tokens, $users, $hashtag) {
        
        $totalresults = new \stdClass();
        if (!$tokens) {
            $result = (object) ['statuses' => [],
                'errors' => ['message' => "No connection tokens provided!!! Impossible to connect to twitter."]];
            return $result;
        }
        if (count($users) == 0) {
            $totalresults->statuses = [];
            return $totalresults;
        }
        $tagparser = new \tag_parser($hashtag);
        global $CFG;
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret,
            'consumer_key' => get_config('msocialconnector_twitter', 'consumer_key'),
            'consumer_secret' => get_config('msocialconnector_twitter', 'consumer_secret')
        );
        foreach ($users as $socialuser) {
            // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
            // Perform the request and return the parsed response.
            $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
            $getfield = "screen_name=$socialuser->socialname&count=50&tweet_mode=extended";
            $requestmethod = "GET";
            $twitter = new TwitterAPIExchange($settings);
            $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();
            $result = json_decode($json);
            if ($result == null || isset($result->errors)) {
                
                $msg = "Error querying last tweets from user $socialuser->socialname. Response was " .
                ($result == null ? $json : print_r($result->errors, true));
                $totalresults->errors[] = $msg;

                $totalresults->badtokens[]=(object)['user'=>$socialuser, 'msg' => $msg ];
            } else {
                // Order results to detect threads.
                if (count($result)) {
                    usort($result, function($itema, $itemb){
                        $datea = new \DateTime($itema->created_at);
                        $dateb = new \DateTime($itemb->created_at);
                        return $datea > $dateb;
                    });
                }
                // Filter hashtags.
                $statuses = [];
                foreach ($result as $status) {
                    if ($status instanceof \stdClass) {
                        $text = $status->full_text;
                        $isthread = key_exists($status->in_reply_to_status_id, $status);
                        if ($tagparser->check_hashtaglist($text) || $isthread) {
                            $statuses[$status->id] = $status;
                        }
                    }
                }
                $totalresults->statuses = array_merge(isset($totalresults->statuses) ? $totalresults->statuses : [],
                    $statuses);
            }
        }
        return $totalresults;
    }
    
    /** Process the statuses looking for students mentions
     * TODO: process entities->user_mentions[]
     *
     * @param \stdClass[] $statuses
     * @return \stdClass[] student statuses meeting criteria. */
    protected function process_statuses($statuses, $msocial, $usersstruct) {
      
        $userrecords = $usersstruct->userrecords;
        $twitters = array();
        foreach ($userrecords as $userid => $user) { // Include all users (including teachers).
            $socialuserid = $this->socialusercache->get_social_userid($user); // Get twitter usernames from users'
            // profile.
            if ($socialuserid !== null) {
                $twittername = $socialuserid->socialname;
                $twitters[$twittername] = $userid;
            }
        }
        // Compile statuses of the users.
        $studentsstatuses = array();
        foreach ($statuses as $status) {
            $twittername = $status->user->screen_name;
            // TODO : process entities->user_mentions[] here...
            if (isset($twitters[$twittername])) { // Tweet is from a student.
                $userauthor = $userrecords[$twitters[$twittername]];
            } else {
                $userauthor = null;
            }
            $createddate = strtotime($status->created_at);
            if (isset($status->retweeted_status)) { // Retweet count comes from original message.
                // Ignore it.
                $status->retweet_count = 0;
            }
            if (msocial_time_is_between($createddate, $this->msocial->startdate, $this->msocial->enddate)) {
                $status->userauthor = $userauthor;
                $studentsstatuses[] = $status;
            }
        } // Iterate tweets (statuses)...
        return $studentsstatuses;
    }
    /** TODO : save records in bunches.
     *
     * @deprecated
     *
     * @global moodle_database $DB
     * @param array[]mixed $status
     * @param mixed $userrecord */
    protected function store_status($statuses) {
        global $DB;
        foreach ($statuses as $status) {
            $userrecord = isset($status->userauthor) ? $status->userauthor : null;
            $tweetid = $status->id_str;
            $statusrecord = $DB->get_record('msocial_tweets',
                array('msocial' => $this->msocial->id, 'tweetid' => $tweetid));
            if (!$statusrecord) {
                $statusrecord = new \stdClass();
            } else {
                $DB->delete_records('msocial_tweets',
                    array('msocial' => $this->msocial->id, 'tweetid' => $tweetid));
            }
            $statusrecord->tweetid = $tweetid;
            $statusrecord->twitterusername = $status->user->screen_name;
            $statusrecord->msocial = $this->msocial->id;
            $statusrecord->status = json_encode($status);
            $statusrecord->userid = $userrecord != null ? $userrecord->id : null;
            $statusrecord->retweets = $status->retweet_count;
            $statusrecord->favs = $status->favorite_count;
            $statusrecord->hashtag = $this->hashtag;
            $DB->insert_record('msocial_tweets', $statusrecord);
        }
    }
   
    /** Connect to twitter API at https://api.twitter.com/1.1/search/tweets.json
     *
     * @global type $CFG
     * @param \stdClass[] $tokens oauth tokens
     * @param string $hashtag hashtag to search for
     * @return \stdClass result->statuses o result->errors[]->message (From Twitter API.) */
    protected function search_twitter($tokens, $hashtag) {
        if (!$tokens) {
            $result = (object) ['statuses' => [],
                'errors' => [(object)['message' => "No connection tokens provided!!! Impossible to connect to twitter."]]];
            return $result;
        }
        $settings = array('oauth_access_token' => $tokens->token, 'oauth_access_token_secret' => $tokens->token_secret,
            'consumer_key' => get_config('msocialconnector_twitter', 'consumer_key'),  // ...twitter
            // developer
            // app
            // key.
            'consumer_secret' => get_config('msocialconnector_twitter', 'consumer_secret') // ...twitter
            // developer
            // app
            // secret.
        );
        // URL for REST request, see: https://dev.twitter.com/docs/api/1.1/
        // Perform the request and return the parsed response.
        $url = 'https://api.twitter.com/1.1/search/tweets.json';
        $getfield = "q=$hashtag&count=100&tweet_mode=extended";
        $requestmethod = "GET";
        $twitter = new TwitterAPIExchange($settings);
        $json = $twitter->set_getfield($getfield)->build_oauth($url, $requestmethod)->perform_request();
        $result = json_decode($json);
        if ($result == null) {
            throw new \ErrorException("Fatal error connecting with Twitter. Response was: $json");
        }
        return $result;
    }
    /**
     * Search for new Likes.
     * @param social_interaction[] $targetinteractions
     */
    protected function refresh_likes($targetinteractions) {
        $likeinteractions = [];
        $interactions = array_filter($targetinteractions, function(social_interaction $inter) {
           return ($inter->type == social_interaction::POST);
        });
//         $filter = new filter_interactions([filter_interactions::PARAM_SOURCES => $this->plugin->get_subtype(),
//             filter_interactions::PARAM_INTERACTION_POST => true], $this->msocial);
//         $interactions = social_interaction::load_interactions_filter($filter);
//         $interactions = $this->merge_interactions($interactions, $this->lastinteractions);
        
        mtrace("<li>Checking ". count($interactions) . " tweets for Favs.");
        foreach ($interactions as $interaction) {
            if ($interaction->type == social_interaction::POST) {
                $status = json_decode($interaction->rawdata);
                if ($status->favorited == false) {
                    continue;
                }
                mtrace("<li>Getting favs for " . $this->plugin->get_interaction_url($interaction));
                $popupcode = $this->browse_twitter('https://twitter.com/i/activity/favorited_popup?id=' . $interaction->uid);
                $json = json_decode($popupcode);
                if (isset($json->htmlUsers)) {
                    $users = $json->htmlUsers;
                } else {
                    continue; // Tweet deleted or account made private.
                }
                $matches = [];
                preg_match_all('/screen-name="(?\'screenname\'[\w\s]+)"\s+data-user-id="(?\'userid\'\d+)"/', $users, $matches, PREG_PATTERN_ORDER);
                $count = count($matches[1]);
                if ($count == 0) {
                    continue;
                }
                mtrace("<li>Tweet " . $this->plugin->get_interaction_url($interaction) . " has $count favs.");
                for ($i = 0; $i < $count; $i++) {
                    // Create a new Like interaction.
                    $likeinteraction = new social_interaction();
                    $likeinteraction->source = $this->plugin->get_subtype();
                    $likeinteraction->nativefrom = $matches['userid'][$i];
                    $likeinteraction->fromid = $this->socialusercache->get_userid($likeinteraction->nativefrom);
                    $likeinteraction->nativeto = $interaction->nativefrom;
                    $likeinteraction->toid = $interaction->fromid;
                    $likeinteraction->nativetoname = $interaction->nativefromname;
                    $likeinteraction->nativefromname = $matches['screenname'][$i];
                    $likeinteraction->description = $likeinteraction->nativefromname . ' liked tweet ' . $interaction->uid;
                    $likeinteraction->parentinteraction = $interaction->uid;
                    $likeinteraction->uid = $interaction->uid . '-likedby-' . $likeinteraction->nativefrom;
                    $likeinteraction->timestamp = $interaction->timestamp;
                    $likeinteraction->nativetype = 'fav';
                    $likeinteraction->type = social_interaction::REACTION;
                    $likeinteractions[$likeinteraction->uid] = $likeinteraction;
                }
            }
        }
        return $likeinteractions;
    }
    /**
     * Search for new Likes.
     * @param social_interaction[] $targetinteractions
     */
    protected function refresh_retweets($targetinteractions) {
        $retweetinteractions = [];
        $interactions = array_filter($targetinteractions, function(social_interaction $inter) {
            return ($inter->type == social_interaction::POST);
        });
          
            mtrace("<li>Checking ". count($interactions) . " tweets for Retweets.");
            foreach ($interactions as $interaction) {
                if ($interaction->type == social_interaction::POST) {
                    $status = json_decode($interaction->rawdata);
                    if ($status->retweeted == false) {
                        continue;
                    }
                    mtrace("<li>Getting retweets for " . $this->plugin->get_interaction_url($interaction));
                    $popupcode = $this->browse_twitter('https://twitter.com/i/activity/retweeted_popup?id=' . $interaction->uid);
                    $json = json_decode($popupcode);
                    if (isset($json->htmlUsers)) {
                        $users = $json->htmlUsers;
                    } else {
                        continue; // Tweet deleted or account made private.
                    }
                    $matches = [];
                    preg_match_all('/screen-name="(?\'screenname\'[\w\s]+)"\s+data-user-id="(?\'userid\'\d+)"/', $users, $matches, PREG_PATTERN_ORDER);
                    $count = count($matches[1]);
                    if ($count == 0) {
                        continue;
                    }
                    mtrace("<li>Tweet " . $this->plugin->get_interaction_url($interaction) . " has $count retweets.");
                    for ($i = 0; $i < $count; $i++) {
                        // Create a new Retweet interaction.
                        $retweetinteraction = new social_interaction();
                        $retweetinteraction->source = $this->plugin->get_subtype();
                        $retweetinteraction->nativefrom = $matches['userid'][$i];
                        $retweetinteraction->fromid = $this->socialusercache->get_userid($retweetinteraction->nativefrom);
                        $retweetinteraction->nativeto = $interaction->nativefrom;
                        $retweetinteraction->toid = $interaction->fromid;
                        $retweetinteraction->nativetoname = $interaction->nativefromname;
                        $retweetinteraction->nativefromname = $matches['screenname'][$i];
                        $retweetinteraction->description = $retweetinteraction->nativefromname . ' retweeted tweet ' . $interaction->uid;
                        $retweetinteraction->parentinteraction = $interaction->uid;
                        $retweetinteraction->uid = $interaction->uid . '-retweetedby-' . $retweetinteraction->nativefrom;
                        $retweetinteraction->timestamp = $interaction->timestamp;
                        $retweetinteraction->nativetype = 'retweet';
                        $retweetinteraction->type = social_interaction::MENTION;
                        $retweetinteractions[$retweetinteraction->uid] = $retweetinteraction;
                    }
                }
            }
            return $retweetinteractions;
    }
    protected function build_interactions($statuses) {
        $interactions = [];
        foreach ($statuses as $status) {
            $interaction = new social_interaction();
            $interaction->uid = $status->id_str;
            $interaction->rawdata = json_encode($status);
            $interaction->source = $this->plugin->get_subtype();
            $interaction->nativetype = 'tweet';
            $interaction->nativefrom = $status->user->id_str;
            $interaction->nativefromname = $status->user->screen_name;
            $interaction->fromid = $this->socialusercache->get_userid($interaction->nativefrom);
            $interaction->nativeto = $status->in_reply_to_user_id_str;
            
            $interaction->parentinteraction = $status->in_reply_to_status_id_str;
            $interaction->timestamp = new \DateTime($status->created_at);
            $interaction->description = $status->full_text;
            // Twitter threads are autoreplies. Consider them as POSTS.
            if ($interaction->nativeto == ""
                || $interaction->nativeto === $interaction->nativefrom ) {
                    $interaction->type = social_interaction::POST;
                    $interaction->nativeto = "";
                } else {
                    $interaction->type = social_interaction::REPLY;
                    $interaction->nativetoname = $status->in_reply_to_screen_name;
                    $interaction->toid = $this->socialusercache->get_userid($interaction->nativeto);
                }
                $interactions[$interaction->uid] = $interaction;
                // Process mentions...
                foreach ($status->entities->user_mentions as $mentionstatus) {
                    $mentioninteraction = new social_interaction();
                    $mentioninteraction->rawdata = json_encode($mentionstatus);
                    $mentioninteraction->source = $this->plugin->get_subtype();
                    $mentioninteraction->nativetype = 'mention';
                    $mentioninteraction->toid = $this->socialusercache->get_userid($mentionstatus->id_str);
                    $mentioninteraction->nativeto = $mentionstatus->id_str;
                    $mentioninteraction->nativetoname = $mentionstatus->screen_name;
                    $mentioninteraction->timestamp = new \DateTime($status->created_at);
                    $mentioninteraction->type = social_interaction::MENTION;
                    $mentioninteraction->nativefrom = $interaction->nativefrom;
                    $mentioninteraction->fromid = $interaction->fromid;
                    $mentioninteraction->nativefromname = $interaction->nativefromname;
                    $mentioninteraction->description = 'Mentioned by @' . $mentionstatus->id_str . " ($mentionstatus->name)";
                    $mentioninteraction->uid = $interaction->uid . '-' . $mentionstatus->id_str;
                    $mentioninteraction->parentinteraction = $interaction->uid;
                    $interactions[$mentioninteraction->uid] = $mentioninteraction;
                }
                
        }
        return $interactions;
    }
    /**
     * @global moodle_database $DB
     * @return mixed $result->statuses $result->messages[]string $result->errors[]->message */
    protected function harvest_hashtags($token, $hashtag, $usersstruct) {
        global $DB;
        
        $result = $this->get_statuses($token, $hashtag);
	$result->interactions = [];

        if (isset($result->errors)) {
            if ($token) {
                $info = "UserToken for:$token->username ";
            } else {
                $info = "No twitter token defined!!";
            }
            $errormessage = $result->errors[0]->message;
            $msocial = $this->msocial; 
            $cm = $this->plugin->get_cmid();
            $result->messages[] = "Searching: $hashtag. For module msocial\connector\twitter by hashtag: $msocial->name (id=$cm) " .
            " in course (id=$msocial->course) $info ERROR:" . $errormessage;
            $result->error[] = (object) ['message' => $errormessage];
            $result->statuses = [];
        } else if (isset($result->statuses)) {
            $DB->set_field('msocial_twitter_tokens', 'errorstatus', null, array('id' => $token->id));
            
            $statuses = count($result->statuses) == 0 ? array() : $result->statuses;
            $msocial = $this->msocial;
            
            $processedstatuses = $this->process_statuses($statuses, $this->msocial, $usersstruct);
            $studentstatuses = array_filter($processedstatuses,
                function ($status) {
                    return isset($status->userauthor);
                });
            $this->store_status($processedstatuses);
            $interactions = $this->build_interactions($processedstatuses);
            $result->interactions = $interactions;
            $errormessage = null;
            $result->errors = [];
            $result->messages[] = "Searching by hashtag: $hashtag. For module msocial\\connector\\twitter by hashtags: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) ";
            $result->statuses = [];
        } else {
            $msocial = $this->msocial;
            $errormessage = "ERROR querying twitter results null! Maybe there is no twiter account linked in this activity.";
            $result->errors[] = (object) ['message' => $errormessage];
            $result->messages[] = "Searching: $hashtag. For module msocial\\connector\\twitter by hashtags: $msocial->name (id=$msocial->id) " .
            "in course (id=$msocial->course) " . $errormessage;
            $result->statuses = [];
        }
        if ($token) {
            $token->errorstatus = $errormessage;
            $DB->update_record('msocial_twitter_tokens', $token);
            if ($errormessage) { // Marks this tokens as erroneous to warn the teacher.
                $message = "Updating token with id = $token->id with $errormessage";
                $result->errors[] = (object) ['message' => $message];
                $result->messages[] = $message;
                $result->statuses = [];
            }
        }
        return $result;
    }
    
    private function browse_twitter($geturl) {
        $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0';
        $options = array(
            CURLOPT_RETURNTRANSFER => true, // to return web page
            CURLOPT_FOLLOWLOCATION => true, // to follow redirects
            CURLOPT_ENCODING       => "",   // to handle all encodings
            CURLOPT_AUTOREFERER    => true, // to set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,  // set a timeout on connect
            CURLOPT_TIMEOUT        => 120,  // set a timeout on response
            CURLOPT_MAXREDIRS      => 10,   // to stop after 10 redirects
            CURLINFO_HEADER_OUT    => true, // no header out
            CURLOPT_SSL_VERIFYPEER => false,// to disable SSL Cert checks
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT      => $agent,
        );
        $ch = curl_init($geturl);
        curl_setopt_array( $ch, $options );
        $popupcode = curl_exec($ch);
        curl_close($ch);
        return $popupcode;
    }
    
    /**
     * @deprecated
     * @param \stdClass $user
     * @return array
     */
    protected function load_statuses($user = null) {
        global $DB;
        $condition = ['msocial' => $this->msocial->id];
        if ($user) {
            $condition['userid'] = $user->id;
        }
        $statuses = $DB->get_records('msocial_tweets', $condition);
        return $statuses;
    }
    
    /**
     * @todo Get a list of interactions between the users
     * @global moodle_database $DB
     * @param integer $fromdate null|starting time
     * @param integer $todate null|end time
     * @param array $users filter of users
     * @return \mod_msocial\connector\social_interaction[] of interactions.
     * @see \mod_msocial\connector\social_interaction 
     * @deprecated
     */
    public function get_interactions($fromdate = null, $todate = null, $users = null) {
        global $DB;
        $tweets = $DB->get_records('msocial_tweets', ["msocial" => $this->msocial->id], 'tweetid');
        $interactions = $this->build_interactions($tweets);
        return $interactions;
    }
   
    /**
     * Merge arrays preserving keys. (PHP may convert string to int and renumber the items).
     */
    protected function merge_interactions($arr1, $arr2) {
        $merged = [];
        if ($arr1) {
            foreach ($arr1 as $key => $inter) {
                $merged[$key] = $inter;
            }
        }
        if ($arr2) {
            foreach ($arr2 as $key => $inter) {
                $merged[$key] = $inter;
            }
        }
        return $merged;
    }
}

