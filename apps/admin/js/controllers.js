// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 *
 */
var app = angular.module('myApp.controllers', []);


/*
 *
 */
app.controller("MainCtrl", function($scope, breadcrumbs, authentication, restEndpoint) {
    $scope.breadcrumbs = breadcrumbs;
    $scope.authentication = authentication;
    $scope.restEndpoint = restEndpoint;
    
    
    $scope.isLoginRoute = function() {
        return $location.path().toLowerCase() == '/login';
    }
});


/*
 *
 */
app.controller("ClientListCtrl", function($scope, $location, dialogs, clientStorage, restClient, restClients, restInfoFilter, apiKey) {
    $scope.warning = null;
    
    
    $scope.init = function() {
        if ($scope.clients == null) 
            $scope.loadClients();
    };

    
    $scope.loadClients = function() {        
        restClients.query({},
            function(response) {
                if (response.status == "success") {
                    clientStorage.setClients(response.clients);
                    $scope.clients = clientStorage.getClients();
                }
                else {
                    $scope.authentication.logout();
                    $scope.authentication.setError('You have been logged out because you don\'t have enough permissions to access this menu.');
                }
            },
            function(response) {                
                $scope.warning = restInfoFilter('<strong>Warning:</strong> Could not contact REST-Interface to fetch client data! %INFO%', response.status, response.data);
            }
        );
    };

    
    $scope.createNewClient = function() {
        var current = clientStorage.getDefault();
        clientStorage.addClient(current);
        clientStorage.setCurrent(current);
        $location.path("/clientlist/clientedit");
    };

    
    $scope.editClient = function(client) {
        clientStorage.setCurrent(client);
        $location.path("/clientlist/clientedit");
    };

    
    $scope.deleteClient = function(index) {
        var doDelete = function(button){
            var client = $scope.clients.splice(index, 1)[0];
            
            // Note: Use array-notation + quotes to pamper the syntax-validator (delete is a keyword)
            restClient['delete']({id: client.id}, 
                function (response) { },
                function (response) {
                    var status = addslashes(response.status);
                    var data = addslashes(response.data);
                    
                    $scope.warning = restInfoFilter('<strong>Warning:</strong> Delete-Operation failed, could not contact REST-Interface! %INFO%', response.status, response.data);
                }
            );
        };
        
        var dialog;
        if ($scope.clients[index].api_key != apiKey) 
            dialog = dialogs.confirm('Delete Client', 'Do you really want to remove this client?');
        else 
            dialog = dialogs.confirm('Delete Admin-Panel Client', 'This clients API-Key is required by the the Admin-Panel, you should change the default api-key (inside app.js) first!<br/><br/>Do you really want to remove this client?');
        
        dialog.result.then(doDelete);
    };
    
    
    $scope.init();
});


/*
 *
 */
