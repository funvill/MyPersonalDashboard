<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Steven Smethurst">
    <link rel="shortcut icon" href="favicon.ico">

    <title>Personal dashboard</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->


    <script type="text/javascript" src="/js/jquery-2.0.3.min.js"></script>
    <script type="text/javascript" src="/js/knockout-3.0.0.js"></script>
    <script type="text/javascript" src="/js/globalize.min.js"></script>
    <script type="text/javascript" src="/js/dx.chartjs.js"></script>    

  </head>

  <body>    
      <div class="container">
        <div class="navbar navbar-default" role="navigation">
          <div class="navbar-header">
            <a class="navbar-brand" href="#">Personal Dashboard</a>
          </div>
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Home</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="https://github.com/funvill/MyPersonalDashboard">Source code</a></li>
          </ul>
        </div>
      </div>
    

    <div class="container">
      <div id="chartContainerCombined" style="max-width:800px;height: 400px;"></div>
      <div id="chartContainerTwitter" style="max-width:800px;height: 400px;"></div>
      <div id="chartContainerTwitterStatuses" style="max-width:800px;height: 400px;"></div>
      <div id="chartContainerGithub" style="max-width:800px;height: 400px;"></div>

      <h2 id='about'>About</h2>
      <p>ToDo: Fill this page in with notes about this project. </p>
      <p>Inspired by <a href='https://ahmetalpbalkan.com/blog/personal-dashboard/'>A dashboard about myself by Ahmet Alp Balkan</a>, and <a href='http://blog.stephenwolfram.com/2012/03/the-personal-analytics-of-my-life/'>The Personal Analytics of My Life by Stephen Wolfram</a></p>
      <p>Reasons: 
        <ul>
            <li>Learn different APIs (Twitter, Github, Foursquare, LastFM, Moves-App, etc...)</li>
            <li>Learn javascript chart system (ChartJS, D3JS, etc...) </li>
            <li><a href='http://quantifiedself.com/'>Quantified Self</a> - I have always been interested in the Quantified Self (QT) movement. If you can't track it then you can't change it. </li>
        </ul>
      </p>
      <p>Tools used: 
          <ul>
              <li><a href='http://chartjs.devexpress.com/'>ChartJS</a> - Pretty Charts and Graphs </li>
              <li><a href='http://getbootstrap.com/'>Bootstrap</a> - A frontend framework for making websites fast.</li>
              <li><a href='https://dev.twitter.com/docs/api/1.1'>Twitter API</a> - Social site. </li>
              <li><a href='http://github.com/j7mbo/twitter-api-php'>Twitter API PHP</a> - An php libary that makes communicating with twitter easier</li>
              <li><a href='https://developer.foursquare.com/'>Foursquare API</a> - Tracks the places I check into </li>
              <li><a href='http://www.last.fm/api'>LastFM API</a> - Tracks the songs that are played from my variouse devices and apps.</li>
              <li><a href='http://developer.github.com/'>Github API</a> - Storage for the source code. Tracking commits</li>
              <li><a href='https://moves-app.com/'>Moves-App</a> - An Android and ISO app that tracks your movements and steps.</li>
              <li><a href='https://www.sqlite.org/'>SQLite</a> - Filebased database.</li>

              
          </ul>
      </p>  
    </div><!-- /.container -->



    <script>
      <?php 
        require_once('settings.php');

        // Connect and set up the database.
        $dbhandle = new SQLite3( $settings['database']['file'] ); 

        // Combine all the date driven data. 
        $combinedData = array(); 
        $results = $dbhandle->query('SELECT * FROM last_fm ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          $combinedData[ $row['datestring'] ]['lastfm'] = $row['total'] ; 
        } 
        $results = $dbhandle->query('SELECT * FROM foursquare ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          $combinedData[ $row['datestring'] ]['foursquare'] = $row['checkins'] ; 
        } 
        $results = $dbhandle->query('SELECT datestring, contributions FROM github ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          $combinedData[ $row['datestring'] ]['github'] = $row['contributions'] ; 
        } 
        echo 'var combinedDataSource = [';
        foreach( $combinedData as $key => $value ) {
            if( isset( $value['lastfm'] ) && isset( $value['foursquare'] ) && isset( $value['github'] ) ) {
                echo '{ time: "'. $key .'", lastfm: '. $value['lastfm'] .', foursquare:'. $value['foursquare'].', github:'. $value['github'] ."},\n";
            }
        } 
        echo '];';


        // Display the Twitter dataSource 
        echo 'var twitterDataSource = [';
        $results = $dbhandle->query('SELECT * FROM twitter ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          echo '{ time: "'. $row['datestring'] .'", followers_count: '. $row['followers_count'] .', friends_count:'. $row['friends_count'] .', favourites_count:'. $row['favourites_count'] .', statuses_count:'. $row['statuses_count'] ."},\n";
        } 
        echo '];';


        // Display the Github dataSource 
        echo 'var githubDataSource = [';
        $results = $dbhandle->query('SELECT * FROM github WHERE public_repos > 0 ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          if(  $row['public_repos'] > 0 ) {
            echo '{ time: "'. $row['datestring'] .'", public_repos: '. $row['public_repos'].', public_gists: '. $row['public_gists'].', followers: '. $row['followers'].', following: '. $row['following'] .', contributions: '. $row['contributions'] ."},\n";
          }
        }
        echo '];';

        echo 'var githubDataSourceContributions = [';
        $results = $dbhandle->query('SELECT datestring, contributions FROM github ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
            echo '{ time: "'. $row['datestring'] .'", contributions: '. $row['contributions'] ."},\n";
        }
        echo '];';

