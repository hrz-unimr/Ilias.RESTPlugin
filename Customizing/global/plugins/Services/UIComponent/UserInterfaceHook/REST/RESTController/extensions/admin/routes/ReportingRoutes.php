<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;

/**
 *  ILIAS Reporting API (for Administrators)
 */
$app->group('/admin/reporting', function () use ($app) {
    /**
     * Returns a list of active user sessions.
     */
    $app->get('/active_sessions', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {

        $app->log->debug('Calling reporting/active_sessions route');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));
        $model = new ReportingModel();
        $active_sessions_data = $model->getActiveSessions();

        $result['active_sessions'] = $active_sessions_data;
        if (count($active_sessions_data) == 0) {
            $result['status'] = 'No active sessions found.';
        } else {
            $result['status'] = 'Active sessions found.';
        }

        $app->success($result);
    });

    /**
     * Returns a list of active user sessions.
     */
    $app->get('/session_stats', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {

        $app->log->debug('Calling reporting/sessions_stats route');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));
        $model = new ReportingModel();
        $sessions_stats = $model->getSessionStatistics();

        $result['session_stats'] = $sessions_stats;
        $result['status'] = 'Ok';


        $app->success($result);
    });

});