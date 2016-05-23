<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\docs_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;

$app->group('/v1/docs', function () use ($app) {

    /**
     *
     */
    $app->get('/route', function () use ($app) {
        $model = new DocumentationModel();
        $verb = 'GET';
        $route = 'v1/example';
        $result = $model->getDocumentation($route, $verb);
        $resp = array('results ' => $result);
        $app->success($resp);
    });


     /**
      *
      */
    $app->get('/routes', function () use ($app) {
        $model = new DocumentationModel();
        $result = $model->getCompleteApiDocumentation();
        $resp = array('results ' => $result);
        $app->success($resp);
    });

    /**
     *
     */
    $app->get('/api', function () use ($app) {
        //'core/oauth2_v2/views/index.php'
       $model = new DocumentationModel();
       $routeDocs = $model->getCompleteApiDocumentation();
       $plugin     = Libs\RESTilias::getPlugin();
       $pluginDir  = str_replace('./', '', $plugin->getDirectory());
       $pluginDir  = $pluginDir . '/RESTController/extensions/docs_v1/views/';

       $app->response()->setFormat('HTML');
       $app->render('extensions/docs_v1/views/api_view.php',
           array(
               'viewURL' => ILIAS_HTTP_PATH . '/' . $pluginDir,
               'docs' => $routeDocs
           )
       );
    });

});
