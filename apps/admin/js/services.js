// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 *
 */
var services = angular.module('myApp.services', ['ngResource']);


app.factory('authentication', function() {
    return {
        isAuthenticated: false,
        user: null,
        access_token: null,
        manual_login: false
    };
});


/*
 *
 */
services.factory('TokenHandler', ['authentication', function(authentication) {
    var tokenHandler = {};

    tokenHandler.get = function() {
        var token = authentication.access_token;
        return token;
    };

    // wrap given actions of a resource to send auth token with every
    // request
    tokenHandler.wrapActions = function( resource, actions ) {
        // copy original resource
        var wrappedResource = resource;
        for (var i=0; i < actions.length; i++) {
            tokenWrapper( wrappedResource, actions[i] );
        };
        // return modified copy of resource
        return wrappedResource;
    };

    // wraps resource action to send request with auth token
    var tokenWrapper = function( resource, action ) {
        // copy original action
        resource['_' + action]  = resource[action];
        // create new action wrapping the original and sending token
        resource[action] = function( data, success, error){
            return resource['_' + action](
                angular.extend({}, data || {}, {token: tokenHandler.get()}, {Authorization:tokenHandler.get()}),
                success,
                error
            );
        };

    };
    return tokenHandler;
}]);


/*
 *
 */
services.value('getRestURL', function() {
    // Use value given by postvars
    if (postvars.inst_folder != "") {
        return postvars.inst_folder;
    }
    
    // Explode path from window.location, searching for "Customizing" folder
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
});


/*
 *
 */
services.provider('restAuth', function() {
    this.$get = function($resource, restIliasLoginURL, getRestURL) {
        var restURL = getRestURL();
        return $resource(restURL + restIliasLoginURL, {}, {
            auth: { method: 'POST', params: {} }
        });
    };
});

services.provider('restAuthToken', function() {
    this.$get = function($resource, restTokenURL, getRestURL) {
        var restURL = getRestURL();
        return $resource(restURL + restTokenURL, {}, {
            auth: { method: 'POST', params: {}, ignoreLoadingBar: true }
        });
    };
});




services.provider('restClients', function() {
    this.$get = function($resource, TokenHandler, restClientsURL, getRestURL) {
        var restURL = getRestURL();
      
        var resource = $resource(restURL + restClientsURL, {}, {
            query: { method: 'GET', params: {}},
            create: { method: 'POST', params: {} }
        });
        resource = TokenHandler.wrapActions(resource, ['query','create']);
        return resource;
    };
});

services.provider('restClient', function() {
    this.$get = function($resource, TokenHandler, restClientURL, getRestURL) {
        var restURL = getRestURL();
        
        var resource =  $resource(restURL + restClientURL, {}, {
            show: { method: 'GET' },
            update: { method: 'PUT', params: {id: '@id'}},
            'delete': { method: 'DELETE', params: {id: '@id'}},                         // Use quotes to pamper the syntax-validator (delete is a keyword)
        });
        resource = TokenHandler.wrapActions( resource, ['show', 'update', 'delete'] );
        return resource;
    };
});

services.provider('restRoutes', function() {
    this.$get = function($resource, restRoutesURL, getRestURL) {
        var restURL = getRestURL();
        return $resource(restURL + restRoutesURL, {}, {
            query: { method: 'GET', params: {} } 
        });
    };
});
