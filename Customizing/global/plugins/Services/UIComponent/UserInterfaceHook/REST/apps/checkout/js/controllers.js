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

        // Add authentication and endpoint to scope
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


ctrl.controller("CheckoutCtrl", function($sce, $scope, $location, $filter, $resource, restApiRoutes, authentication, restEndpoint, $window) {
    /*
     * Called during (every) instantiation of this controller.
     *
     * Note: Using a dedicated method is cleaner and more reusable than
     * doing it directly inside the controller.
     */
    $scope.init = function() {
        $scope.loadApiRoutes();
        $scope.inputVerbIndicator = "GET";
        //$scope.requestParameters = "";
        $scope.setParameterTemplate();
        $scope.description = "";
        $scope.openNewWindow = 0;
        $scope.routeArray = [];
    };

    $scope.setParameterTemplate = function() {
        $scope.requestParameters = "{\"param1\":\"value1\", \"param2\":\"value2\"}";
        //",
        //    "param2":"value2"}';
    }
    /*
     * Format permissions into easily readable format.
     * Mainly used for <select> -> <option> formatting.
     */
    $scope.formatPermissionOption = function(route, verb, middleware) {
        return '['+verb+"] "+route;
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


    $scope.loadApiRoutes = function() {
        // Perform REST call
        restApiRoutes.query(
            // Data)
            {},
            // Success
            function(response) {
                $scope.routes = angular.fromJson(angular.toJson(response));
                $scope.permissions = response.permissions;
                //console.log($scope.routes);
                angular.forEach($scope.routes, function(value, key) {
                    //this.push(key + ': ' + value);
                    this.push('['+value.verb+'] '+value.pattern);
                }, $scope.routeArray);
            },
            // Failure
            function(response) {
                $scope.warning = $filter('restInfo')($filter('translate')('NO_CLIENTS'), response.status, response.data);
            }
        );
    }

    $scope.openTypeAhead = function () {
        $scope.inputRestEndpoint = "/";
    }
    
    $scope.onSelect = function ($item, $model, $label) {
        console.log("on Selected called.");
        console.log("item "+$item);
        console.log("model "+$model);
        console.log("label "+$label);

        $scope.$item = $item;
        $scope.$model = $model;
        $scope.$label = $label;
        var parts = $item.split("] ");
        var myverb = parts[0].replace("[","");
        var mypattern = parts[1];
        var route = {"verb" : myverb, "pattern": mypattern};
        $scope.setRoute(route);
        /*console.log("myverb: "+myverb);
        console.log("mypattern: "+mypattern);
        $scope.inputRestEndpoint = "here";*/
    };

    $scope.setRoute = function(route) {
        console.log("setRoute called - verb : "+route.verb);
        $scope.inputVerbIndicator = route.verb;
        $scope.inputRestEndpoint = route.pattern;
        // Call API for auxiliary information about the route.
        $scope.description = "";
        $scope.callDocAPI(route);
    }

    $scope.callDocAPI = function(route) {
        var docApiRoute = '/v1/docs/route/';
        var Res = $resource(restEndpoint.getEndpoint() + docApiRoute, {}, {
            query: { method:  'GET',  params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }}
        });
        var params = '{"verb":"'+route.verb+'","route":"'+route.pattern+'"}';
        Res.query( JSON.parse(params),
            // Success
            function (response) {
                try {
                    if (response['results'][0]!=null) {
                        //$scope.result = jsonPrettyPrint.prettyPrint(response);
                        $scope.description = response['results'][0]['description'];
                        $scope.requestParameters = response['results'][0]['parameters'];
                    }

                } catch (err) {
                    console.log(err);
                }
            },
            // Failure
            function (response){
                console.log('Could not retrieve DOC API information');
            }
        );
    }

    $scope.setVerb = function(verbStr) {
        $scope.inputVerbIndicator = verbStr;
    }

    $scope.checkout = function() {
        if ($scope.openNewWindow == 1) {
            $scope.restCallInNewWindow();
        } else {
            $scope.restCall();
        }
    }

    $scope.restCall = function() {
        var route = $scope.inputRestEndpoint;
        var Res = $resource(restEndpoint.getEndpoint() + route, {}, {
            query: { method:  'GET',  params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }},
            create: { method: 'POST', params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }},
            update: { method: 'PUT', params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }},
            remove: { method: 'DELETE', params: {}, headers: { 'Authorization': 'Bearer '+authentication.getToken() }}
        });


        var params = $scope.requestParameters;
        if (params=="") {
            params = '{}';
        }
        console.log('parameters: '+params);

        switch ($scope.inputVerbIndicator) {
            case "GET":
                console.log('Requesting via GET');
                Res.query( JSON.parse(params),
                    // Success
                    function (response) {
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    },
                    // Failure
                    function (response){
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    }
                );
                break;
            case "POST":
                console.log('Requesting via POST');
                Res.create( JSON.parse(params),
                    // Success
                    function (response) {
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    },
                    // Failure
                    function (response){
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    }
                );
                break;
            case "PUT" :
                console.log('Requesting via PUT');
                Res.update( JSON.parse(params),
                    // Success
                    function (response) {
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    },
                    // Failure
                    function (response){
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    }
                );
                break;
            case "DELETE" :
                Res.remove( JSON.parse(params),
                    // Success
                    function (response) {
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    },
                    // Failure
                    function (response){
                        $scope.result = jsonPrettyPrint.prettyPrint(response);
                    }
                );
                console.log('Requesting via Delete');
                break;
            default:
                console.log('Requesting via GET (default)');
        }
        
    }

    // This method needs to be invoked in case when the rest route requires
    // to initiate an ilias session and to perform a redirect, e.g. calling an ilias learn module
    // /v1/m/htlm/:ref_id
    // Note: this method does not support sending additional parameters
    $scope.restCallInNewWindow = function() {
        var route = $scope.inputRestEndpoint;
        var url = "";
        var params = $scope.requestParameters;
        if (params=="") {
            params = '{}';
        }
        var data = JSON.parse(params);
        var getParams = Object.keys(data).map(function(k) {
            return encodeURIComponent(k) + '=' + encodeURIComponent(data[k])
        }).join('&');
        console.log('Get param String : ' + getParams);
        if (route.indexOf('?') > -1) {
            url = restEndpoint.getEndpoint() + route + '&access_token='+authentication.getToken() + '&' + getParams;
        } else {
            url = restEndpoint.getEndpoint() + route + '?access_token='+authentication.getToken() + '&' + getParams;
        }
        $window.open(url);
    }

    // Do the initialisation
    $scope.init();
});

