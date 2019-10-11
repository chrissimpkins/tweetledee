<?php
/***********************************************************************************************
 * Tweetledee  - Incredibly easy access to Twitter data
 *   searchrss.php -- Tweet search query results formatted as RSS feed
 *   Version: 0.5.0
 * Copyright 2014 Christopher Simpkins
 * MIT License
 ************************************************************************************************/
/*-----------------------------------------------------------------------------------------------
==> Instructions:
    - place the tweetledee directory in the public facing directory on your web server (frequently public_html)
    - Generic tweet search RSS feed URL (count = 25):
            e.g. http://<yourdomain>/tweetledee/searchrss.php?q=<search-term>
==> Twitter Tweet Search RSS feed parameters:
    - 'c'   - specify a tweet count (range 1 - 200, default = 25)
            e.g. http://<yourdomain>/tweetledee/searchrss.php?q=<search-term>&c=100
    - 'rt'  - result type (possible values: mixed, recent, popular; default = mixed)
            e.g. http://<yourdomain>/tweetledee/searchrss.php?q=<search-term>&rt=recent
    - 'q'   - query term
             e.g. http://<yourdomain>/tweetledee/searchrss.php?q=coolsearch
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

require 'tldlib/renderers/rss.php';


/***************************************************************************************
*  Mandatory parameter (q)
*   - do not execute the OAuth authentication request if missing (keep before OAuth code)
****************************************************************************************/
// q = search query term
if (isset($_GET["q"])){
    $query = $_GET["q"];
}
else if (defined('STDIN')) {
    if (isset($argv)){
        $shortopts = "q:";
    }
    else {
        die("Error: missing the search query term in your request.  Please use the 'q' parameter in your request.");
    }
    $params = getopt($shortopts);
    if (isset($params['q'])){
        $query = urlencode($params['q']);
    }
    else{
        die("Error: unable to parse the search query term in your request.  Please use the 'q' parameter in your request.");
    }
}
else{
    die("Error: missing search query term in your request.  Please use the 'q' parameter in your request.");
}

/*******************************************************************
*  Defaults
********************************************************************/
$count = 25;  //default tweet number = 25
$result_type = 'mixed'; //default to mixed popular and realtime results
$cache_interval = 90; // default cache interval = 90 seconds

/*******************************************************************
*   Optional Parameters
*    - can pass via URL to web server
*    - or as a short or long switch at the command line
********************************************************************/

// Command line parameter definitions //
if (defined('STDIN')) {
    // check whether arguments were passed, if not there is no need to attempt to check the array
    if (isset($argv)){
        $shortopts = "c:";
        $longopts = array(
            "rt",
        );
        $params = getopt($shortopts, $longopts);
        if (isset($params['c'])){
            if ($params['c'] > 0 && $params['c'] <= 200)
                $count = $params['c'];  //assign to the count variable
        }
        if (isset($params['rt'])){
            $result_type = $params['rt'];
        }
        if (isset($params['cache_interval'])){
            $cache_interval = $params['cache_interval'];
        }
    }
}
// Web server URL parameter definitions //
else{
    // c = tweet count ( possible range 1 - 200 tweets, else default = 25)
    if (isset($_GET["c"])){
        if ($_GET["c"] > 0 && $_GET["c"] <= 200){
            $count = $_GET["c"];
        }
    }
    // rt = response type
    if (isset($_GET["rt"])){
        if ($_GET["rt"] == 'popular' || $_GET["rt"] == 'recent'){
            $result_type = $_GET["rt"];
        }
    }
    // cache_interval = the amount of time to keep the cached file
    if (isset($_GET["cache_interval"])){
        $cache_interval = $_GET["cache_interval"];
    }
}

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

//Create the feed title with the query
$feedTitle = 'Twitter search for "' . $query . '"';

// URL encode the search query
//$urlquery = urlencode($query);

/*******************************************************************
*  Request
********************************************************************/
$searchResultsObj = $tldCache->user_request(array(
			'url' => '1.1/search/tweets',
			'params' => array(
          		'include_entities' => true,
    			'count' => $count,
                'result_type' => $result_type,
                'q' => $query,
        	)
        ));

//concatenate the URL for the atom href link
if (defined('STDIN')) {
    $thequery = $_SERVER['PHP_SELF'];
} else {
    $thequery = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
}

// Start the output
header("Content-Type: application/rss+xml");
header("Content-type: text/xml; charset=utf-8");

$renderer = new RssRenderer();
$config = array(
    'atom'              =>  $my_domain . urlencode($thequery),
    'link'               =>  sprintf('http://www.twitter.com/search/?q=%s', $query),
    'lastBuildDate'     =>  date(DATE_RSS),
    'title'             =>  $feedTitle,
    'description'       =>  sprintf('A Twitter search for the query "%s" with the %s search result type', $query, $result_type),
    'twitterAvatarUrl'  =>  $twitterAvatarUrl,
);
?>
<?php echo $renderer->render_feed($config, $searchResultsObj['statuses'])?>
