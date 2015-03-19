<?php
//var_dump($_POST);
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" ng-app="myApp" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" ng-app="myApp" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" ng-app="myApp" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" ng-app="myApp" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>ILIAS REST Plugin - Administration</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="bower_components/html5-boilerplate/css/normalize.css">
  <link rel="stylesheet" href="bower_components/html5-boilerplate/css/main.css">
  <link rel="stylesheet" href="css/app.css"/>
  <link rel="stylesheet" href="css/loginadmin.css"/>
  <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">
  <link rel="stylesheet" href="bower_components/angular-loading-bar/build/loading-bar.min.css">
  <link href="css/bootstrap.min.css" rel="stylesheet"> <!-- v3.0.0 -->

  <link href="bower_components/angular-xeditable/dist/css/xeditable.css" rel="stylesheet">
  <script src="bower_components/html5-boilerplate/js/vendor/modernizr-2.6.2.min.js"></script>
<script>
    var postvars = {
        user_id : "<?php echo $_POST['user_id']; ?>",
        session_id : "<?php echo $_POST['session_id']; ?>",
        rtoken : "<?php echo $_POST['rtoken']; ?>",
        inst_folder : "<?php echo $_POST['inst_folder']; ?>"
    };
</script>
</head>
<body ng-controller="defaultCtrl">

  <!--[if lt IE 7]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
  <![endif]-->
    <div class="main_div">

    <nav role="navigation" class="navbar navbar-default">
            <a href="#" class="navbar-brand">ILIAS REST Plugin > Clients Administration</a>
            <p data-ng-show="isAuthenticated()" class="navbar-text ">
                Logged in as {{getUsername()}}
            </p>
        <ul class="nav navbar-nav navbar-right">
            <li data-ng-show="isAuthenticated()"><a href="#" ng-click="logout()">Logout</a></li>
        </ul>
    </nav>
  <div class="{{ pageClass }}" ng-view></div>

  </div>
  <!-- In production use:
  <script src="//ajax.googleapis.com/ajax/libs/angularjs/x.x.x/angular.min.js"></script>
  -->
  <script src="bower_components/angular/angular.js"></script>
  <script src="bower_components/angular-route/angular-route.js"></script>
  <script src="bower_components/angular-resource/angular-resource.js"></script>
  <script src="bower_components/angular-xeditable/dist/js/xeditable.js"></script>
  <script src="bower_components/angular-ui-utils/ui-utils.js"></script>
  <script src="bower_components/angular-bootstrap/ui-bootstrap.js"></script>
  <script src="bower_components/angular-animate/angular-animate.js"></script>
  <script src="bower_components/angular-loading-bar/build/loading-bar.min.js"></script>
  <script src="js/app.js"></script>
  <script src="js/services.js"></script>
  <script src="js/controllers.js"></script>
  <script src="js/filters.js"></script>
  <script src="js/directives.js"></script>
</body>
</html>
