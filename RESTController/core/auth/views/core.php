<!DOCTYPE html>
<!--[if lt IE 7]>      <html lang="en" class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html lang="en" class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html lang="en" class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="en" class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $tpl_title; ?></title>

    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/normalize.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/animate.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/bootstrap.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/bootstrap-theme.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/html5-boilerplate.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/angular-loading-bar.css">
    <link rel="stylesheet" href="<?php echo $tpl_path; ?>/libs/css/angular-xeditable.css">

    <script type="text/javascript" src="<?php echo $tpl_path; ?>/libs/js/modernizr.js"></script>
</head>

<body>
    <?php
    include($tpl_file);
    ?>
    
    <script src="<?php echo $tpl_path; ?>/libs/js/jquery.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/less.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-animate.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-loading-bar.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-resource.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-route.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-ui-bootstrap.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-ui-utils.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-ui-utils-ieshiv.js"></script>
    <script src="<?php echo $tpl_path; ?>/libs/js/angular-xeditable.js"></script>
</body>
</html>