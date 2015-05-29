<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\admin;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


/*
 * Admin routes for the ILIAS repository.
 */

$app->group('/admin', function () use ($app) {

    /**
     * Returns a subtree of the current repository object, where the root node's ref_id must be specified.
     * In the extreme case, the complete repository (tree) will be retrieved.
     */
    $app->get('/repository/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($ref_id) use ($app) {
        $maxDepth = 1000;
        $maxAge = 24; // 24 month
        $maxDepth = $request->params("depth");
        $maxAge = $request->params("age");

        $repModel = new RepositoryAdminModel();
      //  $data = $repModel->getSubTree($ref_id);
        $data = $repModel->getSubTreeWithinTimespanDepth($ref_id, $maxAge, $maxDepth);

        $result = $data;
        $result['status'] = 'Subtree of repository item '.$ref_id.'.';

        $app->success($result);
    });

    /**
     * Get subtree of categories.
     */
    $app->get('/repository/categories/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($ref_id) use ($app) {
        $repModel = new RepositoryAdminModel();
        $data = $repModel->getRekNode($ref_id, 0, array('cat'), 0, 1000);

        $result = $data;
        $result['status'] = 'Subtree of repository item '.$ref_id.'.';

        $app->success($result);
    });

    $app->get('/repository/analytics/:ref_id', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function ($ref_id) use ($app) {
        $repModel = new RepositoryAdminModel();
        //  $data = $repModel->getSubTree($ref_id);
        $data = $repModel->getRepositoryReadEvents($ref_id);

        $result = $data;
        $result['status'] = 'Subtree of repository item '.$ref_id.'.';

        $app->success($result);
    });

    /**
     * Creates a new category within the repository container object specfied by ref_id
     */
    $app->post('/categories', '\RESTController\libs\OAuth2Middleware::TokenAdminAuth', function () use ($app) {
        $repModel = new RepositoryAdminModel();
        $parent_ref_id = $request->params("ref_id");
        $title = $request->params("title");
        $description = $request->params("description");
        $new_ref_id = $repModel->createNewCategoryAsUser($parent_ref_id, $title, $description);

        $result = $data;
        $result['status'] = 'New Category added to container '.$ref_id.' successfully.';

        $app->success($result);
    });




});
?>
