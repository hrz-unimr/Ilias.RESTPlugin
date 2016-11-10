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
        autoLogin: false,
        error: null,
        apiKey: "",
        ip: postVars.ip,
        iliasClient: null,
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

        handler.getApiKey = function() {
            return data.apiKey;
        };

        handler.getIliasClient = function() {
            return data.iliasClient;
        };

        handler.getIp = function() {
            return data.ip;
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
        handler.login = function(userName, token, apiKey, iliasClient) {
            // Store login data
            data.userName = userName;
            data.token = token;
            data.isAuthenticated = true;
            data.apiKey = apiKey;
            data.iliasClient = iliasClient;

            // Reset any (login-related) error-messages
            handler.setError(null);
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
            return data.error !== null;
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
 * This services tries to find the REST Endpoint, in the following order:
 *  - Using the 'restEndpoint' POST variable
 *  - Find the ILIAS main folder (<ILIAS>) by using 'window.location.pathname' and finding the 'Customizing' folder
 * Als contains a promise that gets resolved once endpoint has been found (or rejected if non is found).
 */
services.factory('restEndpoint', function($q, $http, restRoutesURL) {
    // Promise and endpoint variables
    var deferred = $q.defer();

    // Fetch installation directory from current path
    var pluginPath = '/Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/REST';
    var fullPath   = window.location.pathname;
    var index      = fullPath.indexOf(pluginPath);

    // Fallback value
    var installDir = pluginPath;

    // Extract from window-location
    if (index >= 0)
      installDir = fullPath.substring(0, index + pluginPath.length)
    // Extract from post-variable passed by ILIAS
    else if (postVars.restEndpoint !== "")
      installDir = postVars.restEndpoint.concat(pluginPath);

    // Append endpoint location
    var endPoint   = installDir.concat('/api.php');

    // Legacy-code, because I'm to lazy to update the other code >_>
    deferred.resolve(endPoint);

    // Return object containing:
    return {
        // Promise that is resolved once endpoint was found
        // or rejected once no endpoint was found.
        promise: deferred.promise,

        // Function to query found endpoint
        // Note: If promise got resolved this contains a valid endpoint!
        getEndpoint: function () {
            return endPoint;
        },

        // Function that returns the ILIAS main folder
        getInstallDir: function () {
            return installDir;
        }
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
 * REST API routes endpoint service. List all available routes callable with the API key.
 *  Use .query({}, successFunction, failureFunction);
 */
services.service('restApiRoutes', function($resource, restApiRoutesURL, restEndpoint, authentication) {
    return $resource(restEndpoint.getEndpoint() + restApiRoutesURL, {}, {
        query: { method: 'GET', headers: { 'Authorization': 'Bearer '+authentication.getToken() } }
    });
});
