// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 * This variable stores all AngularJS controllers
 */
var services = angular.module('myApp.services', []);


/*
 * This service handles and stores user authentication,
 * which mainly includes username, bearer-token, error-handling
 * as well as returning authentification status.
 * Note: This needs to be a provider, because it needs to be injected in config
 * in order to decorate rest-services (restClients & restClient) which will
 * require bearer-tokens to function.
 */
services.provider('authentication', function() {
    // Stored data variables
    var data = {
        isAuthenticated: false,
        userName: null,
        token: null,
        autoLogin: (postVars.userId.length > 0 && postVars.sessionId.length > 0 && postVars.rtoken.length > 0),
        error: null,
    };
    
    // Return object containing login-related functions.
    this.$get = function($location) {
        // Returned object
        var handler = {};
        
        // Function that returns the bearer-token
        // of the currently logged-in user.
        handler.getToken = function() {
            return data.token;
        };
        
        // Function that returns the username
        // of the currently logged-in user.
        handler.getUserName = function() {
            return data.userName;
        };

        // Function that returns the current login-state
        // of the user. If this returns true then getToken
        // and getUserName should return valid data.
        handler.isAuthenticated = function() {
            return data.isAuthenticated;
        };
        
        // Function that internally logs-in the user
        // given by username (mostly for display purpose)
        // and the bearer-token, which is required to talk
        // to the REST interface.
        handler.login = function(userName, token) {
            // Store login data
            data.userName = userName;
            data.token = token;
            data.isAuthenticated = true;
            
            // Reset any (login-related) error-messages
            handler.setError();
        };
        
        // Function that internally logs-out the currently
        // logged-in user.
        handler.logout = function() {
            // Reset login-data
            data.userName = null;
            data.token = null;
            
            // Make sure user is logged out
            data.isAuthenticated = false;
            data.autoLogin = false;
            
            // Redirect to login
            $location.url("/login");
        };
        
        // Function that returns to if required data
        // for an automatic login by exchanging an ILIAS
        // session against a bearer-token is available.
        handler.tryAutoLogin = function() {
            return data.autoLogin;
        };
        
        // Handles all login-related error messages.
        // Used to display feedback on failure.
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
    };
    
    // Make token available to provider. We need to be able to
    // query the token when derocation restClients & restClient.
    this.getToken = function() {
        return data.token;
    }
});


/*
 * This service internally handles all client data recieved from 
 * and send to the REST Interface.
 * This includes adding, removing clients as well as setting the
 * current client (for editing) and providing default settings
 * for new clients.
 */
services.factory('clientStorage', function() {
    // Data object that stores all information
    var data = {
        clients: [],
        current: null
    };
    
    // Object that will be returned
    var handler = {};
    
    // Getter/Setter methods for the list of all clients
    handler.getClients = function() {
        return data.clients;
    };
    handler.setClients = function(clients) {
        data.clients = clients;
    };
    
    // Adds a new client (internally only!) to the list of clients
    handler.addClient = function(client) {
        return data.clients.push(client);
    };
    
    // getter/Setter methods for the current client (which eg.
    // might be edited in the /clientlist/clientedit route)
    handler.getCurrent = function() {
        return data.current;
    };
    handler.setCurrent = function(client) {
        data.current = client;
    };
    
    // Returns some default client settings, eg. 
    // creating a new client.
    handler.getDefault = function() {
        return {
            id: "-1",
            permissions: [],
            oauth2_gt_client_active: "1",
            oauth2_gt_client_user: "1",
            oauth2_gt_resourceowner_active: "1"
        };
    };
    
    // Return object
    return handler;
});


/*
 * This services tries to find the REST Endpoint, in the following order:
 *  - Using the 'restEndpoint' POST variable
 *  - Find the ILIAS main folder (<ILIAS>) by using 'window.location.pathname' and finding the 'Customizing' folder
 *   - Try to find restplugin.php endpoint using http.get(<ILIAS>/restplugin.php/routes)
 *   - Try to find sub-domain endpoint using http.get(<ILIAS>/routes)
 * Als contains a promise that gets resolved once endpoint has been found (or rejected if non is found).
 */
