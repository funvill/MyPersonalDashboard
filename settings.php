<?php 

// General 
$settings['database']['file'] 	= 'database.sqlite' ; 
$settings['useragent'] 			= 'MyPersonalDashboard-funvill';
$settings['debug']				= true ; 

// Last FM
// -------------------------------------------------
$settings['lastfm']['url'] 		= 'http://ws.audioscrobbler.com/2.0/';
$settings['lastfm']['api_key'] 	= 'xxxx';
$settings['lastfm']['secret'] 	= 'xxxx';
$settings['lastfm']['user'] 	= 'funvill';

// Four Square 
// -------------------------------------------------
$settings['foursquare']['url'] 				= 'https://api.foursquare.com/v2/';
$settings['foursquare']['client_id'] 		= 'xxxx';
$settings['foursquare']['client_secret'] 	= 'xxxx';
$settings['foursquare']['oauth_token'] 		= 'xxxx';

// Git hub
// -------------------------------------------------
$settings['github']['www'] 				= 'https://github.com/';
$settings['github']['url'] 				= 'https://api.github.com/';
$settings['github']['user'] 			= 'funvill';

// Twitter
// -------------------------------------------------
// https://dev.twitter.com/docs/api/1.1
// https://dev.twitter.com/docs/api/1.1/get/users/lookup
$settings['twitter']['user'] 				= 'funvill';
$settings['twitter']['url'] 				= 'https://api.twitter.com/1.1/users/lookup.json';
$settings['twitter']['api_key'] 			= 'xxxx ';
$settings['twitter']['api_secret'] 			= 'xxxx';
$settings['twitter']['access_token'] 		= 'xxxx';
$settings['twitter']['access_token_secret'] = 'xxxx';
$settings['twitter']['consumer_key'] 		= 'xxxx';
$settings['twitter']['consumer_secret'] 	= 'xxxx';

// Moves-app
// -------------------------------------------------
$settings['moves-app']['url']				= 'https://api.moves-app.com/';
$settings['moves-app']['client_id']			= 'xxxx';
$settings['moves-app']['client_secret']		= 'xxxx';
?>