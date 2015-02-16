'use strict';

/* Controllers */

var app = angular.module('myApp.controllers', []);

app.controller("defaultCtrl", function($scope, $window, $resource, baseUrl, restClient, restClients, $location, authentication, restRoutes) {
    $scope.logindata = postvars;
    $scope.pageClass = 'page-main';
    $scope.clients = {};
    $scope.routes = {};
    $scope.currentClient = {id:-1, permissions:[]}; // Current Client
    $scope.newPermission = "";


    $scope.loadClients = function() {
        restClients.getResource().query({}, function(response) {
            console.log("Clients get response: ",response);
            $scope.clients = response.clients;
        });
    }

    restRoutes.getResource().get(function(response) {
        // $scope.data = response;
        $scope.routes = response.routes;
    });

    /*RoutesFactory.get(function(response) {
        // $scope.data = response;
        $scope.routes = response.routes;
    });*/

    $scope.createNewClient = function() {
        $scope.setClient();
        $location.path("/clientedit");
        console.log("Create new client invoked");
        console.log("edit obj id: "+$scope.currentClient.id);
    }

    $scope.editClient = function(client) {
        $scope.currentClient = client;
        $scope.currentClient.permissions = angular.fromJson($scope.currentClient.permissions);
        console.log(client);
        console.log("Edit Client invoked "+$scope.currentClient.id);
        $location.path("/clientedit");
    }

    $scope.setClient = function() { // default options for a new rest client
        $scope.currentClient = {permissions:[]};
        $scope.currentClient.id = -1;
        $scope.currentClient.oauth2_gt_client_active = 1;
        $scope.currentClient.oauth2_gt_client_user = 6;
        $scope.currentClient.oauth2_gt_resourceowner_active = 1;
    }

    $scope.backToListView = function() {
        $location.url("/clientlist");
    }

    $scope.label = function(route, verb) {
        return route + " ( "+verb+" )";
    }

    $scope.addPermission = function(permission) {
        console.log("Trying to add newPermission : " + permission);
        //console.log("Current Permission : " + $scope.newPermission);
        console.log($scope.currentClient);
        if (angular.isDefined($scope.currentClient.permissions) && $scope.currentClient.permissions!=null) {
            $scope.currentClient.permissions.push(permission);
        } else {
            $scope.currentClient.permissions = [];
            $scope.currentClient.permissions.push(permission);
        }

    }

    $scope.deletePermission = function(index) {
        /*if (!confirm('Confirm delete')) {
            return;
        }*/
        var aDelPerm= $scope.currentClient.permissions.splice(index, 1);
    }

    $scope.createRandomApiKey = function() {
        $scope.currentClient.api_key='xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {var r = Math.random()*16|0,v=c=='x'?r:r&0x3|0x8;return v.toString(16);});
    }

    $scope.createRandomApiSecret = function() {
        $scope.currentClient.api_secret='xxxx.xxxx-xx'.replace(/[xy]/g, function(c) {var r = Math.random()*16|0,v=c=='x'?r:r&0x3|0x8;return v.toString(16);});
    }


    $scope.saveClient = function() {
        if ($scope.currentClient.id==-1) {
            console.log("Creating a new Client");
            restClients.getResource().create(
                {
                    api_key: $scope.currentClient.api_key,
                    api_secret:$scope.currentClient.api_secret,
                    oauth2_redirection_uri : $scope.currentClient.oauth2_redirection_uri,
                    oauth2_consent_message : $scope.currentClient.oauth2_consent_message,
                    permissions: angular.toJson($scope.currentClient.permissions),
                    oauth2_gt_client_active: $scope.currentClient.oauth2_gt_client_active,
                    oauth2_gt_client_user: $scope.currentClient.oauth2_gt_client_user,
                    oauth2_gt_authcode_active: $scope.currentClient.oauth2_gt_authcode_active,
                    oauth2_gt_implicit_active: $scope.currentClient.oauth2_gt_implicit_active,
                    oauth2_gt_resourceowner_active: $scope.currentClient.oauth2_gt_resourceowner_active,
                    oauth2_user_restriction_active: $scope.currentClient.oauth2_user_restriction_active,
                    access_user_csv: $scope.currentClient.access_user_csv
                }, function (data) {
                console.log('Callback : ',data);
                if (data.status == "success") {
                    $scope.currentClient.id = data.id;
                    $scope.clients.push($scope.currentClient);
                }
            });
        } else {
            console.log("Saving client with id: "+$scope.currentClient.id);
            restClient.getResource().update(
                {
                    id: $scope.currentClient.id,
                    data: {
                        api_key: $scope.currentClient.api_key,
                        api_secret:$scope.currentClient.api_secret,
                        oauth2_redirection_uri : $scope.currentClient.oauth2_redirection_uri,
                        oauth2_consent_message : $scope.currentClient.oauth2_consent_message,
                        permissions: angular.toJson($scope.currentClient.permissions),
                        oauth2_gt_client_active: $scope.currentClient.oauth2_gt_client_active,
                        oauth2_gt_client_user: $scope.currentClient.oauth2_gt_client_user,
                        oauth2_gt_authcode_active: $scope.currentClient.oauth2_gt_authcode_active,
                        oauth2_gt_implicit_active: $scope.currentClient.oauth2_gt_implicit_active,
                        oauth2_gt_resourceowner_active: $scope.currentClient.oauth2_gt_resourceowner_active,
                        oauth2_user_restriction_active: $scope.currentClient.oauth2_user_restriction_active,
                        access_user_csv: $scope.currentClient.access_user_csv
                    }
                }, function (data) {
                console.log('Callback : ',data);
            });
        }
        $location.url("/clientlist");
    }

    $scope.deleteClient = function(index) {
        if (!confirm('Confirm delete')) {
            return;
        }
        var aDelItems = $scope.clients.splice(index, 1);
        var delItem = aDelItems[0];
        console.log('Invoking REST Delete for item id: '+delItem.id);
        restClient.getResource().delete({id: delItem.id}, function (data) {
            console.log('Delete Callback: ',data)
        });
    }

    $scope.isAuthenticated = function() {
        return authentication.isAuthenticated;
    }

    $scope.getUsername = function() {
        return authentication.user;
    }

    $scope.logout = function() {
        authentication.isAuthenticated = false;
        $location.url("/login");
    }

    $scope.getAccessToken = function() {
        return authentication.access_token;
    }
});

app.controller('AuthCtrl', function($scope, $filter, $http, restAuth) {
    $scope.data = {};
    restAuth.authorize(function(data) {
        console.log('Query auth provider');
        return restAuth.getResource().auth();
    });
});


app.controller('LoginCtrl', function($scope, authentication, $location, restAuth) {
    $scope.loginfromilias = function () {

        var v_user_id=$scope.logindata.user_id;
        var v_api_key = $scope.logindata.api_key;
        var v_session_id = $scope.logindata.session_id;
        var v_rtoken = $scope.logindata.rtoken;
        restAuth.getResource().auth({api_key: v_api_key, user_id: v_user_id, session_id: v_session_id, rtoken: v_rtoken }, function (data) {
            console.log('Auth Callback : ',data);
            if (data.status == "success") {
                $scope.token = data.token;//.access_token;
                $scope.access_token = $scope.token.access_token
                authentication.isAuthenticated = true;
                authentication.access_token = $scope.access_token;
                authentication.user = data.user;
                authentication.access_token = data.token.access_token;
                $location.url("/clientlist");
                $scope.loadClients();
            } else {
                authentication.isAuthenticated = false;
                //$location.url("/login");
            }
        });
    };
});
