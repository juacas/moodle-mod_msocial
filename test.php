<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('TwitterAPIExchange.php');

/** Set access tokens here - see: https://dev.twitter.com/apps/ **/
$settings = array(
    'oauth_access_token' => "255700071-aawZtTtbqMX8XfcpLJVdvW7EmlOkJlGril9c8aof",
    'oauth_access_token_secret' => "SzS8PvIcnVi5HowdvZGG7kukIaMDmrmUOmLyfbAcrHmzy",
    'consumer_key' => "wT08DqV9NPacunLmxvZvd5JI8",
    'consumer_secret' => "Vb1lKLqYMnF0KtCnjRH4xFuZhNC2zy9YmssBM3wgX0PDkn9iII"
);
/** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/

/** Perform the request and echo the response **/
$url = 'https://api.twitter.com/1.1/search/tweets.json';
$getfield = '?q=#inmuva';
$requestMethod = 'GET';
$twitter = new TwitterAPIExchange($settings);
$json =  $twitter->set_getfield($getfield)
        ->buildOauth($url, $requestMethod)
        ->perform_request();

$result = json_decode($json);

foreach ($result->statuses as $status)
{
    echo $status;
}