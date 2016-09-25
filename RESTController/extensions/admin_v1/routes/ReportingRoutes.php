<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use Codeception\PhpUnit\ResultPrinter\Report;
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;

/**
 *  ILIAS Reporting API (for Administrators)
 */
$app->group('/v1/admin/reporting', function () use ($app) {
    /**
     * Returns a list of active user sessions.
     */
    $app->get('/active_sessions', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/active_sessions route');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $active_sessions_data = ReportingModel::GetActiveSessions();

        $result['active_sessions'] = $active_sessions_data;
        if (count($active_sessions_data) == 0) {
            $result['status'] = 'No active sessions found.';
        } else {
            $result['status'] = 'Active sessions found.';
        }

        $app->success($result);
    });

    /**
     * Returns statistics about user sessions.
     */
    $app->get('/session_stats', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/sessions_stats route');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $sessions_stats = ReportingModel::GetSessionStatistics();

        $result['session_stats'] = $sessions_stats;

        $app->success($result);
    });

    /**
     * Returns statistics about user sessions within a 24-h time frame.
     */
    $app->get('/session_stats_daily', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/session_stats_daily route');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $sessions_stats = ReportingModel::GetSessionsDaily();

        $result['session_stats_daily'] = $sessions_stats;

        $app->success($result);
    });

    /**
     * Returns statistics about user sessions within a 1-h time frame.
     */
    $app->get('/session_stats_hourly', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/session_stats_hourly');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $sessions_stats = ReportingModel::GetSessionsHourly();

        $result['session_stats_hourly'] = $sessions_stats;

        $app->success($result);
    });

    /**
     * Returns the current absolute number of ilias objects.
     */
    $app->get('/object_stats', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/object_stats');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $obj_stats = ReportingModel::GetObjectStatistics();
        $result['object_stats'] = $obj_stats;

        $app->success($result);
    });

    /**
     * Returns the access statistics on all ILIAS repository objects within a 24-h time frame.
     * In addition to the access numbers the route also provides information about the objects, such as
     *  - title
     *  - type
     *  - location within the repository hierarchy
     */
    $app->get('/repository_stats', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {

        $app->log->debug('Calling reporting/repository_stats');

        $result = array('msg' => array('querytime'=>date("H:i:s",time()), 'querytimestamp'=>time()));

        $repository_stats = ReportingModel::GetRepositoryStatistics();

        $result['repository_stats'] = $repository_stats;

        $app->success($result);
    });

});
