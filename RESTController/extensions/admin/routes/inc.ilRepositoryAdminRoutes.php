<?php
/*
 * Admin routes for the ILIAS repository.
 */

$app->group('/admin', function () use ($app) {

    /**
     * Returns a subtree of the current repository object, where the root node's ref_id must be specified.
     * In the extreme case, the complete repository (tree) will be retrieved.
     */
    $app->get('/repository/:ref_id', 'authenticateILIASAdminRole', function ($ref_id) use ($app) {
        $request = new ilRestRequest($app);
        $response = new ilRestResponse($app);
        $repModel = new ilRepositoryAdminModel();
        $data = $repModel->getSubTree(61);
        $response->setData("subtree",$data);
        $response->setMessage('Subtree of repository item '.$ref_id.'.');
        $response->send();
    });



});
?>
