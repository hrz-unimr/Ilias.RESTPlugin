// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 * This variable stores all AngularJS controllers
 */
var ctrl = angular.module('myApp.controllers', []);


/*
 * This is the "main-menu" controller, that handles displaying
 * navigation (breadcrumbs) and login-information.
 * In addition, all other controllers inherit from this one.
 */
ctrl.controller("MainCtrl", function($scope, $location, $filter, breadcrumbs, authentication, restEndpoint, $route) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        // Add breadcrumbs to scope and setup translations
        breadcrumbs.options = {
            'LABEL_LOGIN': $filter('translate')('LABEL_LOGIN'),
            'LABEL_OFFLINE': $filter('translate')('LABEL_OFFLINE'),
            'LABEL_CLIENTS': $filter('translate')('LABEL_CLIENTS'),
            'LABEL_EDIT': $filter('translate')('LABEL_EDIT')
        };
        $scope.breadcrumbs = breadcrumbs;

        // Add authentification and ebdpoint to scope
        $scope.authentication = authentication;
        $scope.restEndpoint = restEndpoint;

        // Required for translation data to work
        $scope.translationData = {
            authentication: authentication
        };

    };


    /*
     * Used to check if currently on the login route.
     * Required to show/hide certain (warning) elements.
     */
    $scope.isLoginRoute = function() {
        return $location.path().toLowerCase() == '/login';
    };

    /*
     *  Reload current view
     */
    $scope.reload = function() {
         $route.reload();
    }


    $scope.resetTimer = function() {
        $scope.$broadcast('timer-reset');
        $scope.$broadcast('timer-start');
    }


    $scope.$on('loginPerformed', function (event) {
        $scope.resetTimer();
    });

    // Do the initialisation
    $scope.init();
});


/*
 * This controller handles all client-list related functionality,
 * such as displaying, adding and removing clients as well as
 * redirecting to client-edit route.
 */
ctrl.controller("ClientListCtrl", function($scope, $location, $filter, dialogs, clientStorage, restClient, restClients, apiKey) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        // Warning message (mostly for when REST calls fail)
        $scope.warning = null;

        // Load clients into AngularJS via REST
        $scope.loadClients();
    };



    /*
     * Fetch all clients via REST and inserts them into the $scope
     * such that they will/may be $watch'ed by AngularJS.
     */
    $scope.loadClients = function() {
        // Do an AJAJ REST call
        restClients.query(
            // Data
            {},
            // Success
            function(response) {
                // Enough access rights
                if (response.status == "success") {
                    clientStorage.setClients(response.clients);
                    $scope.clients = clientStorage.getClients();
                }
                // Probably insufficient access rights
                // Note: We could additionally check response.msg
                else {
                    $scope.authentication.logout();
                    $scope.authentication.setError($filter('translate')('AUTH_PERM'));
                }
            },
            // Failure
            function(response) {
                $scope.warning = $filter('restInfo')($filter('translate')('NO_CLIENTS'), response.status, response.data);
            }
        );
    };


    /*
     * Creates a new client with default settings (locally only).
     * Client will be commited via REST from inside the EditClientCtrl.
     */
    $scope.createNewClient = function() {
        // Add a default client locally
        var current = clientStorage.getDefault();
        clientStorage.addClient(current);
        clientStorage.setCurrent(current);

        // Redirect
        $location.path("/clientlist/clientedit");
    };


    /*
     * Updates a client by forwarding all changes done via AngularJS forms
     * via REST. ($scope is already up-to-date)
     */
    $scope.editClient = function(client) {
        // Update remotely
        clientStorage.setCurrent(client);

        // Redirect
        $location.path("/clientlist/clientedit");
    };


    /*
     * Deletes a client via REST and updates the $scope
     * such that all views get updated as well.
     */
    $scope.deleteClient = function(index) {
        // Open a warning when deleting a client
        // Adds a special warning when trying to delete the Admin-Panel API-Key
        var dialog;
        if ($scope.clients[index].api_key != apiKey)
            dialog = dialogs.confirm($filter('translate')('DIALOG_DELETE'), $filter('translate')('DIALOG_DELETE_MSG'));
        else
            dialog = dialogs.confirm($filter('translate')('DIALOG_DELETE_AP'), $filter('translate')('DIALOG_DELETE_AP_MSG'));

        // Start remote deletion once confirmed
        dialog.result.then(function(button){
            // Remove client in AngularJS
            var client = $scope.clients.splice(index, 1)[0];

            // Remove client remotely
            // Note: Use array-notation to pamper the syntax-validator (delete is a keyword)
            restClient['delete'](
                // Data
                {id: client.id},
                // Success
                function (response) { },
                // Failure
                function (response) {
                    $scope.warning = $filter('restInfo')($filter('translate')('DEL_FAILED_REMOTE'), response.status, response.data);
                }
            );
        });
    };


    // Do the initialisation
    $scope.init();
});


