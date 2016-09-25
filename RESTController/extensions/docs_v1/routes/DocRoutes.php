<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\docs_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;
use \RESTController\libs\Exceptions as LibExceptions;

$app->group('/v1/docs', function () use ($app) {

    /**
     * Retrieves meta-information about a particular route. The following parameters must be specified: verb and route.
     */
    $app->get('/route', function () use ($app) {
        $request = $app->request();
        try {
            $verb = $request->getParameter("verb", null, true);
            $route = $request->getParameter("route", null, true);

            $model = new DocumentationModel();
            $result = $model->getDocumentation($route, $verb);
            $resp = array('results' => $result);

            $app->success($resp);
        } catch (Libs\RESTException $e) {
            $app->halt(401, "Error: ".$e->getRESTMessage(), -1);
        }
    });


     /**
      * Retrieves meta-information on all documented routes.
      */
    $app->get('/routes', function () use ($app) {
        $model = new DocumentationModel();
        $result = $model->getCompleteApiDocumentation();
        $resp = array('results' => $result);
        $app->success($resp);
    });

    /**
     * Provides the API documentation as html page. Needs to be called within a web browser.
     */
    $app->get('/api', function () use ($app) {
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
