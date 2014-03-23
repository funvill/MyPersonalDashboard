<pre><?php 
/**
 * My Personal Dashboard 
 * 
 * There are two types of data sources. Ones that we have to request every day because they 
 * only show the current values and the other one that you can query historal values (LastFM, FourSquare). 
 * 
 *
 */
require_once('settings.php');

// Script set up.
date_default_timezone_set('UTC');

// Helper functions 
function GetURL($url) {
	global $settings;

	if( $settings['debug'] ) { 
		echo $url ."\n"; 
	}

	if( ! function_exists("curl_init")   || 
		! function_exists("curl_setopt") || 
		! function_exists("curl_exec")   || 
		! function_exists("curl_close") ) 
	{
		echo "Error: cURL Basic Functions UNAVAILABLE" ; 
		return false ; 
	}

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 				$url);
    curl_setopt($ch, CURLOPT_USERAGENT, 		$settings['useragent']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 	FALSE);    
    // curl_setopt($ch, CURLOPT_HEADER, 			false);
    // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 	true);
    // curl_setopt($ch, CURLOPT_REFERER, 			$url);
    $result = curl_exec($ch);
    if( ! $result ) {
    	echo 'Curl error: ' . curl_error($ch);
    	curl_close($ch);
    	return false; 
    }
    curl_close($ch);

	// Save the contents of the file to disk for debug
	file_put_contents( 'request.json', $result ); 
	
	// return the results. 
	return $result ; 
}

// Helper functions 


function days_in_month($year, $month) { 
    return( date( "t", mktime( 0, 0, 0, $month, 1, $year) ) ); 
}

// Connect and set up the database.
$dbhandle = new SQLite3( $settings['database']['file'] ); 

// Create tables if they do not exist 
$results = $dbhandle->query('CREATE TABLE IF NOT EXISTS twitter (datestring char(255), followers_count int, friends_count int,favourites_count int,statuses_count int, PRIMARY KEY (datestring))'); 
$results = $dbhandle->query('CREATE TABLE IF NOT EXISTS last_fm (datestring char(255), total int, PRIMARY KEY (datestring))'); 
$results = $dbhandle->query('CREATE TABLE IF NOT EXISTS github  (datestring char(255), public_repos int, public_gists int, followers int, following int, contributions int, PRIMARY KEY (datestring))'); 
// $results = @$dbhandle->query('ALTER TABLE github ADD COLUMN contributions int;');
$results = $dbhandle->query('CREATE TABLE IF NOT EXISTS foursquare (datestring char(255), checkins int, PRIMARY KEY (datestring))'); 
$results = $dbhandle->query('CREATE TABLE IF NOT EXISTS moves (datestring char(255), duration int, distance int, steps int, PRIMARY KEY (datestring))'); 

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
// Contributions
// These values are not exposed in the API instead we get them from the user page.  
// We need to do this github request first to fill out the table before we update the table with the other stats 
// https://api.github.com/users/funvill/contributions_calendar_data
$response = GetURL( $settings['github']['www']. 'users/'. $settings['github']['user'] .'/contributions_calendar_data' ); 
$results = json_decode($response, true); 
foreach( $results as $row ) {
	$sql_results = $dbhandle->query('INSERT OR REPLACE INTO github (datestring, contributions ) VALUES ( "'. str_replace('/', '-', $row[0] ) .'", "' .$row[1]. '")');
}

$response = GetURL( $settings['github']['url']. 'users/'. $settings['github']['user'] ); 
$results = json_decode($response, true); 
if( isset( $results['public_repos'] ) && isset( $results['public_gists'] ) && isset( $results['followers'] ) && isset( $results['following'] ) ) {
	$sql_results = $dbhandle->query('UPDATE github SET public_repos="'.$results['public_repos'] .'", public_gists="'. $results['public_gists'] .'", followers="'. $results['followers'] .'", following="'. $results['following'] .'" WHERE datestring = "'. date('Y-m-d') .'"; ');
}


// Moves 
// -------------------------------------------------
// ToDo: 

// Four Square 
// -------------------------------------------------
$current_time = time(); 
$num_of_requests = 0 ; 

// Check the database to see if what values from the last year that we have already gotten
$foursquare_existing_recoreds = array() ; 
$sql = 'SELECT datestring FROM foursquare WHERE datestring >= "'. sprintf('%04d-%02d-%02d', date('Y')-1,1,1) .'" AND datestring <= "'. sprintf('%04d-%02d-%02d', date('Y'),31,12) .'" ;' ;
$results = $dbhandle->query( $sql );	
while( $row = $results->fetchArray() ) {
	$foursquare_existing_recoreds[] = $row['datestring'] ; 
}


// Only scan this last years worth of Last.fm data. 
for( $year = date('Y') ; $year <= date('Y') ; $year++ ) {
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

			// echo $datestring .'... ';

			// Check for current date. 
			if( $current_time < $end ) {
				// Don't poll todays date as we might get bad data if we listen to more songs today. 
				// echo "skipped, todays date \n";
				break; 
			}

			if (in_array($datestring, $foursquare_existing_recoreds)) {
				// echo "skipped, already in database \n";
				continue; // Already got this point.
			}

			$url = $settings['foursquare']['url']. 'users/self/checkins?oauth_token='. $settings['foursquare']['oauth_token'] .'&v=20140322&afterTimestamp='.$start.'&beforeTimestamp='.$end ;
			$response = GetURL( $url ) ; 
			$results = json_decode($response, true); 
			$num_of_requests++; 

			// echo '<pre>'.$datestring .'='. count( $results["response"]["checkins"]["items"] ) .'</pre>';
			
			

			// Check to see if we recived an error. If we do then use the total of zero as a value. 
			$total = 0 ; 
			if( isset( $results["response"]["checkins"]["items"] ) ) {
				$total = count( $results["response"]["checkins"]["items"] ) ; 
			}


			// Update the database 
			$results = $dbhandle->query('INSERT INTO foursquare (datestring, checkins) VALUES ( "'. $datestring .'", "'. $total .'")');
			
		}
	}
}





// Last FM
// -------------------------------------------------
$current_time = time(); 
$num_of_requests = 0 ; 


// Check the database to see if what values from the last year that we have already gotten
$lastfm_existing_recoreds = array() ; 
$sql = 'SELECT datestring FROM last_fm WHERE datestring >= "'. sprintf('%04d-%02d-%02d', date('Y')-1,1,1) .'" AND datestring <= "'. sprintf('%04d-%02d-%02d', date('Y'),31,12) .'" ;' ;
$results = $dbhandle->query( $sql );	
while( $row = $results->fetchArray() ) {
	$lastfm_existing_recoreds[] = $row['datestring'] ; 
}

// Only scan this last years worth of Last.fm data. 
for( $year = date('Y') ; $year <= date('Y') ; $year++ ) {
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

			// echo $datestring .'... ';

			// Check for current date. 
			if( $current_time < $end ) {
				// Don't poll todays date as we might get bad data if we listen to more songs today. 
				// echo "skipped, todays date \n";
				break; 
			}

			if (in_array($datestring, $lastfm_existing_recoreds)) {
				// echo "skipped, already in database \n";
				continue; // Already got this point.
			}

			echo $datestring .'= ';
			$url = $settings['lastfm']['url'].'?method=user.getrecenttracks&user='.$settings['lastfm']['user'] .'&api_key='. $settings['lastfm']['api_key'] .'&format=json&from='.$start.'&to='.$end.'&extended=0&limit=1';
			$response = GetURL( $url ) ; 
			$results = json_decode($response, true); 
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
?></pre>