/*
 * This controller handles all functionality related to editing a client, such
 * as loading and formating routes, permissions, remotely applying changes and
 * generating random keys and secrets.
 */
ctrl.controller("ClientEditCtrl", function($scope, $filter, dialogs, clientStorage, restClient, restClients, $location, restRoutes, apiKey) {
    /*
     * Replaces an 'x' with another randomly permuated character.
     * Used to generate random keys and secrets.
     * (For internal use only)
     */
    var randomize = function(c) {
        var r = Math.random() * 16 | 0;
        var v = ((c == 'x') ? r : (r & 0x3 | 0x8));

        return v.toString(16);
    }


    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        // Set current client on $scope
        $scope.current = clientStorage.getCurrent();

        // Store old key [by value!] (to see if it changed)
        $scope.oldKey = $scope.current.api_key;

        // Fetch available routes
        restRoutes.get(function(response) {
            $scope.routes = response.routes;
        });
    };


    /*
     * Go back to list of clients. (Looks cleaned inside template)
     */
    $scope.goBack = function() {
        $location.url("/clientlist");
    };


    /*
     * Format permissions into easily readable format.
     * Mainly used for <select> -> <option> formatting.
     */
    $scope.formatPermissionOption = function(route, verb) {
        return '['+verb+"] "+route;
    };


    /*
     * Adds a new permission to the $scope to eventually be
     * saved remotely via REST once the clients changes are commited.
     */
    $scope.addPermission = function(permission) {
        // Make sure no empty array is appended to
        if (!angular.isDefined($scope.current.permissions) || $scope.current.permissions == null)
            current.permissions = [];

        // Strip auth-middleware and add permission
        $scope.current.permissions.push({
            pattern: permission.pattern,
            verb: permission.verb,
        });
    };


    /*
     * Remove a permission from the $scope to eventually be
     * saved remotely via REST once the clients changes are commited.
     */
    $scope.deletePermission = function(index) {
        $scope.current.permissions.splice(index, 1);
    };


    /*
     * Generate a random API-Key
     */
    $scope.createRandomApiKey = function() {
        $scope.current.api_key = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, randomize);
    };


    /*
     * Generate a random API-Secret
     */
    $scope.createRandomApiSecret = function() {
        $scope.current.api_secret = 'xxxx.xxxx-xx'.replace(/[xy]/g, randomize);
    };


    /*
     * Save all local ($scope) client changes or create the new client remotely by
     * invoking the corresponding REST AJAJ call.
     */
    $scope.saveClient = function() {
        // Create a new client with $scope data
        if ($scope.current.id==-1) {
            restClients.create({
                // Data
                    api_key: $scope.current.api_key,
                    api_secret:$scope.current.api_secret,
                    oauth2_redirection_uri : $scope.current.oauth2_redirection_uri,
                    oauth2_consent_message : $scope.current.oauth2_consent_message,
                    permissions: $scope.current.permissions,
                    oauth2_gt_client_active: $scope.current.oauth2_gt_client_active,
                    oauth2_gt_client_user: $scope.current.oauth2_gt_client_user,
                    oauth2_gt_authcode_active: $scope.current.oauth2_gt_authcode_active,
                    oauth2_gt_implicit_active: $scope.current.oauth2_gt_implicit_active,
                    oauth2_gt_resourceowner_active: $scope.current.oauth2_gt_resourceowner_active,
                    oauth2_user_restriction_active: $scope.current.oauth2_user_restriction_active,
                    oauth2_consent_message_active: $scope.current.oauth2_consent_message_active,
                    oauth2_authcode_refresh_active: $scope.current.oauth2_authcode_refresh_active,
                    oauth2_resource_refresh_active: $scope.current.oauth2_resource_refresh_active,
                    access_user_csv: $scope.current.access_user_csv
                },
                // Success
                function (response) {
                    if (response.status == "success") {
                        $scope.current.id = response.id;
                        clientStorage.addClient($scope.current);
                    }
                    else
                        $scope.warning = $filter('restInfo')($filter('translate')('SAVE_FAILED_UNKOWN'), response.status, response.data);

                    $location.url("/clientlist");
                },
                // Failure
                function (response) {
                    $scope.warning = $filter('restInfo')($filter('translate')('SAVE_FAILED_REMOTE'), response.status, response.data);
                    $location.url("/clientlist");
                }
            );
        }
        // Save changes (for existing client)
        else {
            // Do the actuall remote update via REST.
            // We will be reusing this code a bit below.
            var doUpdate = function () {
                restClient.update({
                        id: $scope.current.id
                    }, {
                        api_key: $scope.current.api_key,
                        api_secret:$scope.current.api_secret,
                        oauth2_redirection_uri : $scope.current.oauth2_redirection_uri,
                        oauth2_consent_message : $scope.current.oauth2_consent_message,
                        permissions: $scope.current.permissions,
                        oauth2_gt_client_active: $scope.current.oauth2_gt_client_active,
                        oauth2_gt_client_user: $scope.current.oauth2_gt_client_user,
                        oauth2_gt_authcode_active: $scope.current.oauth2_gt_authcode_active,
                        oauth2_gt_implicit_active: $scope.current.oauth2_gt_implicit_active,
                        oauth2_gt_resourceowner_active: $scope.current.oauth2_gt_resourceowner_active,
                        oauth2_user_restriction_active: $scope.current.oauth2_user_restriction_active,
                        oauth2_consent_message_active: $scope.current.oauth2_consent_message_active,
                        oauth2_authcode_refresh_active: $scope.current.oauth2_authcode_refresh_active,
                        oauth2_resource_refresh_active: $scope.current.oauth2_resource_refresh_active,
                        access_user_csv: $scope.current.access_user_csv
                    },
                    // Success
                    function (response) {
                        if (response.status != "success")
                            $scope.warning = $filter('restInfo')($filter('translate')('SAVE_FAILED_UNKOWN'), response.status, response.data);
                        $location.url("/clientlist");
                    },
                    // Failure
                    function (response) {
                        $scope.warning = $filter('restInfo')($filter('translate')('SAVE_FAILED_REMOTE'), response.status, response.data);
                        $location.url("/clientlist");
                    }
                );
            };

            // Check if the Admin-Panel key was changed and show a warning in this case
            if ($scope.oldKey == apiKey && $scope.oldKey != $scope.current.api_key) {
                var dialog = dialogs.confirm(
                    $filter('translate')('DIALOG_UPDATE'),
                    $filter('translate')('DIALOG_UPDATE_MSG')
                );
                dialog.result.then(doUpdate);
            }
            // Simply continue otherwise
            else
                doUpdate();
        }
    };


    // Do the initialisation
    $scope.init();
});


