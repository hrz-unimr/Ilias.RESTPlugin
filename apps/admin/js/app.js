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
    'xeditable',
    'myApp.filters', 
    'myApp.services', 
    'myApp.directives',
    'myApp.controllers', 
    'ui.bootstrap', 
    'ui.utils', 
    'ngAnimate',
    'angular-loading-bar',
    'ng-breadcrumbs',
    'pascalprecht.translate',
    'dialogs.default-translations',
    'dialogs.main'
]);


/*
 * Some (important) global constants, all in one place
 */
app.constant('version',             '1.0');                               // Application version
app.constant('apiKey',              'apollon');                           // API-Key used to log into admin-panel (via username/password)
app.constant('restIliasLoginURL',   '/v1/ilauth/rtoken2bearer');          // rToken to Bearer-Token Endpoint
app.constant('restTokenURL',        '/v1/oauth2/token');                  // Bearer-Token from Username, Password, API-Key pair Endpoint
app.constant('restClientsURL',      '/clients');                          // Client-list Endpoint
app.constant('restClientURL',       '/clients/:id');                      // View / Edit client Endpoint
app.constant('restRoutesURL',       '/routes');                           // Routes Endpoint


/*
 * Different templates used to render certain UI's
 */
app.config(['$routeProvider', function($routeProvider, $locationProvider) {
    // Login page
    $routeProvider.when('/login', {
        templateUrl : 'partials/login.html',
        label: 'Login',
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
        label: 'Clients',
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
        label: 'Edit',
        controller: 'ClientEditCtrl',
        resolve: {
            'RestEndpointData': function(restEndpoint){
                return restEndpoint.promise;
            }
        }
    });
    
    // Default URL
    $routeProvider.otherwise({
        redirectTo : '/clientlist'
    });
} ]);


// Disable Spinner-Icon for Angular (REST-) Loadingbar
app.config(['cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = false;
}]);


// Make sure authentification is checked on each view (route)
app.run(function(authentication, $rootScope, $location) {
    $rootScope.$on('$routeChangeStart', function(evt) {
        if (!authentication.isAuthenticated()) 
            $location.url("/login");
        
        event.preventDefault();
    });
});


// Set AngularJS-Editable theme to bootstrap3
app.run(function(editableOptions) {
    editableOptions.theme = 'bs3';
});