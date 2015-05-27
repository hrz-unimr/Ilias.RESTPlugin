'use strict';

// Declare app level module which depends on filters, and services
var app = angular.module('mobileApp', [
    'ngRoute',
    'ngTouch',
    'ngResource',
    'xeditable',
    'mobileApp.filters',
    'mobileApp.directives',
    'mobileApp.services',
    'mobileApp.controllers',
    'ui.bootstrap',
    'ui.utils',
    'ngAnimate',
    'angular-loading-bar'
    //'angularUtils.directives.dirPagination'
]);

app.config(['$routeProvider', function($routeProvider, $locationProvider) {
    $routeProvider.when('/overview', {templateUrl: 'partials/m_overview.html', controller: 'defaultCtrl'});
    $routeProvider.when('/login', {templateUrl: 'partials/m_login.html', controller: 'LoginCtrl'});
    $routeProvider.when('/mycourses', {templateUrl: 'partials/m_mycourses.html', controller: 'defaultCtrl'});
    $routeProvider.when('/containercontent', {templateUrl: 'partials/m_container_content.html'});
    $routeProvider.when('/info', {templateUrl: 'partials/m_info.html'});
    $routeProvider.when('/calendar', {templateUrl: 'partials/m_calendar.html', controller: 'defaultCtrl'});
    $routeProvider.when('/contacts', {templateUrl: 'partials/m_contacts.html', controller: 'defaultCtrl'});
    //$routeProvider.otherwise({redirectTo: '/calendar'});
}]);

/*app.config(['$httpProvider', function ($httpProvider) {
    $httpProvider.defaults.headers.common['Authentication'] = 'XYZ';
}]);
*/

app.run(function(editableOptions) {
    editableOptions.theme = 'bs3'; // bootstrap3 theme. Can be also 'bs2', 'default'
});

app.run(function($window, $rootScope) {
    $rootScope.online = navigator.onLine;
    $window.addEventListener("offline", function () {
        $rootScope.$apply(function() {
            $rootScope.online = false;
        });
    }, false);
    $window.addEventListener("online", function () {
        $rootScope.$apply(function() {
            $rootScope.online = true;
        });
    }, false);
    // in controller: $scope.$watch('online', function(newStatus) { ... });
});

app.constant("baseUrl", "http://137.248.3.49:8888/ilias5");


app.config(function(restMDeskProvider){
    restMDeskProvider.setBaseUrl('http://137.248.3.49:8888/ilias5'); //http://localhost:8888
});

app.config(['cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
    cfpLoadingBarProvider.includeSpinner = false;
}]);

/*
app.run(function ($rootScope, AuthFactory) {

    $rootScope.$on('$viewContentLoaded', function () {
         console.log('first time');
        AuthFactory.auth({'user_id':$rootScope.user_id,'session_id':$rootScope.session_id, 'rtoken':$rootScope.rtoken,
'client_id':$rootScope.client_id});
    });

});

app.run(function(authentication, $rootScope, $location) {
    $rootScope.$on('$routeChangeStart', function(evt) {
    if(!authentication.isAuthenticated){
        //$location.url("/login");
    }
    event.preventDefault();
    });
});
*/


// Clear browser cache (in development mode)
// http://stackoverflow.com/questions/14718826/angularjs-disable-partial-caching-on-dev-machine
app.run(function ($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function () {
        $templateCache.removeAll();
    });
});

app.run(function (restMDesk, $rootScope, $location) {
   /* $rootScope.$on('$viewContentLoaded', function () {
        $templateCache.removeAll();
    });*/
    console.log("Requesting mdesk data...");
    restMDesk.getResource().query({}, function(response){
        console.log("Mdesk REST response ",response);
        $rootScope.mdeskData = response;
        $rootScope.repositoryItems = response.ritems;
        $rootScope.personalDesktop = response.mypersonaldesktop;
        console.log("Mdesk Personal Desktop ",$rootScope.personalDesktop);
        console.log("MDsk PD 2 ", response.mypersonaldesktop);
        $rootScope.myCourses = response.mycourses;
        $rootScope.myGroups = response.mygroups;

        $rootScope.events = response.calendar.events;
        $rootScope.icalurl = response.calendar.ical_url;
        $rootScope.user = response.user;
        $rootScope.contacts = response.contacts.my_contacts;
        $location.path("/overview");
    });

});

app.run(function ($rootScope) {
    $rootScope.debug = false;
});

app.run(function (authentication) {
    authentication.isAuthenticated = true;
});