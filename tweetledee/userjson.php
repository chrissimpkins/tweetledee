<?php
/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   userjson.php -- User timeline results formatted as JSON
 *   Version: 0.5.0
 * Copyright 2014 Christopher Simpkins
 * MIT License
 ************************************************************************************************/
/*-----------------------------------------------------------------------------------------------
==> Instructions:
    - place the tweetledee directory in the public facing directory on your web server (frequently public_html)
    - Access the default user timeline JSON (count = 25, includes both RT's & replies) at the following URL:
            e.g. http://<yourdomain>/tweetledee/userjson.php
==> User Timeline JSON parameters:
    - 'c' - specify a tweet count (range 1 - 200, default = 25)
            e.g. http://<yourdomain>/tweetledee/userjson.php?c=100
    - 'user' - specify the Twitter user whose timeline you would like to retrieve (default = account associated with access token)
            e.g. http://<yourdomain>/tweetledee/userjson.php?user=cooluser
    - 'xrt' - exclude retweets (1=true, default = false)
            e.g. http://<yourdomain>/tweetledee/userjson.php?xrt=1
    - 'xrp' - exclude replies (1=true, default = false)
            e.g. http://<yourdomain>/tweetledee/userjson.php?xrp=1
    - 'cache_interval' - specify the duration of the cache interval in seconds (default = 90sec)
--------------------------------------------------------------------------------------------------*/
/*******************************************************************
*  Includes
********************************************************************/
require 'tldlib/debug.php';
// Matt Harris' Twitter OAuth library
require 'tldlib/tmhOAuth.php';
require 'tldlib/tmhUtilities.php';

// include user keys
require 'tldlib/keys/tweetledee_keys.php';

// include Geoff Smith's utility functions
require 'tldlib/tldUtilities.php';

// include Christian Varga's twitter cache
require 'tldlib/tldCache.php';

require 'tldlib/parametersProcessing.php';

$parameters = load_parameters(array("c", "exclude_retweets", "exclude_replies", "user", "cache_interval"));
extract($parameters);
if(!isset($parameters['screen_name'])) {
    $screen_name = $data['screen_name'];
}
$include_retweets = !$exclude_retweets;

/*******************************************************************
*  OAuth
********************************************************************/

$tldCache = new tldCache(array(
            'consumer_key'        => $my_consumer_key,
            'consumer_secret'     => $my_consumer_secret,
            'user_token'          => $my_access_token,
            'user_secret'         => $my_access_token_secret,
            'curl_ssl_verifypeer' => false
        ), $cache_interval);

// request the user information
$data = $tldCache->auth_request();

// Parse information from response
$twitterName = $data['screen_name'];
$fullName = $data['name'];
$twitterAvatarUrl = $data['profile_image_url'];

if ( $screen_name == '' ) $screen_name = $data['screen_name'];

/*******************************************************************
*  Request
********************************************************************/

$userTimelineObj = $tldCache->user_request(array(
            'url' => '1.1/statuses/user_timeline',
            'params' => array(
                'include_entities' => true,
                'count' => $count,
                'exclude_replies' => $exclude_replies,
                'include_rts' => $include_retweets,
                'screen_name' => $screen_name,
            )
        ));

header('Content-Type: application/json');
echo json_encode($userTimelineObj);

