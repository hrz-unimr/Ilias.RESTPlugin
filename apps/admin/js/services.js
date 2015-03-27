'use strict';

/*
 * Services
 *
 * http://draptik.github.io/blog/2013/07/28/restful-crud-with-angularjs/
 *
 */
var services = angular.module('myApp.services', ['ngResource']);

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

/*
services.factory('AuthFactory', function ($resource) {
    return $resource('../../../../../../../../../../../restplugin.php/v1/ilauth/rtoken2bearer', {}, {
        auth: { method: 'POST', params: {} } //, headers : {'Content-Type': 'application/x-www-form-urlencoded'} }
    })
});
*/

services.provider('restAuth', function() {
    this.baseUrl = '';
    this.$get = function($resource, TokenHandler) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                return $resource(baseUrl + '/restplugin.php/v1/ilauth/rtoken2bearer', {}, {
                    auth: { method: 'POST', params: {} } //, headers : {'Content-Type': 'application/x-www-form-urlencoded'} }
                })
            }
        }
    };
    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});

services.provider('restAuthTokenEndpoint', function() {
    this.baseUrl = '';
    this.$get = function($resource, TokenHandler) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                return $resource(baseUrl + '/restplugin.php/v1/oauth2/token', {}, {
                    auth: { method: 'POST', params: {}, ignoreLoadingBar: true } //, headers : {'Content-Type': 'application/x-www-form-urlencoded'} }
                })
            }
        }
    };
    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});


app.factory('authentication', function() {
    return {
        isAuthenticated: false,
        user: null,
        access_token: null,
        manual_login: false
    }
});

services.provider('restClients', function() {
    this.baseUrl = '';
    this.$get = function($resource, TokenHandler) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                var resource = $resource(baseUrl + '/restplugin.php/clients', {}, {
                    query: { method: 'GET', params: {}},//, isArray: false },
                    create: { method: 'POST', params: {} } //, headers : {'Content-Type': 'application/x-www-form-urlencoded'} }
                    // create: {method: 'POST', params: {}, isArray:false, headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}}
                });
                resource = TokenHandler.wrapActions(resource, ["query","create"]);
                return resource;
            }
        }
    };

    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});

services.provider('restClient', function() {
    this.baseUrl = '';
    this.$get = function($resource, TokenHandler) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                var resource =  $resource(baseUrl+'/restplugin.php/clients/:id', {}, {
                    show: { method: 'GET' },
                    update: { method: 'PUT', params: {id: '@id'}},
                    delete: { method: 'DELETE', params: {id: '@id'} }
                });
                resource = TokenHandler.wrapActions( resource, ["show", "update", "delete"] );
                //TokenHandler.get();
                return resource;
            }
        }
    };

    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});

services.provider('restRoutes', function() {
    this.baseUrl = '';
    this.$get = function($resource) {
        var baseUrl = this.baseUrl;
        return {
            getResource: function() {
                return $resource(baseUrl+'/restplugin.php/routes', {}, {
                    query: { method: 'GET', params: {} } //isArray: true
                })
            }
        }
    };

    this.setBaseUrl = function(baseUrl) {
        this.baseUrl = baseUrl;
    };
});