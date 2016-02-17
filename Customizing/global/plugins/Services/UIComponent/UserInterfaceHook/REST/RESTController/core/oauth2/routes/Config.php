<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\oauth2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group all oAuth2 (RFC) routes
  $app->group('/oauth2', function () use ($app) {
    /**
     * Route: [GET] /v2/oauth2/
     * [Admin required]
     *
     *
     * Returns:
     *
     */
    $app->get('/', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
    });
  });
});
