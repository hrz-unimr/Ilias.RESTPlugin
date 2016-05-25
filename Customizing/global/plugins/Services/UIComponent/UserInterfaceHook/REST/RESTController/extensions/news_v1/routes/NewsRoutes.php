<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\news_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\core\auth as Auth;


/**
 * The news endpoints allow retrieving news from the ilias, see Personal Desktop>News
 */
$app->group('/v1/news', function () use ($app) {

    /**
     * Returns the personal desktop (pd) news items of the authenticated user.
     */
    $app->get('/pdnews', RESTAuth::checkAccess(RESTAuth::PERMISSION) ,  function () use ($app) {
        //$request = $app->request();

        $accessToken = $app->request->getToken();
        $uid = $accessToken->getUserId();
        $uname = $accessToken->getUserName();

        $model = new NewsModel();
        $pdnews = $model->getPDNewsForUser($uid);

        $result = array();
        $result['pdnews'] = $pdnews;
        $app->success($result);
    });

    /**
     * Admin: Gets the personal desktop (pd) news items of any user.
     */
    $app->get('/pdnews/:user_id', RESTAuth::checkAccess(RESTAuth::ADMIN) ,  function ($user_id) use ($app) {
        $model = new NewsModel();
        $pdnews = $model->getPDNewsForUser($user_id);

        $result = array();
        $result['msg'] = 'Personal Desktop News Items for User: '.$user_id.'.';
        $result['pdnews'] = $pdnews;
        $app->success($result);
    });



});