/*
 * This controller handles the login-page as well as all/most login related messages.
 */
ctrl.controller('LoginCtrl', function($scope, $location, $filter, apiKey, restAuth, restAuthToken) {
    /*
     * Called during (every) instantiation of this controller.
     */
    $scope.init = function() {
        $scope.formData = {};
        $scope.apikeyssel = api_keys_selection;
        //$scope.loginApiKey = "";
        $scope.formData.apiKey = "";
    };

    /*
     * Tries to login via form-data (given in login.html).
     * Requires a valid username / password pair as well
     * a an API-Key to generate a bearer-token that will
     * then be used to talk to the REST interface.
     */
    $scope.manualLogin = function () {
        // REST call
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
                    $scope.authentication.login($scope.formData.userName, response.access_token, $scope.formData.apiKey, response.ilias_client);
                    $location.url("/checkout");
                    $scope.$emit('loginPerformed');
                // Authorisation failed  (Logout internally and redirect)
                } else {
                    $scope.authentication.logout();
                    $location.url("/login");
                }
            },
            // Failure  (Logout internally and redirect)
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

    $scope.setLoginApiKey = function(apiKeyStr) {
        console.log('Set loign api key: '+apiKeyStr);
        $scope.formData.apiKey = apiKeyStr;
    }

    // Do the initialisation
    $scope.init();
});


/*
 * Simple controller that manages functionality of the route that
 * should be displayed IFF the REST-Interface can't be contacted.
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
     * Retry.
     */
    $scope.retry = function() {
        document.location.href = './';
    };


    // Do the initialisation
    $scope.init();
});
