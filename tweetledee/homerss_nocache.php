<?php

/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   homerss_nocache.php -- Home timeline results formatted as RSS feed
 * Copyright 2014 Christopher Simpkins
 * MIT License
 ************************************************************************************************/

/*-----------------------------------------------------------------------------------------------
==> Instructions:
    - place the tweetledee directory in the public facing directory on your web server (frequently public_html)
    - Access the default home timeline feed (count = 25, includes both RT's & replies) at the following URL:
            e.g. http://<yourdomain>/tweetledee/homerss_nocache.php
==> User's Home Timeline RSS feed parameters:
    - 'c' - specify a tweet count (range 1 - 200, default = 25)
            e.g. http://<yourdomain>/tweetledee/homerss_nocache.php?c=100
    - 'xrp' - exclude replies (1=true, default = false)
            e.g. http://<yourdomain>/tweetledee/homerss_nocache.php?xrp=1
    - 'recursion_limit' - When a tweet is a reply, specifies the maximum number of "parents" tweets to load (default = 0).
                        A value of 10 can be used without significative performance cost on Raspberry 3.
                        This can be short-handed to 'rl'
    - Example of all of the available parameters:
            e.g. http://<yourdomain>/tweetledee/homerss_nocache.php?c=100&xrp=1&rl=10
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

require 'tldlib/renderers/rss.php';

require 'tldlib/parametersProcessing.php';

$parameters = load_parameters(["c", "exclude_replies"]);
$parameters = load_parameters([
    "c",
    "exclude_replies",
    "recursion_limit"
]);
extract($parameters);

/*******************************************************************
 *  OAuth
 ********************************************************************/
$tmhOAuth = new tmhOAuth([
    'consumer_key'        => $my_consumer_key,
    'consumer_secret'     => $my_consumer_secret,
    'user_token'          => $my_access_token,
    'user_secret'         => $my_access_token_secret,
    'curl_ssl_verifypeer' => false
]);

// request the user information
$code = $tmhOAuth->user_request([
    'url' => $tmhOAuth->url('1.1/account/verify_credentials')
]);

// Display error response if do not receive 200 response code
if ($code <> 200) {
    if ($code == 429) {
        die("Exceeded Twitter API rate limit");
    }
    echo $tmhOAuth->response['error'];
    die("verify_credentials connection failure");
}

// Decode JSON
$data = json_decode($tmhOAuth->response['response'], true);

// Parse information from response
$twitterName = $data['screen_name'];
$fullName = $data['name'];
$twitterAvatarUrl = $data['profile_image_url_https'];
$feedTitle = ' Twitter home timeline for ' . $twitterName;

/*******************************************************************
 *  Request
 ********************************************************************/
$code = $tmhOAuth->user_request([
    'url' => $tmhOAuth->url('1.1/statuses/home_timeline'),
    'params' => [
        'include_entities' => true,
        'count' => $count,
        'exclude_replies' => $exclude_replies,
    ]
]);

// Anything except code 200 is a failure to get the information
if ($code <> 200) {
    echo $tmhOAuth->response['error'];
    die("home_timeline connection failure");
}

$homeTimelineObj = json_decode($tmhOAuth->response['response'], true);

//headers
header("Content-Type: application/rss+xml");
header("Content-type: text/xml; charset=utf-8");

// Start the output

$renderer = new RssRenderer($recursion_limit);
$renderer->using_client($tmhOAuth);
$config = [
    'atom'              =>  $my_domain . $_SERVER['PHP_SELF'],
    'link'              =>  sprintf('http://www.twitter.com/%s', $twitterName),
    'twitterName'       => $twitterName,
    'lastBuildDate'     =>  date(DATE_RSS),
    'title'             =>  $feedTitle,
    'description'       =>  sprintf('Twitter home timeline updates for %s/%s', $fullName, $twitterName),
    'twitterAvatarUrl'  =>  $twitterAvatarUrl
];
echo $renderer->render_feed($config, $homeTimelineObj);
