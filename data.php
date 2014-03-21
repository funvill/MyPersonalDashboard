<?php 

require_once('settings.php');

// Script set up.
date_default_timezone_set('UTC');

// Helper functions 
function GetURL( $url ) {
	echo $url ."\n"; 
	// Get the 
	$json = file_get_contents( $url );	

	// Save the contents of the file to disk for debug
	file_put_contents( 'request.json', $json ); 

	// return the results. 
	return json_decode($json, true); 
}

function days_in_month($year, $month) { 
    return( date( "t", mktime( 0, 0, 0, $month, 1, $year) ) ); 
}

// Connect and set up the database.
$dbhandle = new SQLite3( $settings['database']['file'] ); 

// Create tables if they do not exist 
$results = @$dbhandle->query('CREATE TABLE IF NOT EXISTS twitter (datestring char(255), followers_count int, friends_count int,favourites_count int,statuses_count int, PRIMARY KEY (datestring))'); 
$results = @$dbhandle->query('CREATE TABLE IF NOT EXISTS last_fm (datestring char(255), total int, PRIMARY KEY (datestring))'); 
$results = @$dbhandle->query('CREATE TABLE IF NOT EXISTS github  (datestring char(255), public_repos int, public_gists int, followers int, following int, PRIMARY KEY (datestring))'); 


// Twitter
// -------------------------------------------------
require_once('TwitterAPIExchange.php')	;
$twitter_settings = array(
    'oauth_access_token' 		=> $settings['twitter']['access_token'],
    'oauth_access_token_secret' => $settings['twitter']['access_token_secret'],
    'consumer_key' 				=> $settings['twitter']['consumer_key'],
    'consumer_secret' 			=> $settings['twitter']['consumer_secret']
);

$twitter = new TwitterAPIExchange($twitter_settings);
$response =$twitter->setGetfield( '?screen_name='. $settings['twitter']['user'] )
             ->buildOauth($settings['twitter']['url'], 'GET')
             ->performRequest();  

$results = json_decode($response, true ); 
if( isset( $results[0]['followers_count'] )  && 
	isset( $results[0]['friends_count'] ) 	 && 
	isset( $results[0]['favourites_count'] ) && 
	isset( $results[0]['statuses_count'] ) ) 
{
	// We have the fields that we need. 
	$results = @$dbhandle->query('INSERT OR REPLACE INTO twitter (datestring, followers_count, friends_count, favourites_count, statuses_count ) VALUES ( "'. date('Y-m-d') .'", "'.$results[0]['followers_count'] .'", "'. $results[0]['friends_count'] .'", "'. $results[0]['favourites_count'] .'", "'. $results[0]['statuses_count'] .'")');
}

// Git Hub 
// -------------------------------------------------
/*
// ToDo: change the GetURL function to work with http[S] requests. 
$response = GetURL( $settings['github']['url']. $settings['github']['user'] ); 
$results = json_decode($response, true ); 
if( isset( $results['public_repos'] ) && isset( $results['public_gists'] ) && isset( $results['followers'] ) && isset( $results['following'] ) ) {
	$results = @$dbhandle->query('INSERT OR REPLACE INTO github (datestring, public_repos, public_gists, followers, following ) VALUES ( "'. date('Y-m-d') .'", "'.$results['public_repos'] .'", "'. $results['public_gists'] .'", "'. $results['followers'] .'", "'. $results['following'] .'")');
}
*/




// Last FM
// -------------------------------------------------
$current_time = time(); 
$num_of_requests = 0 ; 


// Check the database to see if what values from the last year that we have already gotten
$old_datestring = array() ; 
$sql = 'SELECT datestring FROM last_fm WHERE datestring >= "'. sprintf('%04d-%02d-%02d', date('Y')-1,1,1) .'" AND datestring <= "'. sprintf('%04d-%02d-%02d', date('Y'),31,12) .'" ;' ;
$results = $dbhandle->query( $sql );	
while( $row = $results->fetchArray() ) {
	$old_datestring[] = $row['datestring'] ; 
}

// Only scan this last years worth of Last.fm data. 
for( $year = date('Y')-1 ; $year < date('Y') ; $year++ ) {
	for( $month = 1 ; $month < 12 ; $month++ ) {
		$max_days = days_in_month($year, $month ); 
		for( $day = 1 ; $day <= $max_days ; $day++) {
			if( $num_of_requests > 30 ) {
				break; 
			}

			// Create the date string. 
			$start = mktime (0,0,0, $month, $day, $year );
			$end   = mktime (0,0,0, $month, $day+1, $year );
			$datestring = sprintf('%04d-%02d-%02d', $year,$month,$day);

			// Check for current date. 
			if( $current_time < $end ) {
				// Don't poll todays date as we might get bad data if we listen to more songs today. 
				break; 
			}

			if (in_array($datestring, $old_datestring)) {
				continue; // Already got this point.
			}

			echo $datestring .'= ';
			$url = $settings['lastfm']['url'].'?method=user.getrecenttracks&user='.$settings['lastfm']['user'] .'&api_key='. $settings['lastfm']['api_key'] .'&format=json&from='.$start.'&to='.$end.'&extended=0&limit=1';
			$results = GetURL( $url ) ; 
			$num_of_requests++; 

			// Check to see if we recived an error. If we do then use the total of zero as a value. 
			$total = 0 ; 
			if( isset( $results['recenttracks']['@attr']['total'] ) ) {
				$total = $results['recenttracks']['@attr']['total'] ; 
			}


			// Update the database 
			$results = @$dbhandle->query('INSERT INTO last_fm (datestring, total) VALUES ( "'. $datestring .'", "'. $total .'")');
		}
	}
}
?>