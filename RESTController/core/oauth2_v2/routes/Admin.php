<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
// Requires <$app = \RESTController\RESTController::getInstance()>
namespace RESTController\core\oauth2_v2;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;
use \RESTController\database as Database;


// Group Version 2 implementation
$app->group('/v2', function () use ($app) {
  // Group all oauth2 admin routes
  $app->group('/admin', function () use ($app) {
    /**
     * Route: [GET] /v2/admin/clients
     * [Admin required]
     *  Fetches a list (with data) of of all available oauth2 clients.
     *  Parameter terms should correspond to Oauth2 Spec: https://tools.ietf.org/html/rfc6749
     *
     * Returns:
     *  Array(
     *    'id' => Array(
     *      'id',                        <Number> - Internal clientId for this oauth2 client (only used to reference a client internally)
     *      'api_key',                   <String> - API-Key used by this oauth2 client (should be unique!)
     *      'api_secret',                <String> - Enable secret (password) for client-credentials
     *      'cert_serial',               <String> - Enable SSL-Certificate 'serial' restriction (regex/string-list) for client-credentials
     *      'cert_issuer',               <String> - Enable SSL-Certificate 'issuer' restriction (regex/string-list) for client-credentials
     *      'cert_subject',              <String> - Enable SSL-Certificate 'subject' restriction (regex/string-list) for client-credentials
     *      'redirect_uri',              <String> - Redirection URL for the redirection-based grant flow
     *      'consent_message',           <String> - Message displayed during authorization of client-application
     *                                              (Steps (B) and (C) for Authorization-Code and the Implicit grant-types.)
     *      'client_credentials_userid', <Number> - UserId attached to tokens when using client-credentials
     *      'grant_client_credentials',  <Bool>   - Enable grant-type: Client-Credentials
     *      'grant_authorization_code',  <Bool>   - Enable grant-type: Authorization-Code
     *      'grant_implicit',            <Bool>   - Enable grant-type: Implicit
     *      'grant_resource_owner',      <Bool>   - Enable grant-type: Resource-Owner
     *      'refresh_authorization_code',<Bool>   - Enable Refresh-Token for Authorization-Code grant-type
     *      'refresh_resource_owner',    <Bool>   - Enable Refresh-Token for Resource-Owner grant-type
     *      'grant_bridge',              <String> - Enable two-way ILIAS-REST login-bridge
     *                                              (i = ILIAS Session --> oAuth2 Token)
     *                                              (o = ILIAS Session <-- oAuth2 Token)
     *                                              (b = ILIAS Session <-> oAuth2 Token)
     *      'ips',                       <String> - Enable IP restriction (regex) for this client
     *      'users',                     <String> - Enable ILIAS userId restriction (regex/string-list) for this client
     *      'scopes',                    <String> - Enable scope-support (regex/string-list) for tokens generated via this oauth2 client
     *      'description'                <String> - Set a description for this oauth client
     *    )
     *  ) - Array of of clients, sorted by their clientId
     */
    $app->get('/clients', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        // Fetch all clients
        $clients = Database\RESTclient::fromWhere(null, true);

        // Iterate over all clients
        $result = array();
        foreach($clients as $client) {
          // Extract clientId and complete table-row
          $id           = $client->getKey('id');
          $row          = $client->getRow();

          // Remove null values
          $result[$id]  = array_filter($row, function($value) { return !is_null($value); });
        }

        // Send list of all clients
        $app->success($result);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $app->success(array());
      }
    });


    /**
     * Route: [GET] /v2/admin/client | /v2/admin/client/:clientId
     * [Admin required]
     *  Returns data for the oauth2 client given by clienId.
     *  Parameter terms should correspond to Oauth2 Spec: https://tools.ietf.org/html/rfc6749
     *
     * Parameters: [/v2/admin/client]
     *  id <Number> - Internal clientId for this oauth2 client (only used to reference a client internally)
     *
     * Returns:
     *  Array(
     *    'id' => Array(
     *      'id',                        <Number> - Internal clientId for this oauth2 client (only used to reference a client internally)
     *      'api_key',                   <String> - API-Key used by this oauth2 client (should be unique!)
     *      'api_secret',                <String> - Enable secret (password) for client-credentials
     *      'cert_serial',               <String> - Enable SSL-Certificate 'serial' restriction (regex/string-list) for client-credentials
     *      'cert_issuer',               <String> - Enable SSL-Certificate 'issuer' restriction (regex/string-list) for client-credentials
     *      'cert_subject',              <String> - Enable SSL-Certificate 'subject' restriction (regex/string-list) for client-credentials
     *      'redirect_uri',              <String> - Redirection URL for the redirection-based grant flow
     *      'consent_message',           <String> - Message displayed during authorization of client-application
     *                                              (Steps (B) and (C) for Authorization-Code and the Implicit grant-types.)
     *      'client_credentials_userid', <Number> - UserId attached to tokens when using client-credentials
     *      'grant_client_credentials',  <Bool>   - Enable grant-type: Client-Credentials
     *      'grant_authorization_code',  <Bool>   - Enable grant-type: Authorization-Code
     *      'grant_implicit',            <Bool>   - Enable grant-type: Implicit
     *      'grant_resource_owner',      <Bool>   - Enable grant-type: Resource-Owner
     *      'refresh_authorization_code',<Bool>   - Enable Refresh-Token for Authorization-Code grant-type
     *      'refresh_resource_owner',    <Bool>   - Enable Refresh-Token for Resource-Owner grant-type
     *      'grant_bridge',              <String> - Enable two-way ILIAS-REST login-bridge
     *                                              (i = ILIAS Session --> oAuth2 Token)
     *                                              (o = ILIAS Session <-- oAuth2 Token)
     *                                              (b = ILIAS Session <-> oAuth2 Token)
     *      'ips',                       <String> - Enable IP restriction (regex) for this client
     *      'users',                     <String> - Enable ILIAS userId restriction (regex/string-list) for this client
     *      'scopes',                    <String> - Enable scope-support (regex/string-list) for tokens generated via this oauth2 client
     *      'description'                <String> - Set a description for this oauth client
     *    )
     *  )
     */
    $app->get('/client/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        // Fetch client via given clientId
        $client = Database\RESTclient::fromPrimary($clientId);

        // Extract table-row of client and filter null values
        $row    = $client->getRow();
        $row    = array_filter($row, function($value) { return !is_null($value); });

        // Send client data
        $app->success($row);
      }

      // Catch database error (eg. clientId does not exist)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [POST] /v2/admin/client
     * [Admin required]
     *  Add a new oauth2 client to the database.
     *  Parameter terms should correspond to Oauth2 Spec: https://tools.ietf.org/html/rfc6749
     *
     * Parameters:
     *  'id',                        <Number> - [Optional] Internal clientId for this oauth2 client (only used to reference a client internally)
     *  'api_key',                   <String> - API-Key used by this oauth2 client (should be unique!)
     *  'api_secret',                <String> - [Optional] Enable secret (password) for client-credentials
     *  'cert_serial',               <String> - [Optional] Enable SSL-Certificate 'serial' restriction (regex/string-list) for client-credentials
     *  'cert_issuer',               <String> - [Optional] Enable SSL-Certificate 'issuer' restriction (regex/string-list) for client-credentials
     *  'cert_subject',              <String> - [Optional] Enable SSL-Certificate 'subject' restriction (regex/string-list) for client-credentials
     *  'redirect_uri',              <String> - [Optional] Redirection URL for the redirection-based grant flow
     *  'consent_message',           <String> - [Optional] Message displayed during authorization of client-application
     *                                          (Steps (B) and (C) for Authorization-Code and the Implicit grant-types.)
     *  'client_credentials_userid', <Number> - [Optional] UserId attached to tokens when using client-credentials
     *  'grant_client_credentials',  <Bool>   - [Optional] Enable grant-type: Client-Credentials
     *  'grant_authorization_code',  <Bool>   - [Optional] Enable grant-type: Authorization-Code
     *  'grant_implicit',            <Bool>   - [Optional] Enable grant-type: Implicit
     *  'grant_resource_owner',      <Bool>   - [Optional] Enable grant-type: Resource-Owner
     *  'refresh_authorization_code',<Bool>   - [Optional] Enable Refresh-Token for Authorization-Code grant-type
     *  'refresh_resource_owner',    <Bool>   - [Optional] Enable Refresh-Token for Resource-Owner grant-type
     *  'grant_bridge',              <String> - [Optional] Enable two-way ILIAS-REST login-bridge
     *                                          (i = ILIAS Session --> oAuth2 Token)
     *                                          (o = ILIAS Session <-- oAuth2 Token)
     *                                          (b = ILIAS Session <-> oAuth2 Token)
     *   'ips',                       <String> - [Optional] Enable IP restriction (regex) for this client
     *   'users',                     <String> - [Optional] Enable ILIAS userId restriction (regex/string-list) for this client
     *   'scopes',                    <String> - [Optional] Enable scope-support (regex/string-list) for tokens generated via this oauth2 client
     *   'description'                <String> - [Optional] Set a description for this oauth client
     *
     * Returns:
     *  array(
     *    'id' <Number> - ClientId of newly created database entry
     *  )
     */
    $app->post('/client', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function () use ($app) {
      try {
        // Delegate insert-operation to model
        $request  = $app->request();
        $clientId = Client::InsertClient($request);
        if ($clientId)
          $app->success(array( 'id' => $clientId ));
        else
          $app->halt(500, Client::MSG_CLIENT_EXISTS);
      }

      // Catch databases error (eg. client could not be created)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
      // Catch missing parameter
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
    });


    /**
     * Route: [PUT] /v2/admin/client | /v2/admin/client/:clientId
     * [Admin required]
     *  Updates an existing oauth2 clients database entry.
     *  Parameter terms should correspond to Oauth2 Spec: https://tools.ietf.org/html/rfc6749
     *
     * Parameters: [/v2/admin/client]
     *  'id',                        <Number> - Internal clientId for this oauth2 client (only used to reference a client internally)
     *
     * Parameters: [Both]
     *  'api_key',                   <String> - [Optional] API-Key used by this oauth2 client (should be unique!)
     *  'api_secret',                <String> - [Optional] Enable secret (password) for client-credentials
     *  'cert_serial',               <String> - [Optional] Enable SSL-Certificate 'serial' restriction (regex/string-list) for client-credentials
     *  'cert_issuer',               <String> - [Optional] Enable SSL-Certificate 'issuer' restriction (regex/string-list) for client-credentials
     *  'cert_subject',              <String> - [Optional] Enable SSL-Certificate 'subject' restriction (regex/string-list) for client-credentials
     *  'redirect_uri',              <String> - [Optional] Redirection URL for the redirection-based grant flow
     *  'consent_message',           <String> - [Optional] Message displayed during authorization of client-application
     *                                          (Steps (B) and (C) for Authorization-Code and the Implicit grant-types.)
     *  'client_credentials_userid', <Number> - [Optional] UserId attached to tokens when using client-credentials
     *  'grant_client_credentials',  <Bool>   - [Optional] Enable grant-type: Client-Credentials
     *  'grant_authorization_code',  <Bool>   - [Optional] Enable grant-type: Authorization-Code
     *  'grant_implicit',            <Bool>   - [Optional] Enable grant-type: Implicit
     *  'grant_resource_owner',      <Bool>   - [Optional] Enable grant-type: Resource-Owner
     *  'refresh_authorization_code',<Bool>   - [Optional] Enable Refresh-Token for Authorization-Code grant-type
     *  'refresh_resource_owner',    <Bool>   - [Optional] Enable Refresh-Token for Resource-Owner grant-type
     *  'grant_bridge',              <String> - [Optional] Enable two-way ILIAS-REST login-bridge
     *                                          (i = ILIAS Session --> oAuth2 Token)
     *                                          (o = ILIAS Session <-- oAuth2 Token)
     *                                          (b = ILIAS Session <-> oAuth2 Token)
     *   'ips',                       <String> - [Optional] Enable IP restriction (regex) for this client
     *   'users',                     <String> - [Optional] Enable ILIAS userId restriction (regex/string-list) for this client
     *   'scopes',                    <String> - [Optional] Enable scope-support (regex/string-list) for tokens generated via this oauth2 client
     *   'description'                <String> - [Optional] Set a description for this oauth client
     *
     * Returns:
     *  array(
     *    'id' <Number> - Updated clientId
     *  )
     */
    $app->put('/client/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        // Delegate update to model
        $request  = $app->request();
        if (Client::UpdateClient($clientId, $request))
          $app->success(array( 'id' => $clientId ));
        else
          $app->halt(500, Client::MSG_CLIENT_NOT_UPDATED);
      }

      // Catch databases error (eg. client could not be found/updated)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [DELETE] /v2/admin/client | /v2/admin/client/:clientId
     * [Admin required]
     *  Removes an existing oauth2 client from the database.
     *  Parameter terms should correspond to Oauth2 Spec: https://tools.ietf.org/html/rfc6749
     *
     * Parameters: [/v2/admin/client]
     *  'id' <Number> - Internal clientId for this oauth2 client
     *
     * Returns:
     *  array(
     *    'id' <Number> - ClientId of deleted oauth2 client
     *  )
     */
    $app->delete('/client/:id', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      // Delete permission via given clientId
      if (Database\RESTclient::deleteByPrimary($clientId) > 0)
        $app->success(array( 'id' => intval($clientId) ));

      // Deleting failed! (No entry to begin with?)
      else
        $app->halt(500, Client::MSG_CLIENT_NOT_DELETED);
    });


    /**
     * Route: [GET] /v2/admin/permissions/:clientId
     * [Admin required]
     *  Returns a list of all available permissions for the given client.
     *
     * Returns:
     *  {
     *    'id': {
     *      'id',       <Number> - Internal id for this permission
     *      'api_id',   <String> - Association between permission and client via api_id = clientId
     *      'pattern',  <String> - Route that the client will have access to
     *      'verb',     <String> - Operation that the client will have access to [GET]/[PUT]/[POST]/[DELETE]
     *    }
     *  }
     */
    $app->get('/permissions/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        // Fetch permission by given client-id
        $clientId     = intval($clientId);
        $where        = sprintf('api_id = %d', Database\RESTpermission::quote($clientId, 'integer'));
        $permissions  = Database\RESTpermission::fromWhere($where, true);

        // Iterate over all permissions
        $result = array();
        foreach ($permissions as $permission) {
          // Extract permissions and complete table-row
          $row          = $permission->getRow();
          $id           = $row['id'];

          // Insert permission into result
          $result[$id]  = $row;
        }

        // Send list of all permissions
        $app->success($result);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/admin/permission/:clientId
     * [Admin required]
     *  Adds a new permission with given parameters to the selected client.
     *
     * Parameters:
     *  id      <Number> - [Optional] Internal id for this permission
     *  pattern <String> - Route that the client will have access to
     *  verb    <String> - Operation that the client will have access to [GET]/[PUT]/[POST]/[DELETE]
     *
     * Returns:
     *  array(
     *    'id' <Number> - PermissionId of newly created database entry for this permission
     *  )
     */
    $app->post('/permission/:clientId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($clientId) use ($app) {
      try {
        // Delegate insert to model
        $request      = $app->request();
        $permissionId = Permission::InsertPermission($request);
        if ($permissionId)
          $app->success(array( 'id' => $permissionId ));
        else
          $app->halt(500, Client::MSG_EXISTS);
      }

      // Catch missing parameter
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [GET] /v2/admin/permission/:permissionId
     * [Admin required]
     *  Deletes the clients permission with selected permissionId
     *
     * Returns:
     *  array(
     *    'id' <Number> - PermissionId of deleted database entry for this permission
     *  )
     */
    $app->delete('/permission/:permissionId', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($permissionId) use ($app) {
      // Delete permission via given permissionId
      if (Database\RESTpermission::deleteByPrimary($permissionId) > 0)
        $app->success(array( 'id' => intval($permissionId) ));

      // Deleting failed! (No entry to begin with?)
      else
        $app->halt(500, Client::MSG_NOT_DELETED);
    });


    /**
     * Route: [GET] /v2/admin/config/:key
     * [Admin required]
     *  Returns the current value for the requested config key.
     *
     * Returns:
     *  {
     *    'key': 'value'
     *  }
     */
    $app->get('/config/:key', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($key) use ($app) {
      try {
        // Fetch settings
        $settings = Database\RESTconfig::fetchSettings($key);

        // Send settings (contains requested key)
        $app->success($settings);
      }

      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });


    /**
     * Route: [PUT] /v2/admin/config/:key
     * [Admin required]
     *  Updates a config settings with a new value.
     *
     * Parameters:
     *  value <String> - New value
     *
     * Returns:
     *  {
     *    'key': 'value'
     *  }
     */
    $app->put('/config/:key', Libs\RESTAuth::checkAccess(Libs\RESTAuth::ADMIN), function ($key) use ($app) {
      try {
        // Fetch new value from request
        $request  = $app->request();
        $value    = $request->getParameter('value', null, true);

        // Fetch current table entry and update with new value
        $settings = Database\RESTconfig::fromSettingName($key);
        $settings->setKey('setting_value', $value);
        $settings->update();

        // Send settings (contains requested key)
        $app->success(array(
          $key => $value,
        ));
      }

      // Catch missing parameter
      catch (Libs\Exceptions\Parameter $e) {
        $e->send(400);
      }
      // Catch database error (Should never happen, unless maybe no oauth2 clients exist...)
      catch (Libs\Exceptions\Database $e) {
        $e->send(500);
      }
    });
  });
});
