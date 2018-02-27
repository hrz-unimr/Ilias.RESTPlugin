<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\contacts_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs\RESTAuth as RESTAuth;
use \RESTController\libs as Libs;
use \RESTController\libs\Exceptions as LibExceptions;


$app->group('/v1', function () use ($app) {

    /**
     * Returns the personal ILIAS contacts of the authenticated user.
     */
    $app->get('/contacts', RESTAuth::checkAccess(RESTAuth::PERMISSION), function () use ($app) {
        $accessToken = $app->request->getToken();


        $authorizedUserId =  $accessToken->getUserId();

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $id = $authorizedUserId;

            $model = new ContactsModel();
            $data = $model->getMyContacts($id);
            $resp = array("contacts" => $data);
            $app->success($resp);

        }
        else {
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
        }
    });

    /**
     * Creates a new contact entry to the contact list of the authenticated user.
     * Requires POST variables: login, firstname, lastname, email
     */
    $app->post('/contacts/add', RESTAuth::checkAccess(RESTAuth::PERMISSION), function() use ($app) {
        $app->log->debug("POST contacts add ...");
        $accessToken = $app->request->getToken();
        $request = $app->request();
        $login      = $request->getParameter("login");
        $firstname  = $request->getParameter("firstname");
        $lastname   = $request->getParameter("lastname");
        $email      = $request->getParameter("email");

        $authorizedUserId =  $accessToken->getUserId();

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $app->log->debug("contacts add ...".$request->getParameter("login"));
            $model = new ContactsModel();
            $data = $model->addContactEntry($authorizedUserId,$login,$firstname,$lastname,$email);
            $resp = array("contact_added"=>$data);
            $app->success($resp);

        }
        else {
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
        }
    });

    /**
     * Deletes entry specified by addr_id from the contact list of the authenticated user.
     */
    $app->delete('/contacts/:addr_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($addr_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId =  $accessToken->getUserId();


        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $id = $authorizedUserId;

            $model = new ContactsModel();
            $data = $model->deleteContactEntry($id, $addr_id);
            $resp = array("contact_removed" => $data);
            $app->success($resp);
        }
        else {
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
        }
    });

    /**
     * Updates contact entry addr_id of the authenticated user.
     */
    $app->put('/contacts/:addr_id', RESTAuth::checkAccess(RESTAuth::PERMISSION), function ($addr_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId =  $accessToken->getUserId();
        $request = $app->request();

        if ($authorizedUserId > -1) { // only the user is allowed to access the data
            $model = new ContactsModel();
            $data = $model->getContactEntry($authorizedUserId, $addr_id);
            $new_login = $request->getParameter("login",$data['login'],false);
            $new_firstname = $request->getParameter("firstname",$data['firstname'],false);
            $new_lastname = $request->getParameter("lastname",$data['lastname'],false);
            $new_email = $request->getParameter("email",$data['email'],false);
            $success = $model->updateContactEntry($authorizedUserId, $addr_id, $new_login, $new_firstname, $new_lastname, $new_email);
            $resp = array("contact_updated" => $success);
            $app->success($resp);
        }
        else {
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
        }
    });

    /**
     * Admin: Returns all contacts of a user specified by id.
     */
    $app->get('/contacts/:user_id', RESTAuth::checkAccess(RESTAuth::ADMIN), function ($user_id) use ($app) {
        $accessToken = $app->request->getToken();
        $authorizedUserId = $accessToken->getUserId();

        if ($authorizedUserId == $user_id || Libs\RESTilias::isAdmin($authorizedUserId)) { // only the user or the admin is allowed to access the data
            try {
                $model = new ContactsModel();
                $data = $model->getMyContacts($user_id);
                $resp = array("contacts" => $data);
                $app->success($resp);
            } catch (\Exception $e) {
                $app->halt(404, 'Error: Could not retrieve data for user '.$user_id.".", -15);
            }
        }
        else
            $app->halt(401, Libs\OAuth2Middleware::MSG_NO_ADMIN, Libs\OAuth2Middleware::ID_NO_ADMIN);
    });

});
