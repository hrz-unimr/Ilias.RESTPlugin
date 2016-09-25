<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;


$app->group('/v1/admin', function () use ($app) {
    /**
     * Returns a subtree of the current repository object, where the root node's ref_id must be specified.
     * In the extreme case, the complete repository (tree) will be retrieved.
     */
    $app->get('/repository/:ref_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($ref_id) use ($app) {
        $maxDepth = 1000;
        $maxAge = 24; // 24 month
        $request  = $app->request();
        $maxDepth = $request->getParameter("depth");
        $maxAge = $request->getParameter("age");

        $repModel = new RepositoryAdminModel();
      //  $data = $repModel->getSubTree($ref_id);
        $data = $repModel->getSubTreeWithinTimespanDepth($ref_id, $maxAge, $maxDepth);

        $app->success($data);
    });


    /**
     * Get subtree of categories.
     */
    $app->get('/repository/categories/:ref_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($ref_id) use ($app) {
        $repModel = new RepositoryAdminModel();
        $data = $repModel->getRekNode($ref_id, 0, array('cat'), 0, 1000);

        $app->success($data);
    });

    /**
     * Return the number of read events of an object given its reference id.
     */
    $app->get('/repository/analytics/:ref_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($ref_id) use ($app) {
        $repModel = new RepositoryAdminModel();
        //  $data = $repModel->getSubTree($ref_id);
        $data = $repModel->getRepositoryReadEvents($ref_id);

        $app->success($data);
    });


    /**
     * Creates a new category within the repository container object specfied by ref_id
     */
    $app->post('/categories', RESTAuth::checkAccess(RESTAuth::ADMIN), function () use ($app) {
        $repModel = new RepositoryAdminModel();
        $parent_ref_id = $request->getParameter("ref_id");
        $title = $request->getParameter("title");
        $description = $request->getParameter("description");
        $new_ref_id = $repModel->createNewCategoryAsUser($parent_ref_id, $title, $description);

        $result = array('ref_id' => $new_ref_id);
        $app->success($result);
    });
});
