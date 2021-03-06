<?php

/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   homejson_pp.php -- Home timeline results formatted as pretty printed JSON
 * Copyright 2014 Christopher Simpkins
 * MIT License
 ************************************************************************************************/

/*-----------------------------------------------------------------------------------------------
==> Instructions:
    - place the tweetledee directory in the public facing directory on your web server (frequently public_html)
    - Access the default home timeline JSON (count = 25 & includes replies) at the following URL:
            e.g. http://<yourdomain>/tweetledee/homejson_pp.php
==> User's Home Timeline Pretty Printed JSON parameters:
    - 'c' - specify a tweet count (range 1 - 200, default = 25)
            e.g. http://<yourdomain>/tweetledee/homejson_pp.php?c=100
    - 'xrp' - exclude replies (1=true, default = false)
            e.g. http://<yourdomain>/tweetledee/homejson_pp.php?xrp=1
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

// include Martín Lucas Golini's pretty print functions
require 'tldlib/tldPrettyPrint.php';

require 'tldlib/parametersProcessing.php';

$parameters = load_parameters(["c", "exclude_replies", "cache_interval"]);
extract($parameters);

/*******************************************************************
 *  OAuth
 ********************************************************************/

$tldCache = new tldCache([
        'consumer_key'        => $my_consumer_key,
        'consumer_secret'     => $my_consumer_secret,
        'user_token'          => $my_access_token,
        'user_secret'         => $my_access_token_secret,
        'curl_ssl_verifypeer' => false
], $cache_interval);

// request the user information
$data = $tldCache->auth_request();

// Parse information from response
$twitterName = $data['screen_name'];
$fullName = $data['name'];
$twitterAvatarUrl = $data['profile_image_url_https'];
$feedTitle = ' Twitter home timeline for ' . $twitterName;
$screen_name = $data['screen_name'];


/*******************************************************************
 *  Request
 ********************************************************************/
$homeTimelineObj = $tldCache->user_request([
        'url' => '1.1/statuses/home_timeline',
        'params' => [
                'include_entities' => true,
                'count' => $count,
                'exclude_replies' => $exclude_replies,
        ]
]);

header('Content-Type: application/json');
echo json_encode_pretty_print($homeTimelineObj);
