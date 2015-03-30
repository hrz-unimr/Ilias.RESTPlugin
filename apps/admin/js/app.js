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

app.config([ 'cfpLoadingBarProvider', function(cfpLoadingBarProvider) {
	cfpLoadingBarProvider.includeSpinner = false;
} ])

app.constant("baseUrl", "http://localhost/restplugin.php");

app.config([ '$routeProvider', function($routeProvider, $locationProvider) {
	$routeProvider.when('/clientedit', {
		templateUrl : 'partials/clientedit.html'
	});
	$routeProvider.when('/clientlist', {
		templateUrl : 'partials/clientlist.html'
	});
	$routeProvider.when('/login', {
		templateUrl : 'partials/login.html'
	});
	$routeProvider.otherwise({
		redirectTo : '/clientlist'
	});
} ]);

app.config(function(restRoutesProvider) {
	restRoutesProvider.setBaseUrl(getInstallationFolder());
});

app.config(function(restClientProvider) {
	restClientProvider.setBaseUrl(getInstallationFolder());
});

app.config(function(restClientsProvider) {
	restClientsProvider.setBaseUrl(getInstallationFolder());
});

app.config(function(restAuthProvider) {
	restAuthProvider.setBaseUrl(getInstallationFolder());
});

app.config(function(restAuthTokenEndpointProvider) {
	restAuthTokenEndpointProvider.setBaseUrl(getInstallationFolder());
});

app.run(function(editableOptions) {
	editableOptions.theme = 'bs3';
});

app.run(function(authentication, $rootScope, $location) {
	$rootScope.$on('$routeChangeStart', function(evt) {
		if (!authentication.isAuthenticated) {
			$location.url("/login");
		}
		event.preventDefault();
	});
});

var getInstallationFolder = function() {
	if (postvars.inst_folder != "") {
		return postvars.inst_folder;
	}
	var pathArray = window.location.pathname.split('/');
	var iliasSubFolder = '';
	for (var i = 0; i < pathArray.length; i++) {
		if (pathArray[i] == "Customizing") {
			if (i > 1) {
				iliasSubFolder = "/" + pathArray[i - 1];
				break;
			}
		}
	}
	return iliasSubFolder;
};