services.factory('restEndpoint', function($q, $http, restRoutesURL) {
    // Promise and endpoint variables
    var deferred = $q.defer();
    var restEndpoint = ""
    
    // Tries to find ILIAS main folder by looking at window.location.pathname
    // and finding the 'Customizing' folder
    var getInstallDir = function() {
        var pathArray = window.location.pathname.split('/');
        var iliasSubFolder = '';
        for (var i = 0; i < pathArray.length; i++) {
            if (pathArray[i] == 'Customizing') 
                break;
            if (pathArray[i] != '')
                iliasSubFolder += '/'+pathArray[i];
        }
        return iliasSubFolder;
    }
    
    var dir;
    // Use POST variable to establish endpoint
    // Note: Value is taken 'as-is', no AJAJ call is done to check correctness.
    if (postVars.restEndpoint != "") 
        dir = postVars.restEndpoint;
    
    // Tries to find endpoint by doing AJAJ calls to <ILIAS>/routes and <ILIAS>/restplugin.php
    // Whichever returns a success first will be used.
    else 
        // Find ILIAS main folder 
        dir = getInstallDir();
        
    // Stores wether AJAJ call was successfull (true) or not (false) [null means not done]
    var apiPath = null;
    var phpPath = null;
    
    // Initiate AJAJ call
    var apiQuery = $http.get(dir+restRoutesURL);
    var phpQuery = $http.get(dir+'/restplugin.php'+restRoutesURL);
    
    // AJAJ call succeeded, save result and forward to promise (resolve)
    // Note: Also make sure, that promise is only resolved once.
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
    
    // AJAJ call failed, forward to promise (reject)
    // Note: Also make sure, that promise is only rejected once.
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

    // Return object containing:
    return {
        // Promise that is resolved once endpoint was found
        // or rejected once no endpoint was found.
        promise: deferred.promise,
        
        // Function to query found endpoint
        // Note: If promise got resolved this contains a valid endpoint!
        getEndpoint: function () {
            return restEndpoint;
        },
        
        // Function that returns the ILIAS main folder
        getInstallDir: getInstallDir
    };
});


/*
 * REST Authentification via ILIAS session & api-key endpoint service.
 *  Use .auth({api_key: ..., user_id: ..., session_id: ..., rtoken: ...}, successFunction, failureFunction);
 */
services.service('restAuth', function($resource, restIliasLoginURL, restEndpoint) {
    return $resource(restEndpoint.getEndpoint() + restIliasLoginURL, {}, {
        auth: { method: 'POST', params: {} }
    });
});


/*
 * REST Authentification via username/password pair & api-key endpoint service.
 *  Use .auth({api_key: ..., username: ..., password: ..., grant_type: ...}, successFunction, failureFunction);
 */
services.service('restAuthToken', function($resource, restTokenURL, restEndpoint) {
    return $resource(restEndpoint.getEndpoint() + restTokenURL, {}, {
        auth: { method: 'POST', params: {}, ignoreLoadingBar: true }
    });
});


/*
 * REST Clients endpoint service. Query all clients or create new client.
 *  Use .query({}, successFunction, failureFunction);
 *  Use .create({api_key, api_secret, oauth2_redirection_uri, oauth2_consent_message , permissions, oauth2_gt_client_active, oauth2_gt_client_user, oauth2_gt_authcode_active, oauth2_gt_implicit_active, oauth2_gt_resourceowner_active, oauth2_user_restriction_active, oauth2_consent_message_active, oauth2_authcode_refresh_active, oauth2_resource_refresh_active, access_user_csv}, successFunction, failureFunction);
 */
services.service('restClients', function($resource, restClientsURL, restEndpoint) {    
    return $resource(restEndpoint.getEndpoint() + restClientsURL, {}, {
        query: { method: 'GET', params: {}},
        create: { method: 'POST', params: {} }
    });
});


/*
 * REST Client endpoint service. Query client information, change client data or delete client given by an id.
 *  Use .show({id}, successFunction, failureFunction);
 *  Use .update({id, {api_key, api_secret, oauth2_redirection_uri, oauth2_consent_message , permissions, oauth2_gt_client_active, oauth2_gt_client_user, oauth2_gt_authcode_active, oauth2_gt_implicit_active, oauth2_gt_resourceowner_active, oauth2_user_restriction_active, oauth2_consent_message_active, oauth2_authcode_refresh_active, oauth2_resource_refresh_active, access_user_csv}}, successFunction, failureFunction);
 *  Use .delete({id}, successFunction, failureFunction);
 */
services.service('restClient', function($resource, restClientURL, restEndpoint) {
    return $resource(restEndpoint.getEndpoint() + restClientURL, {}, {
        show: { method: 'GET' },
        update: { method: 'PUT', params: {id: '@id'}},
        // Note: Use array-notation to pamper the syntax-validator (delete is a keyword)
        'delete': { method: 'DELETE', params: {id: '@id'}},
    });
});


/*
 * REST routes endpoint service. List all available routes.
 *  Use .query({}, successFunction, failureFunction);
 */
services.service('restRoutes', function($resource, restRoutesURL, restEndpoint) {
    return $resource(restEndpoint.getEndpoint() + restRoutesURL, {}, {
        query: { method: 'GET', params: {} } 
    });
});
