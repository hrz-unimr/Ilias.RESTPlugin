// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * Declare app level module which depends on filters, and services
 */
var app = angular.module('myApp', [
    'ngRoute', 
    'ngResource', 
    'xeditable',
    'myApp.filters', 
    'myApp.services', 
    'myApp.directives',
    'myApp.controllers', 
    'ui.bootstrap', 
    'ui.utils', 
    'ngAnimate',
    'angular-loading-bar',
    'ng-breadcrumbs'
]);


/*
 * Some (important) global constants, all in one place
 */
app.constant('version',             '0.6');                               // Application version
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
        label: 'Login'
    });
    
    // Client-list
    $routeProvider.when('/clientlist', {
        templateUrl : 'partials/clientlist.html',
        label: 'Clients'
    });
    
    // Edit client
    $routeProvider.when('/clientlist/clientedit', {
        templateUrl: 'partials/clientedit.html',
        label: 'Edit'
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


// Make sure authentification is cheched on each view (route)
app.run(function(authentication, $rootScope, $location) {
    $rootScope.$on('$routeChangeStart', function(evt) {
        if (!authentication.isAuthenticated) {
            $location.url("/login");
        }
        
        event.preventDefault();
    });
});


// Set AngularJS-Editable theme to bootstrap3
app.run(function(editableOptions) {
    editableOptions.theme = 'bs3';
});
