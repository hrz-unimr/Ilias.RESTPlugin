<?php

/**
 * Contacts API
 */
$app->group('/v1', function () use ($app) {

    /**
     * Returns the personal ILIAS contacts for a user specified by id.
     */
    $app->get('/contacts/:id', 'authenticate', function ($id) use ($app) {
        $env = $app->environment();
        $response = new RESTResponse($app);
        $authorizedUserId =  RESTLib::loginToUserId($env['user']);
        if ($authorizedUserId == $id || RESTLib::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ContactsModel();
                $data = $model->getMyContacts($id);
                $response->setMessage("Contacts for user " . $id . ".");
                $response->addData('contacts', $data);
            } catch (Exception $e) {
                $response->setRESTCode("-15");
                $response->setMessage('Error: Could not retrieve data for user '.$id.".");
            }
        } else {
            $response->setRESTCode("-13");
            $response->setMessage('User has no RBAC permissions to access the data.');
        }
        $response->toJSON();
    });
});
