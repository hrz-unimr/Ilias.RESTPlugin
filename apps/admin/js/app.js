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
 * Setup all routes (used to display different functionality)
 */
app.config(function($routeProvider, $locationProvider) {
    // Login page
    $routeProvider.when('/offline', {
        templateUrl : 'partials/offline.html',
        label: 'Offline',
        controller: 'OfflineCtrl'
    });
    
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
});


/*
 * Disable Spinner-Icon for Angular (REST-) Loadingbar
 */
app.config(function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = false;
});


/*
 * Add bearer-token to restClients & restClient resources
 * since we need to be authenticated to use those endpoints.
 */
app.config(function($provide, authenticationProvider) {
    // Wraps the given action on resource by prefixing the
    // old action with '_' and replacing it with a modified
    // one that has token information added.
    var addToken = function(resource, action) {
        // Move old action
        resource['_' + action]  = resource[action];
        
        // Create new action
        resource[action] = function(data, success, error) {
            // Call old action with extra data
            return resource['_' + action](
                angular.extend(
                    {}, 
                    data || {}, 
                    { token: authenticationProvider.getToken() }, 
                    { Authorization: authenticationProvider.getToken() }
                ),
                success,
                error
            );
        };

    };
    
    // Wraps all actions (array) on given resource
    var wrapActions = function(resource, actions) {
        // Wrap all actions
        for (var i = 0; i < actions.length; i++) 
            addToken(resource, actions[i]);

        // return modified resource
        return resource;
    };

    // Both /clients & /client/:id require a bearer-token to work
    $provide.decorator('restClients', function($delegate) {
        return wrapActions($delegate, ['query', 'create']);
    });
    $provide.decorator('restClient', function($delegate) {
        return wrapActions($delegate, ['show', 'update', 'delete']);
    });
});


/*
 * Make sure authentification is checked on each view (route)
 */
app.run(function($rootScope, $location, authentication, restEndpoint, $templateCache) {
    // Go to login page if not logged in (and we should not display the offline notification)
    $rootScope.$on('$routeChangeStart', function(evt) {
        if (!(authentication.isAuthenticated() || $location.url() == "/offline")) 
            $location.url("/login");
        
        event.preventDefault();
    });
    
    // Something went wrong (rest-interfac down, maybe?)
    $rootScope.$on('$routeChangeError', function(event, current, previous, rejection) {
        if (rejection == "NoEndpoint")
            $location.path("/offline");
        
        event.preventDefault();
    });
});


/*
 * Set AngularJS-Editable theme to bootstrap3
 */
app.run(function(editableOptions) {
    editableOptions.theme = 'bs3';
});