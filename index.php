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
        </div>
      </div>
    

    <div class="container">
    <!-- 
      <div class="row">
        <div class="col-md-6">
          <script src="js/dug.js"></script>
          <div id="github">Loading&hellip;</div>
          <script>
            dug({
              endpoint: 'https://api.github.com/users/funvill',
              target: 'github',
              error: function(){
                console.log('error');
              },
              template: '<div>\
                <a href="{{data.url}}">\
                  <h3>{{data.login}}</h3>\
                  <img src="{{data.avatar_url}}">\
                </a>\
              </div>'
            });
          </script>
        </div>
      </div> 
      -->

      <div id="chartContainerLastFM" style="max-width:800px;height: 400px;"></div>
      <div id="chartContainerTwitter" style="max-width:800px;height: 400px;"></div>
      <div id="chartContainerTwitterStatuses" style="max-width:800px;height: 400px;"></div>

<script>
      <?php 
        require_once('settings.php');

        // Connect and set up the database.
        $dbhandle = new SQLite3( $settings['database']['file'] ); 

        // Display the Twitter dataSource 
        echo 'var twitterDataSource = [';
        $results = $dbhandle->query('SELECT * FROM twitter ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          echo '{ time: "'. $row['datestring'] .'", followers_count: '. $row['followers_count'] .', friends_count:'. $row['friends_count'] .', favourites_count:'. $row['favourites_count'] .', statuses_count:'. $row['statuses_count'] ."},\n";
        } 
        echo '];';

        // Display the Last FM dataSource 
        echo 'var lastFMDataSource = [';
        $results = $dbhandle->query('SELECT * FROM last_fm ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          echo '{ time: "'. $row['datestring'] .'", total: '. $row['total'] ."},\n";
        }
        echo '];';

        // Display the Github dataSource 
        echo 'var githubDataSource = [';
        $results = $dbhandle->query('SELECT * FROM github ORDER BY datestring DESC LIMIT 30;');
        while ($row = $results->fetchArray()) {
          echo '{ time: "'. $row['datestring'] .'", public_repos: '. $row['public_repos'].', public_gists: '. $row['public_gists'].', followers: '. $row['followers'].', following: '. $row['following'] ."},\n";
        }
        echo '];';
?>



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
        type: "splinearea",
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

$("#chartContainerLastFM").dxChart({
    dataSource: lastFMDataSource,
    commonSeriesSettings: {
        type: "splineArea",
        argumentField: "time",
        point: {
        visible: true
    },
    },
    series: [
        { valueField: "total", name: "Songs" },
    ],
    tooltip: {
        enabled: true,
        customizeText: function () {
            // return this.argumentField + " Songs: " + this.valueText;
            return "Songs: " + this.valueText;
        }
    },
    
    title: "LastFM",
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

</script>



    </div><!-- /.container -->

    
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script> -->
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

    <?php require_once('data.php'); ?>

  </body>
</html>
