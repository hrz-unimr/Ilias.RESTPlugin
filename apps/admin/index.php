<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" data-ng-app="myApp" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" data-ng-app="myApp" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" data-ng-app="myApp" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" data-ng-app="myApp" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>ILIAS REST Plugin - Administration</title>
    
    <link rel="icon" href="img/icon.png">
    <link rel="shortcut icon" href="img/logo.png">
    <link rel="apple-touch-icon" href="img/logo.png">

    <link rel="stylesheet" href="libs/css/normalize.css">
    <link rel="stylesheet" href="libs/css/animate.css">
    <link rel="stylesheet" href="libs/css/bootstrap.css">
    <link rel="stylesheet" href="libs/css/bootstrap-theme.css">
    <link rel="stylesheet" href="libs/css/html5-boilerplate.css">
    <link rel="stylesheet" href="libs/css/angular-loading-bar.css">
    <link rel="stylesheet" href="libs/css/angular-xeditable.css">
    
    <link rel="stylesheet" href="css/app.css" />
        
    <script type="text/javascript" src="libs/js/modernizr.js"></script>
    <script>
        <?php
        // Fetch POST data
        $user_id =  isset($_POST['user_id']) ? $_POST['user_id'] : '';
        $session_id = isset($_POST['session_id']) ? $_POST['session_id'] : '';
        $rtoken = isset($_POST['rtoken']) ? $_POST['rtoken'] : '';
        if (isset($_POST['inst_folder'])) {
            $inst_folder = $_POST['inst_folder'];
        } elseif (file_exists('config.ini.php')) {
            include_once ('config.ini.php');
            echo $inst_folder;
        }
        
        // Make it save
        $user_id = htmlspecialchars($user_id, ENT_COMPAT | ENT_HTML5);
        $session_id = htmlspecialchars($session_id, ENT_COMPAT | ENT_HTML5); 
        $rtoken = htmlspecialchars($rtoken, ENT_COMPAT | ENT_HTML5);
        $inst_folder = htmlspecialchars($inst_folder, ENT_COMPAT | ENT_HTML5);
        ?>
    
        var postvars = {
            user_id: "<?php echo $user_id; ?>",
            session_id: "<?php echo $session_id; ?>",
            rtoken: "<?php echo $rtoken;  ?>",
            inst_folder: "<?php echo $inst_folder; ?>",
        };
    </script>
</head>
<body data-ng-controller="defaultCtrl">
    <div class="main-div">
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
    
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <!-- Brand and toggle get grouped for better mobile display -->
                <div class="navbar-header navbar-left">
                    <p><a class="navbar-brand" href="#/clientlist"><img class="brand-img" alt="Logo" src="img/icon.png"> ILIAS REST Plugin > Clients Administration</a></p>
                </div>
                <div class="navbar-right addRightPadding" data-ng-show="isAuthenticated()">
                    <p class="navbar-text">Logged in as {{getUsername()}}</p>
                    <button class="btn btn-default navbar-btn" type="button" data-ng-click="logout()">Logout</button>
                </div>
            </div>
        </nav>
    
        <div data-ng-show="noAccessRights" class="alert alert-danger" role="alert">You do not have the required permissions to continue...</div>
        <div data-ng-hide="noAccessRights" class="{{ pageClass }}" data-ng-view></div>
    </div>
    
    <script src="libs/js/angular.js"></script>
    <script src="libs/js/angular-route.js"></script>
    <script src="libs/js/angular-resource.js"></script>
    <script src="libs/js/angular-xeditable.js"></script>
    <script src="libs/js/angular-ui-utils.js"></script>
    <script src="libs/js/angular-ui-bootstrap.js"></script>
    <script src="libs/js/angular-animate.js"></script>
    <script src="libs/js/angular-loading-bar.js"></script>
    <script src="libs/js/jquery.js"></script>
    <script src="libs/js/less.js"></script>

    <script src="js/app.js"></script>
    <script src="js/services.js"></script>
    <script src="js/controllers.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/directives.js"></script>
</body>
</html>
