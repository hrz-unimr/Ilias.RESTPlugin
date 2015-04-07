// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 *
 */
var services = angular.module('myApp.services', []);


app.service('restEndpoint', function($q, $http, $location, restRoutesURL) {
    var deferred = $q.defer();
    var restEndpoint = ""
    
    var getInstallDir = function() {
        var pathArray = window.location.pathname.split('/');
        var iliasSubFolder = '';
        for (var i = 0; i < pathArray.length; i++) 
            if (pathArray[i] == "Customizing") 
                if (i > 1) {
                    iliasSubFolder = "/" + pathArray[i - 1];
                    break;
                }
        
        return iliasSubFolder;
    }
    
    if (postVars.restEndpoint != "") {
        restEndpoint = postVars.restEndpoint;
        deferred.resolve(restEndpoint);
    }
    else {
        var dir = getInstallDir();
        var apiPath = null;
        var phpPath = null;
        
        var apiQuery = $http.get(dir+restRoutesURL);
        var phpQuery = $http.get(dir+'/restplugin.php'+restRoutesURL);
        
        apiQuery.success(function(data, status, headers, config) {
            apiPath = true;
            if (phpPath != true) {
                restEndpoint = dir;
                deferred.resolve(restEndpoint);               
            }
        });
        phpQuery.success(function(data, status, headers, config) {
            phpPath = true;
            if (apiPath != true) {
                restEndpoint = dir+"/restplugin.php";
                deferred.resolve(restEndpoint);
            }                
        });
        
        apiQuery.error(function(data, status, headers, config) {
            apiPath = false;
            if (phpPath == false) {
                restEndpoint = null;
                deferred.reject("NoEndpoint");
            }
        });
        phpQuery.error(function(data, status, headers, config) {
            phpPath = false;
            if (apiPath == false) {
                restEndpoint = null;
                deferred.reject("NoEndpoint");
            }
        });
    }

    return {
        promise: deferred.promise,
        getEndpoint: function () {
            return restEndpoint;
        },
        hasEndpoint: function() {
            return restEndpoint != null && restEndpoint != "";
        },
        getInstallDir: getInstallDir
    };
});


/*
 * 
 */
app.factory('authentication', function($location) {    
    var handler = {};
    var data = {
        isAuthenticated: false,
        userName: null,
        token: null,
        autoLogin: (postVars.userId.length > 0),
        error: null,
    };
    
    handler.getToken = function() {
        return data.token;
    };
    handler.getUserName = function() {
        return data.userName;
    };
    handler.isAuthenticated = function() {
        return data.isAuthenticated;
    };
    
    handler.login = function(userName, token) {
        data.userName = userName;
        data.token = token;
        data.isAuthenticated = true;
        
        data.autoLogin = false;
        handler.setError();
    };
    handler.logout = function() {
        data.userName = null;
        data.token = null;
        data.isAuthenticated = false;
        $location.url("/login");
    };
    
    
    handler.tryAutoLogin = function() {
        return data.autoLogin;
    };
    
    handler.hasError = function() {
        return data.error != null;
    };
    handler.getError = function() {
        return data.error;
    };
    handler.setError = function(error) {
        data.error = error;
    };
    
    return handler;
});


/*
 *
 */
services.factory('TokenHandler', ['authentication', function(authentication) {
    var handler = {};

    handler.get = function() {
        var token = authentication.getToken();
        return token;
    };

    // wrap given actions of a resource to send auth token with every
    // request
    handler.wrapActions = function( resource, actions ) {
        // copy original resource
        var wrappedResource = resource;
        for (var i=0; i < actions.length; i++) 
            tokenWrapper( wrappedResource, actions[i] );
        
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
                angular.extend({}, data || {}, {token: handler.get()}, {Authorization:handler.get()}),
                success,
                error
            );
        };

    };
    return handler;
}]);


/*
 *
 */
services.factory('clientStorage', function() {
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


/*
 *
 */
services.factory('restAuth', function($resource, restIliasLoginURL, restEndpoint) {
    var restURL = restEndpoint.getEndpoint();
    
    return $resource(restURL + restIliasLoginURL, {}, {
        auth: { method: 'POST', params: {} }
    });
});


/*
 *
 */
services.factory('restAuthToken', function($resource, restTokenURL, restEndpoint) {
    var restURL = restEndpoint.getEndpoint();
    
    return $resource(restURL + restTokenURL, {}, {
        auth: { method: 'POST', params: {}, ignoreLoadingBar: true }
    });
});


/*
 *
 */
services.factory('restClients', function($resource, TokenHandler, restClientsURL, restEndpoint) {    
    var restURL = restEndpoint.getEndpoint();
  
    var resource = $resource(restURL + restClientsURL, {}, {
        query: { method: 'GET', params: {}},
        create: { method: 'POST', params: {} }
    });
    resource = TokenHandler.wrapActions(resource, ['query','create']);
    return resource;
});


/*
 *
 */
services.factory('restClient', function($resource, TokenHandler, restClientURL, restEndpoint) {
    var restURL = restEndpoint.getEndpoint();
    
    var resource =  $resource(restURL + restClientURL, {}, {
        show: { method: 'GET' },
        update: { method: 'PUT', params: {id: '@id'}},
        // Note: Use array-notation to pamper the syntax-validator (delete is a keyword)
        'delete': { method: 'DELETE', params: {id: '@id'}},
    });
    resource = TokenHandler.wrapActions( resource, ['show', 'update', 'delete'] );
    return resource;
});


/*
 *
 */
services.factory('restRoutes', function($resource, restRoutesURL, restEndpoint) {
    var restURL = restEndpoint.getEndpoint();
    
    return $resource(restURL + restRoutesURL, {}, {
        query: { method: 'GET', params: {} } 
    });
});
