'use strict';


// Declare app level module which depends on filters, and services
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
    'angular-loading-bar'
]);

app.constant("baseUrl", "http://localhost/restplugin.php");

app.config(['$routeProvider', function($routeProvider, $locationProvider) {
  $routeProvider.when('/clientedit', {templateUrl: 'partials/clientedit.html'});
  $routeProvider.when('/clientlist', {templateUrl: 'partials/clientlist.html'});
  $routeProvider.when('/login', {templateUrl: 'partials/login.html', controller: 'LoginCtrl'});
  $routeProvider.otherwise({redirectTo: '/clientlist'});
}]);

app.config(function(restRoutesProvider){
    restRoutesProvider.setBaseUrl(postvars.inst_folder);
});

app.config(function(restClientProvider){
    restClientProvider.setBaseUrl(postvars.inst_folder);
});

app.config(function(restClientsProvider){
    restClientsProvider.setBaseUrl(postvars.inst_folder);
});

app.config(function(restAuthProvider){
    restAuthProvider.setBaseUrl(postvars.inst_folder);
});


/*app.config(['$httpProvider', function ($httpProvider) {
    $httpProvider.defaults.headers.common['Authentication'] = 'XYZ';
}]);
*/

app.run(function(editableOptions) {
    editableOptions.theme = 'bs3'; // bootstrap3 theme. Can be also 'bs2', 'default'
});

//app.run(function ($rootScope, restAuth) {
   /*
    $rootScope.$on('$viewContentLoaded', function () {
         console.log('first time');
        AuthFactory.auth({'user_id':$rootScope.user_id,'session_id':$rootScope.session_id, 'rtoken':$rootScope.rtoken,
'api_key':$rootScope.api_key});
    });
    */
//});


app.run(function(authentication, $rootScope, $location) {
    $rootScope.$on('$routeChangeStart', function(evt) {
    if(!authentication.isAuthenticated){
        $location.url("/login");
    }
    event.preventDefault();
    });
});


// Clear browser cache (in development mode)
//
// http://stackoverflow.com/questions/14718826/angularjs-disable-partial-caching-on-dev-machine
app.run(function ($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function () {
        $templateCache.removeAll();

    });
});