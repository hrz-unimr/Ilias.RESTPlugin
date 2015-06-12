<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\news_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


/**
 * The news endpoints allow retrieving news from the ilias, see Personal Desktop>News
 */
$app->group('/v1/news', function () use ($app) {

    /**
     * Allows for submission of a new feedback entry via GET.
     */
    $app->get('/news', '\RESTController\libs\OAuth2Middleware::TokenAuth' ,  function () use ($app) {
        //$request = $app->request();

        $auth = new Auth\Util();
        $accessToken = $auth->getAccessToken();
        $uid = $accessToken->getUserId();
        $uname = $accessToken->getUserName();

        $model = new NewsModel();

        $result = array();
        $result['msg'] = 'In get News OP.';
        $result['debug_uid'] = $uid;
        $result['debug_uname'] = $uname;
        $app->success($result);
    });



});
