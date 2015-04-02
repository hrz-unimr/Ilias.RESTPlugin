// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 *
 */
var services = angular.module('myApp.services', []);

/*
 * 
 */
app.factory('authentication', function($location) {    
    var authHandler = {};
    var data = {
        isAuthenticated: false,
        userName: null,
        token: null,
        autoLogin: (postVars.userId.length > 0),
        error: null,
    };
    
    authHandler.getToken = function() {
        return data.token;
    };
    authHandler.getUserName = function() {
        return data.userName;
    };
    authHandler.isAuthenticated = function() {
        return data.isAuthenticated;
    };
    
    authHandler.login = function(userName, token) {
        data.userName = userName;
        data.token = token;
        data.isAuthenticated = true;
        
        data.autoLogin = false;
        authHandler.setError();
    };
    authHandler.logout = function() {
        data.userName = null;
        data.token = null;
        data.isAuthenticated = false;
        $location.url("/login");
    };
    
    
    authHandler.tryAutoLogin = function() {
        return data.autoLogin;
    };
    
    authHandler.hasError = function() {
        return data.error != null;
    };
    authHandler.getError = function() {
        return data.error;
    };
    authHandler.setError = function(error) {
        data.error = error;
    };
    
    return authHandler;
});


/*
 *
 */
services.factory('TokenHandler', ['authentication', function(authentication) {
    var tokenHandler = {};

    tokenHandler.get = function() {
        var token = authentication.getToken();
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
// ADD AJAX setup for / or restplugin.php/
services.value('getRestURL', function() {
    // Use value given by postVars
    if (postVars.restEndpoint != "") {
        return postVars.restEndpoint;
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
    //console.log(iliasSubFolder);
    
    return iliasSubFolder+"/restplugin.php";
});


services.factory('clientService', function() {
    var handler = {};
    var data = {
        clients: [],
        current: null
    };
    
    handler.getClients = function() {
        return data.clients;
    };
    handler.setClients = function(clients) {
        data.clients = clients;
    };
    
    handler.hasClients = function() {
        return data.clients.length > 0;
    };
    
    handler.addClient = function(client) {
        return data.clients.push(client);
    };
    
    handler.getCurrent = function() {
        return data.current;
    };
    handler.setCurrent = function(client) {
        data.current = client;
    };
    
    handler.getDefault = function() {
        return {
            id: "-1",
            permissions: [],
            oauth2_gt_client_active: "1",
            oauth2_gt_client_user: "1",
            oauth2_gt_resourceowner_active: "1"
        };
    };
    
    return handler;
});



// SERVICE!!! oder besser sogar filter
function addslashes( str ) {
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}


function randomize(c) {
    var r = Math.random() * 16 | 0;
    var v = ((c == 'x') ? r : (r & 0x3 | 0x8));
    
    return v.toString(16);
}


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
