'use strict';

/*
 * Services
 *
 * http://draptik.github.io/blog/2013/07/28/restful-crud-with-angularjs/
 *
 */
var services = angular.module('mobileApp.services', ['ngResource']);

services.value('version', '0.3');
services.value('debug', true);

services.factory('TokenHandler', ['authentication', function(authentication) {
    var tokenHandler = {};

    tokenHandler.get = function() {
        var token = authentication.access_token;
        console.log('Tokenhandler > get()' , token);
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
                angular.extend({}, data || {}, {token: tokenHandler.get()}, {'Authorization':tokenHandler.get()}),
                success,
                error
            );
        };

    };
    return tokenHandler;
}]);

services.factory('AuthFactory', function ($resource) {
    return $resource('/restplugin.php/v1/oauth2/token', {}, {
        auth: { method: 'POST', params: {} } //, headers : {'Content-Type': 'application/x-www-form-urlencoded'} }
    })
});

app.factory('authentication', function() {
    return {
        isAuthenticated: false,
        user: null,
        access_token: null
    }
});

services.factory('WorkspacesFactory', ['$resource', 'TokenHandler', function ($resource, TokenHandler) {
    var resource = $resource('/restplugin.php/admin/workspaces', {}, {
        query: { method: 'GET', params: {}}
    });
    resource = TokenHandler.wrapActions(resource, ["query"]);
    return resource;
}]);

services.factory('WorkspaceFactory', ['$resource', 'TokenHandler', function ($resource, TokenHandler) {
    var resource =  $resource('/restplugin.php/admin/workspaces/:id', {}, {
        show: { method: 'GET' }
    });
    resource = TokenHandler.wrapActions( resource, ["show"] );
    return resource;
}]);

services.factory('FileFactory', ['$resource', 'TokenHandler', function ($resource, TokenHandler) {
    var resource =  $resource('/restplugin.php/admin/files/:id', {}, {
        show: { method: 'GET' }
    });
    resource = TokenHandler.wrapActions( resource, ["show"] );
    return resource;
}]);


services.provider('restMDesk', function() {
    this.baseUrl = '';
    this.$get = function($resource) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                return $resource(baseUrl+'/restplugin.php/m/desk', {}, {
                    query: { method: 'GET', params: {} } //isArray: true
                })
            }
        }
    };

    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});