/*
 * This controller handles the login-page as well as all/most login related messages.
 */
ctrl.controller('LoginCtrl', function($scope, $location, $filter, apiKey, restAuth, restAuthToken) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        // Store postVars in $scope (they don't really change)
        $scope.postVars = postVars;

        // Try auto-login if required data is available
        if ($scope.authentication.tryAutoLogin())
            $scope.autoLogin();
    };


    /*
     * Tries to automatically log, by exchanging ILIAS session-id
     * and rtoken for an oauth2 bearer-token using a REST auth
     * interface.
     * Obviously this only works when this data is given, eg. when
     * comming from the ILIAS configuration dialog.
     */
    $scope.autoLogin = function () {
        // REST AJAJ invocation
        restAuth.auth({
            // Data
                api_key: $scope.postVars.apiKey,
                user_id: $scope.postVars.userId,
                session_id: $scope.postVars.sessionId,
                rtoken: $scope.postVars.rtoken,
                userName: $scope.postVars.userName,
            },
            // Success
            function (response) {
                // Login return OK (Login internally and redirect)
                if (response.status == "success") {
                    //console.log(JSON.stringify(response));
                    $scope.authentication.login($scope.postVars.userName, response.access_token);
                    $scope.postVars = {};
                    $location.url("/clientlist");
                    $scope.$emit('loginPerformed');
                // Login didn't return an OK (Logout internally and redirdct)
                } else {
                    $scope.authentication.logout();
                    $location.url("/login");
                }
            },
            // Failure  (Logout internally and redirdct)
            function (response){
                $scope.authentication.logout();
                $location.url("/login");
            }
        );
    };


    /*
     * Tries to login via form-data (given in login.html).
     * Requires a valid username / password pair as well
     * a an API-Key to generate a bearer-token that will
     * then be used to talk to the REST interface.
     */
    $scope.manualLogin = function () {
        // REST AJAJ invocation
        restAuthToken.auth({
            // Data
                grant_type: 'password',
                username: $scope.formData.userName,
                password: $scope.formData.password,
                api_key: apiKey
            },
            // Success
            function (response) {
                // Authorisation success (Login internally and redirect)
                if (response.token_type == "bearer") {
                    $scope.authentication.login($scope.formData.userName, response.access_token);
                    $location.url("/clientlist");
                    $scope.$emit('loginPerformed');
                // Authorisation failed  (Logout internally and redirdct)
                } else {
                    $scope.authentication.logout();
                    $location.url("/login");
                }
            },
            // Failure  (Logout internally and redirdct)
            function (response){
                console.log("NOT OK");
                // Try to decode the more common error-codes
                if (response.status == 401)
                    $scope.authentication.setError($filter('restInfo')($filter('translate')('LOGIN_REJECTED'), response.status, response.data));
                else if (response.status == 405)
                    $scope.authentication.setError($filter('restInfo')($filter('translate')('LOGIN_DISABLED'), response.status, response.data));
                else if (response.status != 200)
                    $scope.authentication.setError($filter('restInfo')($filter('translate')('LOGIN_UNKNOWN'), response.status, response.data));

                // Logout and redirect
                $scope.authentication.logout();
                $location.url("/login");
            }
        );
    };

    // Do the initialisation
    $scope.init();
});


/*
 * Simple controller that manages functionality of the route that
 * should be displayed IFF the REST-Interface can't be contacted.
 * Note: Currently this is only implemented for when the "connection" is
 * unavailable during page-load. (Nothing happens when the "connection"
 * is lost after AngularJS was loaded and initialized)
 */
ctrl.controller('OfflineCtrl', function($scope, $location, restEndpoint) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        // Convert URL to absolute [Cheat a bit >:->]
        var a = document.createElement('a');
        a.href = "/";

        // Set endpoints (for display purpose only)
        $scope.postEndPoint = a.href+postVars.restEndpoint;
        $scope.installDir = a.href+restEndpoint.getInstallDir();
    };


    /*
     * Retry connection by completly reloading page,
     * thus reloading AngularJS.
     */
    $scope.retry = function() {
        document.location.href = './';
    };


    // Do the initialisation
    $scope.init();
});