app.controller("ClientEditCtrl", function($scope, dialogs, clientStorage, restClient, restClients, $location, restRoutes, restInfoFilter, apiKey) {
    $scope.current = clientStorage.getCurrent();
    $scope.oldKey = $scope.current.api_key;
    $scope.routes = {};
    
    
    $scope.goBack = function() {
        $location.url("/clientlist");
    };

    
    restRoutes.get(function(response) {
        $scope.routes = response.routes;
    });


    $scope.label = function(route, verb) {
        return route + " ( " + verb + " )";
    };

    
    $scope.addPermission = function(permission) {
        if (!angular.isDefined($scope.current.permissions) || $scope.current.permissions == null) 
            current.permissions = [];
        $scope.current.permissions.push(permission);
    };

    
    $scope.deletePermission = function(index) {
        $scope.current.permissions.splice(index, 1);
    };

    
    $scope.createRandomApiKey = function() {
        $scope.current.api_key = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, randomize);
    };

    
    $scope.createRandomApiSecret = function() {
        $scope.current.api_secret = 'xxxx.xxxx-xx'.replace(/[xy]/g, randomize);
    };


    $scope.saveClient = function() {
        if ($scope.current.id==-1) {
            restClients.create({
                    api_key: $scope.current.api_key,
                    api_secret:$scope.current.api_secret,
                    oauth2_redirection_uri : $scope.current.oauth2_redirection_uri,
                    oauth2_consent_message : $scope.current.oauth2_consent_message,
                    permissions: angular.toJson($scope.current.permissions),
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
                function (data) {
                    if (data.status == "success") {
                        $scope.current.id = data.id;
                        clientStorage.addClient($scope.current);
                    }
                }, 
                function (data) {
                    var status = addslashes(response.status);
                    var data = addslashes(response.data);
                    
                    $scope.warning = restInfoFilter('<strong>Warning:</strong> Save-Operation failed, could not contact REST-Interface! %INFO%', response.status, response.data);
                }
            );
            
            $location.url("/clientlist");
        }
        else {
            var doUpdate = function () {
                restClient.update({
                        id: $scope.current.id,
                        data: {
                            api_key: $scope.current.api_key,
                            api_secret:$scope.current.api_secret,
                            oauth2_redirection_uri : $scope.current.oauth2_redirection_uri,
                            oauth2_consent_message : $scope.current.oauth2_consent_message,
                            permissions: angular.toJson($scope.current.permissions),
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
                        }
                    }, 
                    function (data) {}, 
                    function (data) {
                        var status = addslashes(response.status);
                        var data = addslashes(response.data);
                        
                        $scope.warning = restInfoFilter('<strong>Warning:</strong> Save-Operation failed, could not contact REST-Interface! %INFO%', response.status, response.data);
                    }
                );
                
                $location.url("/clientlist");
            };
            
            if ($scope.oldKey == apiKey && $scope.oldKey != $scope.current.api_key) {
                var dialog = dialogs.confirm('Update Admin-Panel Client', 'This clients API-Key is required by the the Admin-Panel, you should change the default api-key (inside app.js) first!<br/><br/>Do you really want to apply this changes?');
                dialog.result.then(doUpdate);
            }
            else {
                doUpdate();
            }
        }
        
        
    };
});


/*
 *
 */
app.controller('LoginCtrl', function($scope, $location, apiKey, restAuth, restAuthToken, restInfoFilter) {
    $scope.postVars = postVars;
    
    
    $scope.init = function() {
        if ($scope.authentication.tryAutoLogin()) 
            $scope.autoLogin();
    };
    
    
    $scope.isLoginRoute = function() {
        return true;
    }

    
    $scope.autoLogin = function () {
        restAuth.auth({
                api_key: $scope.postVars.apiKey, 
                user_id: $scope.postVars.userId, 
                session_id: $scope.postVars.sessionId, 
                rtoken: $scope.postVars.rtoken
            }, 
            function (response) {
                if (response.status == "success") {
                    $scope.postVars = {};
                    $scope.authentication.login(response.user, response.token.access_token);
                    $location.url("/clientlist");
                } else {
                    $scope.authentication.logout();
                    $location.url("/login");
                }
            },
            function (response){
                $scope.authentication.logout();
                $location.url("/login");
            }
        );
    };
    

    $scope.manualLogin = function () {
        restAuthToken.auth({
                grant_type: 'password', 
                username: $scope.formData.userName, 
                password: $scope.formData.password, 
                api_key: apiKey 
            },
            function (response) {
                if (response.token_type == "bearer") {
                    $scope.authentication.login(response.user, response.access_token);
                    $location.url("/clientlist");
                } else {
                    $scope.authentication.logout();
                    $location.url("/login");
                }
            },
            function (response){
                if (response.status == 401) {
                    $scope.authentication.setError(restInfoFilter('<strong>Login failed:</strong> Username/Password combination was rejected. %INFO%', response.status, response.data));
                }
                else if (response.status == 405) {
                    $scope.authentication.setError(restInfoFilter('<strong>Login failed:</strong> REST-Interface is disabled! %INFO%', response.status, response.data));
                }
                else if (response.status != 200) {
                    $scope.authentication.setError(restInfoFilter('<strong>Login failed:</strong> An unknown error occured while trying to contact the REST-Interface. %INFO%', response.status, response.data));
                }
                
                $scope.authentication.logout();
                $location.url("/login");
            }
        );
    };

    
    $scope.init();
});