?>


$("#chartContainerCombined").dxChart({
    dataSource: combinedDataSource,
    commonSeriesSettings: {
        type: "splineArea",
        argumentField: "time",
        point: {
        visible: true
    },
    },
    series: [
        { valueField: "lastfm",     name: "Songs Played",                                             color: "#880000" },
        { valueField: "github",     name: "Github Contributions",                     type: "spline", color: "#008800" },
        { valueField: "foursquare", name: "Places Visited",         axis: "Checkins", type: "spline", color: "#000088" },
    ],
    tooltip: {
        enabled: true,
        customizeText: function (arg) {
            return this.valueText ; 
        }
    },
    
    title: "Daily Activies",
    argumentAxis:{
        valueMarginsEnabled: false,
        grid:{
            visible: false
        },        
    },

    valueAxis: [{
        grid: {
            visible: true
        },
        title: {
            text: "LastFM Songs Played"
        },
    }, {
        name: "Checkins",
        position: "right",
        grid: {
            visible: true
        },
        title: {
            text: "FourSquare Checkins"
        },
        label: {
            format: "largeNumber"
        }
    }],

    legend: {
        visible:true,
        verticalAlignment: "bottom",
        horizontalAlignment: "center"
    }
});

$("#chartContainerGithubContributions").dxChart({
    dataSource: githubDataSourceContributions,
    commonSeriesSettings: {
        type: "splineArea",
        argumentField: "time",
        point: {
        visible: true
    },
    },
    series: [
        { valueField: "contributions", name: "Contributions" },
    ],
    tooltip: {
        enabled: true,
        customizeText: function () {
            return "Contributions: " + this.valueText;
        }
    },
    
    title: "Github contributions",
    argumentAxis:{
        valueMarginsEnabled: false,
        grid:{
            visible: false
        },        
    },
    valueAxis:{
        grid:{
            visible: true
        }
    },
    legend: {
      visible:false,
        verticalAlignment: "bottom",
        horizontalAlignment: "center"
    }
});

$("#chartContainerGithub").dxChart({
    dataSource: githubDataSource,
    commonSeriesSettings: {
        type: "spline",
        argumentField: "time",
        point: {
            visible: true
        },
    },
    series: [
        { valueField: "public_repos", name: "Repos" },
        { valueField: "public_gists", name: "Gists" },
        { valueField: "followers",    name: "Followers" },
        { valueField: "following",    name: "Following" },
    ],
    tooltip: {
        enabled: true,
    },
    
    title: "GitHub",
    argumentAxis:{
        valueMarginsEnabled: false,
        grid:{
            visible: false
        },        
    },
    valueAxis:{
        grid:{
            visible: true
        }
    },
    legend: {
      visible:true,
        verticalAlignment: "bottom",
        horizontalAlignment: "center"
    }
});

$("#chartContainerTwitter").dxChart({
    dataSource: twitterDataSource,
    commonSeriesSettings: {
        type: "spline",
        argumentField: "time",
        point: {
        visible: true
    },
    },
    series: [
        { valueField: "followers_count",  name: "Followers" },
        { valueField: "friends_count",    name: "Friends" },
        // { valueField: "favourites_count", name: "Favourites" },
        // { valueField: "statuses_count",   name: "Statuses" },
    ],
    tooltip: {
        enabled: true,
    },
    
    title: "Twitter",
    argumentAxis:{
        valueMarginsEnabled: false,
        grid:{
            visible: false
        },        
    },
    valueAxis:{
        grid:{
            visible: true
        }
    },
    legend: {
      visible:true,
        verticalAlignment: "bottom",
        horizontalAlignment: "center"
    }
});


$("#chartContainerTwitterStatuses").dxChart({
    dataSource: twitterDataSource,
    commonSeriesSettings: {
        type: "bar",
        argumentField: "time",
        point: {
        visible: true
    },
    },
    series: [
        // { valueField: "followers_count",  name: "Followers" },
        // { valueField: "friends_count",    name: "Friends" },
        // { valueField: "favourites_count", name: "Favourites" },
        { valueField: "statuses_count",   name: "Statuses" },
    ],
    tooltip: {
        enabled: true,
    },
    
    title: "Twitter statuses",
    argumentAxis:{
        valueMarginsEnabled: false,
        grid:{
            visible: false
        },        
    },
    valueAxis:{
        grid:{
            visible: true
        }
    },
    legend: {
      visible:true,
        verticalAlignment: "bottom",
        horizontalAlignment: "center"
    }
});


</script>

    

    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script> -->
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  </body>
</html>
