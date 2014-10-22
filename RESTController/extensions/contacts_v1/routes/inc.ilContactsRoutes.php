<?php

/**
 * Contacts API
 */
$app->group('/v1', function () use ($app) {

    /**
     * Returns the contacts for a user specified by id
     */
    $app->get('/contacts/:id',  function ($id) use ($app) {
        $app = \Slim\Slim::getInstance();
        $env = $app->environment();
        $result = array();

        $model = new ilContactsModel();
        $data = $model->getMyContacts($id);
        // todo: add also group and course contacts:
        // see ilMailSearchCoursesGUI.php (from Services/Contact/classes)
        //var_dump($data);
        $result['msg'] = "Contacts for user ".$id;
        $result['contacts']['mycontacts'] = $data;
        echo json_encode($result);
    });
});
