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
    
    <title>{{'INDEX_TITLE' | translate}}</title>
    
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
    <link rel="stylesheet" href="libs/css/angular-flash.css">
    
    <link rel="stylesheet" href="css/app.css" />
        
    <script type="text/javascript" src="libs/js/modernizr.js"></script>
    <script>
        <?php
            // Fetch POST data
            $userName = isset($_POST['userName']) ? $_POST['userName'] : '';
            $apiKey = isset($_POST['apiKey']) ? $_POST['apiKey'] : '';
            $userId =  isset($_POST['userId']) ? $_POST['userId'] : '';
            $sessionId = isset($_POST['sessionId']) ? $_POST['sessionId'] : '';
            $rtoken = isset($_POST['rtoken']) ? $_POST['rtoken'] : '';
            $restEndpoint = isset($_POST['restEndpoint']) ? $_POST['restEndpoint'] : '';
            $iliasClient = isset($_POST['iliasClient']) ? $_POST['iliasClient'] : '';

            // Make it save
            $userName = addslashes (htmlspecialchars($userName, ENT_COMPAT));
            $apiKey = addslashes (htmlspecialchars($apiKey, ENT_COMPAT));
            $userId = addslashes (htmlspecialchars($userId, ENT_COMPAT));
            $sessionId = addslashes (htmlspecialchars($sessionId, ENT_COMPAT));
            $rtoken = addslashes (htmlspecialchars($rtoken, ENT_COMPAT));
            $restEndpoint = addslashes (htmlspecialchars($restEndpoint, ENT_COMPAT));
            $iliasClient = addslashes (htmlspecialchars($iliasClient, ENT_COMPAT));
        ?>
    
        var postVars = {
            userId: "<?php echo $userId; ?>",
            userName: "<?php echo $userName; ?>",
            sessionId: "<?php echo $sessionId; ?>",
            rtoken: "<?php echo $rtoken;  ?>",
            restEndpoint: "<?php echo $restEndpoint; ?>",
            apiKey: "<?php echo $apiKey; ?>",
            iliasClient: "<?php echo $iliasClient; ?>"
        };
    </script>
</head>
<body>
    <a id="top"></a>
    <div class="main-div" data-ng-controller="MainCtrl">
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
        
        <nav class="navbar navbar-default-x" role="navigation">
            <div class="container-fluid">
                <ul class="nav navbar-header">
                    <span class="navbar-brand"><img class="brand-img" alt="Logo" src="img/icon.png"> {{'INDEX_BRAND' | translate}}</span>
                </ul>
                <ul class="nav navbar-nav navbar-left" data-ng-show="breadcrumbs.breadcrumbs.length > 0" data-ng-cloak>
                    <ul class="breadcrumb breadcrumb-brand list-inline">
                        <li ng-repeat="breadcrumb in breadcrumbs.get() track by breadcrumb.path" ng-class="{ active: $last }">
                            <a ng-if="!$last" ng-href="#{{ breadcrumb.path }}" ng-bind="breadcrumb.label" class="margin-right-xs"></a>
                            <span ng-if="$last" ng-bind="breadcrumb.label"></span>
                        </li>

                    </ul>
                </ul>
                
                <ul class="nav navbar-nav navbar-right addRightPadding" data-ng-show="authentication.isAuthenticated()" data-ng-cloak>
                    <li><button class="btn btn-default-outline navbar-btn" type="button" data-ng-click="reload()"><span class="glyphicon glyphicon-repeat"></span></button></li>
                    <li><span class="navbar-text">{{'INDEX_LOGGED_IN' | translate:translationData}} [<timer countdown="1800" max-time-unit="'minute'" interval="1000" finish-callback="authentication.logout()">{{mminutes}}:{{sseconds}}</timer>]</span></li>
                    <li><button class="btn btn-default-outline navbar-btn" type="button" data-ng-click="authentication.logout()">{{'INDEX_LOGOUT' | translate}}</button></li>
                </ul>
            </div>
        </nav>
    
        <div data-ng-show="authentication.hasError()" class="alert alert-warning" role="alert" data-ng-cloak><div ng-bind-html-compile="authentication.getError()"></div></div>
        <div data-ng-show="!authentication.hasError() || isLoginRoute()" class="page-main" data-ng-view></div>


        <!-- <nav class="navbar navbar-default navbar-fixed-bottom" role="navigation">
            <span class="navbar-text navbar-text-center">
                <strong>{{'INDEX_VERSION' | translate}}:</strong> <span data-app-version></span> {{'INDEX_POWERED' | translate}}
                <a href="https://angularjs.org/" data-tooltip="Version: Unknown" data-angularjs-version target="_blank">AngularJS</a>,
               <a href="https://jquery.com/" data-tooltip="Version: Unknown" data-jquery-version target="_blank">jQuery</a>,
                <a href="http://modernizr.com" data-tooltip="Version: Unknown" data-modernizr-version target="_blank">Modernizr</a>,
                <a href="http://lesscss.org" data-tooltip="Version: Unknown" data-less-version target="_blank">LESS</a>,
                <a href="http://getbootstrap.com/" data-tooltip="Version: Unknown" data-bootstrap-version target="_blank">Bootstrap</a>,
                <a href="https://html5boilerplate.com/" data-tooltip="Version: Unknown" data-boilerplate-version target="_blank">HTML5 Boilerplates</a>,
                <a href="http://necolas.github.io/normalize.css/" data-tooltip="Version: Unknown" data-normalize-version target="_blank">Normalize.css</a> &amp;
                <a href="http://daneden.github.io/animate.css/" data-tooltip="Version: Unknown" data-animatecss-version target="_blank">Animate.css</a>
            </span>
        </nav>-->

    </div>
    
    <script src="libs/js/jquery.js"></script>
    <script src="libs/js/less.js"></script>
    <script src="libs/js/bootstrap.js"></script>
    <script src="libs/js/angular.js"></script>
    <script src="libs/js/angular-sanitize.js"></script>
    <script src="libs/js/angular-route.js"></script>
    <script src="libs/js/angular-resource.js"></script>
    <script src="libs/js/angular-xeditable.js"></script>
    <script src="libs/js/angular-ui-utils.js"></script>
    <script src="libs/js/angular-ui-bootstrap.js"></script>
    <script src="libs/js/angular-animate.js"></script>
    <script src="libs/js/angular-loading-bar.js"></script>
    <script src="libs/js/angular-breadcrumbs.js"></script>
    <script src="libs/js/angular-translate.js"></script>
    <script src="libs/js/angular-dialogs.js"></script>
    <script src="libs/js/angular-timer-all.min.js"></script>
    <script src="libs/js/angular-flash.js"></script>

    <script src="lang/angular_en-US.js"></script>
    <script src="lang/angular_de-DE.js"></script>
    
    <script src="js/app.js"></script>
    <script src="js/services.js"></script>
    <script src="js/controllers.js"></script>
    <script src="js/filters.js"></script>
    <script src="js/directives.js"></script>
</body>
</html>
