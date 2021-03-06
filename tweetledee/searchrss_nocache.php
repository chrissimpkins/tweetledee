<?php

/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   searchrss_nocache.php -- Tweet search query results formatted as RSS feed
 * Copyright 2014 Christopher Simpkins
 * MIT License
 ************************************************************************************************/

/*-----------------------------------------------------------------------------------------------
==> Instructions:
    - place the tweetledee directory in the public facing directory on your web server (frequently public_html)
    - Generic tweet search RSS feed URL (count = 25):
            e.g. http://<yourdomain>/tweetledee/searchrss_nocache.php?q=<search-term>
==> Twitter Tweet Search RSS feed parameters:
    - 'c'   - specify a tweet count (range 1 - 200, default = 25)
            e.g. http://<yourdomain>/tweetledee/searchrss_nocache.php?q=<search-term>&c=100
    - 'rt'  - result type (possible values: mixed, recent, popular; default = mixed)
            e.g. http://<yourdomain>/tweetledee/searchrss_nocache.php?q=<search-term>&rt=recent
    - 'q'   - query term
             e.g. http://<yourdomain>/tweetledee/searchrss_nocache.php?q=coolsearch
    - 'recursion_limit' - When a tweet is a reply, specifies the maximum number of "parents" tweets to load (default = 0).
                        A value of 10 can be used without significative performance cost on Raspberry 3.
                        This can be short-handed to 'rl'
    - Example of all parameters
            http://<yourdomain>/tweetledee/searchrss_nocache.php?q=coolsearch&c=50&rt=recent&rl=10
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

$parameters = load_parameters([
    "c",
    "query",
    "rt",
    "recursion_limit"
]);
extract($parameters);
if (!isset($query)) {
    die("Error: missing the search query term.  Please use the 'q' parameter.");
}

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

/*******************************************************************
 *  Defaults
 ********************************************************************/
$count = 25;  //default tweet number = 25
$result_type = 'mixed'; //default to mixed popular and realtime results
$recursion_limit = 0; // as a default we don't quote tweets

$parameters = load_parameters([
    "c",
    "query",
    "rt"
]);
extract($parameters);
if (!isset($query)) {
    die("Error: missing the search query term.  Please use the 'q' parameter.");
}

//Create the feed title with the query
$feedTitle = 'Twitter search for "' . $query . '"';

// URL encode the search query
//$urlquery = urlencode($query);

/*******************************************************************
 *  Request
 ********************************************************************/
$code = $tmhOAuth->user_request([
    'url' => $tmhOAuth->url('1.1/search/tweets'),
    'params' => [
        'include_entities' => true,
        'count' => $count,
        'result_type' => $result_type,
        'q' => $query,
    ]
]);

// Anything except code 200 is a failure to get the information
if ($code <> 200) {
    echo $tmhOAuth->response['error'];
    echo "HTTP Status Code: $code";
    echo " ";
    die("tweet search failure");
}

//concatenate the URL for the atom href link
if (defined('STDIN')) {
    $thequery = $_SERVER['PHP_SELF'];
} else {
    $thequery = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}

$searchResultsObj = json_decode($tmhOAuth->response['response'], true);

// Start the output
header("Content-Type: application/rss+xml");
header("Content-type: text/xml; charset=utf-8");

$renderer = new RssRenderer($recursion_limit);
$renderer->using_client($tmhOAuth);
$config = [
    'atom'              =>  $my_domain . urlencode($thequery),
    'link'               =>  sprintf('http://www.twitter.com/search/?q=%s', $query),
    'lastBuildDate'     =>  date(DATE_RSS),
    'title'             =>  $feedTitle,
    'description'       =>  sprintf(
        'A Twitter search for the query "%s" with the %s search result type',
        $query,
        $result_type
    ),
    'twitterAvatarUrl'  =>  $twitterAvatarUrl,
];
echo $renderer->render_feed($config, $searchResultsObj['statuses']);
