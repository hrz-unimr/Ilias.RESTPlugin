// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * Declare main AngularJS application as well as
 * libraries that should get injected into the app.
 */
var app = angular.module('myApp', [
    'ngRoute',
    'ngResource',
    'ngSanitize',
    'ngAnimate',
    'ng-breadcrumbs',
    'angular-loading-bar',
    'ui.bootstrap',
    'ui.utils',
    'xeditable',
    'pascalprecht.translate',
    'dialogs.main',
    'timer',
    'myApp.translate.en-US',
    'myApp.translate.de-DE',
    'myApp.filters',
    'myApp.services',
    'myApp.directives',
    'myApp.controllers'
]);


/*
 * Some (important) global constants, all in one place
 */
app.constant('version',             '0.2');                               // Application version
app.constant('apiKey',              'apollo5');                           // API-Key used to log into admin-panel (via username/password)
app.constant('restIliasLoginURL',   '/v1/ilauth/rtoken2bearer');          // rToken to Bearer-Token Endpoint
app.constant('restTokenURL',        '/v1/oauth2/token');                  // Bearer-Token from Username, Password, API-Key pair Endpoint
app.constant('restClientsURL',      '/clients');                          // Client-list Endpoint
app.constant('restClientURL',       '/clients/:id');                      // View / Edit client Endpoint
app.constant('restRoutesURL',       '/routes');                           // Routes Endpoint


/*
 * Setup all routes (used to display different functionality)
 */
app.config(function($routeProvider) {
    // Login page
    $routeProvider.when('/offline', {
        templateUrl : 'partials/offline.html',
        label: 'LABEL_LATER', // This will be replaced later!
        controller: 'OfflineCtrl'
    });

    // Login page
    $routeProvider.when('/login', {
        templateUrl : 'partials/login.html',
        label: 'LABEL_LOGIN', // This will be replaced later!
        controller: 'LoginCtrl',
        resolve: {
            'RestEndpointData': function(restEndpoint){
                return restEndpoint.promise;
            }
        }
    });

    // Client-list
    $routeProvider.when('/clientlist', {
        templateUrl : 'partials/clientlist.html',
        label: 'LABEL_CLIENTS', // This will be replaced later!
        controller: 'ClientListCtrl',
        resolve: {
            'RestEndpointData': function(restEndpoint){
                return restEndpoint.promise;
            }
        }
    });

    // Edit client
    $routeProvider.when('/clientlist/clientedit', {
        templateUrl: 'partials/clientedit.html',
        label: 'LABEL_EDIT', // This will be replaced later!
        controller: 'ClientEditCtrl',
        resolve: {
            'RestEndpointData': function(restEndpoint){
                return restEndpoint.promise;
            }
        }
    });

    // Client-list
    $routeProvider.when('/checkout', {
        templateUrl : 'partials/checkout.html',
        label: 'LABEL_CHECKOUT', // This will be replaced later!
        controller: 'CheckoutCtrl',
        resolve: {
            'RestEndpointData': function(restEndpoint){
                return restEndpoint.promise;
            }
        }
    });

    // Default URL
    $routeProvider.otherwise({
        redirectTo : '/checkout'
    });
});


/*
 * Disable Spinner-Icon for Angular (REST-) Loadingbar
 */
app.config(function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = false;
});


/*
 * Make sure authentification is checked on each view (route)
 */
app.run(function($rootScope, $location, authentication, restEndpoint, $templateCache) {
    // Go to login page if not logged in (and we should not display the offline notification)
    $rootScope.$on('$routeChangeStart', function(event, current, previous, rejection) {
        if (!(authentication.isAuthenticated() || $location.url() == "/offline")) {
            $location.url("/login");
        }
    });

    // Something went wrong (rest-interfac down, maybe?)
    $rootScope.$on('$routeChangeError', function(event, current, previous, rejection) {
        if (rejection == "NoEndpoint") {
            $location.path("/offline");
        }
    });
});


/*
 * Set AngularJS-Editable theme to bootstrap3
 */
app.run(function(editableOptions) {
    editableOptions.theme = 'bs3';
});
