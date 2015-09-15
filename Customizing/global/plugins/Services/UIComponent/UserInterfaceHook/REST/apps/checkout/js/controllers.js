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
            'LABEL_EDIT': $filter('translate')('LABEL_EDIT'),
            'LABEL_CHECKOUT': $filter('translate')('LABEL_CHECKOUT')
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


ctrl.controller("CheckoutCtrl", function($sce, $scope, $location, $filter, $resource, dialogs, clientStorage, restClient, restClients, authentication, restEndpoint, $window) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {

    };

    var jsonPrettyPrint = {
        replacer: function(match, pIndent, pKey, pVal, pEnd) {
            var key = '<span class=json-key>';
            var val = '<span class=json-value>';
            var str = '<span class=json-string>';
            var r = pIndent || '';
            if (pKey)
                r = r + key + pKey.replace(/[": ]/g, '') + '</span>: ';
            if (pVal)
                r = r + (pVal[0] == '"' ? str : val) + pVal + '</span>';
            return r + (pEnd || '');
        },
        prettyPrint: function(obj) {
            var jsonLine = /^( *)("[\w]+": )?("[^"]*"|[\w.+-]*)?([,[{])?$/mg;
            return JSON.stringify(obj, null, 2)
                .replace(/&/g, '&amp;').replace(/\\"/g, '&quot;')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(jsonLine, jsonPrettyPrint.replacer);
        }
    };


    $scope.checkout = function() {
        if ($scope.current.openNewWindow == 1) {
            $scope.restCallInNewWindow();
        } else {
            $scope.restCall();
        }
    }

    $scope.restCall = function() {
        var route = $scope.current.inputRestEndpoint;
        var Res = $resource(restEndpoint.getEndpoint() + route, {}, {
            query: { method:  'GET',  params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }},
            create: { method: 'POST', params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }}
        });

        Res.query(function (response) {
            var jsonString = JSON.stringify(response);
            $scope.current.result = jsonPrettyPrint.prettyPrint(response);
            //console.log('result '+jsonPrettyPrint.prettyPrint(jsonString));
        });
        /*restRoutes.get(function(response) {
            $scope.routes = response.routes;
        });*/
        console.log('Probe endpoint');
    }

    // This method needs to be invoked in case when the rest route requires
    // to initiate a ilias session and to perform a redirect, e.g. calling an ilias learn module
    // /v1/m/htlm/:ref_id
    $scope.restCallInNewWindow = function() {
        var route = $scope.current.inputRestEndpoint;
        var url = restEndpoint.getEndpoint() + route + '?access_token='+authentication.getToken();
        $window.open(url);
    }

   // $scope.oldKey = $scope.current.api_key;


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
                api_key: $scope.formData.apiKey,
            },
            // Success
            function (response) {
                // Authorisation success (Login internally and redirect)
                if (response.token_type == "bearer") {
                    $scope.authentication.login($scope.formData.userName, response.access_token, $scope.formData.apiKey);
                    $location.url("/checkout");
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
