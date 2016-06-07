// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 * This variable will manage all en-US translations
 */
var lang_en_us = angular.module('myApp.translate.en-US', []);
 

/*
 * Load en-US translations
 */
lang_en_us.config(function($translateProvider) {
    // Supply translations
    $translateProvider.translations('en-US', {
        // AngularJS - Diloalogs
        DIALOGS_ERROR: "Error",
        DIALOGS_ERROR_MSG: "An unknown error has occurred.",
        DIALOGS_CLOSE: "Close",
        DIALOGS_PLEASE_WAIT: "Please Wait",
        DIALOGS_PLEASE_WAIT_ELIPS: "Please Wait...",
        DIALOGS_PLEASE_WAIT_MSG: "Waiting on operation to complete.",
        DIALOGS_PERCENT_COMPLETE: "% Complete",
        DIALOGS_NOTIFICATION: "Notification",
        DIALOGS_NOTIFICATION_MSG: "Unknown application notification.",
        DIALOGS_CONFIRMATION: "Confirmation",
        DIALOGS_CONFIRMATION_MSG: "Confirmation required.",
        DIALOGS_OK: "OK",
        DIALOGS_YES: "Yes",
        DIALOGS_NO: "No",
        
        // Custom AngularJS texts
        DIALOG_DELETE: 'Delete Client',
        DIALOG_DELETE_MSG: 'Do you really want to remove this client?',
        DIALOG_DELETE_AP: 'Delete Admin-Panel Client',
        DIALOG_DELETE_AP_MSG: 'This clients API-Key is required by the the Admin-Panel, you should change the default api-key (inside app.js) first!<br/><br/>Do you really want to remove this client?',
        DIALOG_UPDATE: 'Update Admin-Panel Client',
        DIALOG_UPDATE_MSG: 'This clients API-Key is required by the the Admin-Panel, you should change the default api-key (inside app.js) first!<br/><br/>Do you really want to apply this changes?',
        
        // Breadcrumb labels
        LABEL_OFFLINE: 'Offline',
        LABEL_LOGIN: 'Checkout Login',
        LABEL_CLIENTS: 'Clients',
        LABEL_EDIT: 'Edit',
        LABEL_CHECKOUT: 'Checkout',
        
        // Warning & error-messages
        AUTH_PERM: 'You have been logged out because you don\'t have enough permissions to access this menu.',
        NO_CLIENTS: '<strong>Warning:</strong> Could not contact REST-Interface to fetch client data! %INFO%',
        DEL_FAILED_REMOTE: '<strong>Warning:</strong> Delete-Operation failed, could not contact REST-Interface! %INFO%',
        SAVE_FAILED_UNKOWN: '<strong>Warning:</strong> Save-Operation failed, for unknown reason! %INFO%',
        SAVE_FAILED_REMOTE: '<strong>Warning:</strong> Save-Operation failed, could not contact REST-Interface! %INFO%',
        LOGIN_REJECTED: '<strong>Login failed:</strong> Username/Password combination was rejected. %INFO%',
        LOGIN_DISABLED: '<strong>Login failed:</strong> REST-Interface is disabled! %INFO%',
        LOGIN_UNKNOWN: '<strong>Login failed:</strong> An unknown error occured while trying to contact the REST-Interface. %INFO%',
        
        // Index.php
        INDEX_TITLE: 'Checkout',
        INDEX_BRAND: 'ILIAS REST Plugin',
        INDEX_LOGGED_IN: 'Logged in as {{authentication.getUserName()}}@{{authentication.getApiKey()}} ({{authentication.getIp()}}) / {{authentication.getIliasClient()}}',
        INDEX_LOGOUT: 'Logout',
        INDEX_VERSION: 'Version',
        INDEX_POWERED: 'Powered by',
        
        // partials/login.html
        LOGIN_AUTO: 'You came from the ILIAS platform. Trying to login automatically ...',
        LOGIN_HEAD: 'REST Administration',
        LOGIN_USERNAME: 'Username',
        LOGIN_PASSWORD: 'Password',
        LOGIN_LOGIN: 'Login',
        LOGIN_APIKEY: 'API Key',
        
        // partials/clientlist.html
        LIST_ID: 'ID',
        LIST_API_KEY: 'API-Key',
        LIST_API_SECRET: 'API-Secret',
        LIST_GRANT_TYPES: 'Grant-Types',
        LIST_PERMISSION: 'Route-Permissions',
        LIST_MODIFY: 'Modify',
        LIST_DELETE: 'Delete',
        LIST_GRANT_AC: 'Authentification Code',
        LIST_GRANT_I: 'Implicit',
        LIST_GRANT_R: 'Resource Owner',
        LIST_GRANT_CC: 'Client Credentials',
        LIST_NEW_CLIENT: 'Create a new REST client',
        
        // partials/offline.html
        OFFLINE_WARNING: 'Warning!',
        OFFLINE_ISSUE: 'Could not locate/contact the REST-API endpoint(s) at:',
        OFFLINE_URL_POST: '[POST information]',
        OFFLINE_URL_FILE: '[File URL]',
        OFFLINE_URL_SUB: '[Subdomain URL]',
        OFFLINE_EXPLANATION: 'The Admin-Panel requires an active REST-Endpoint to work.',
        OFFLINE_RETRY: 'Retry',
        
        // partials/clientedit.html
        EDIT_BACK: 'Back',
        EDIT_SAVE: 'Save',
        EDIT_BASIC_INFORMATION: 'Basic information',
        EDIT_ID: 'ID',
        EDIT_API_KEY: 'API Key',
        EDIT_GENERATE: 'Generate',
        EDIT_API_SECRET: 'API Secret',
        EDIT_PERMISSIONS: 'Permissions (Scope)',
        EDIT_DELETE: 'Delete',
        EDIT_SELECT_PROMPT: '(Select please)',
        EDIT_ADD_ROUTE: 'Add route',
        EDIT_GRANT_TYPE: 'Grant-Type Settings',
        EDIT_GRANT_CC: 'Client Credentials',
        EDIT_ENABLED: 'Enabled',
        EDIT_GRANT_CC_USER: 'Client Credentials User',
        EDIT_GRANT_CC_USER_TEXT: 'Please specify only one ILIAS user_id here. This is typically a user that owns the administration role.',
        EDIT_GRANT_AC: 'Authorization Code',
        EDIT_GRANT_AC_REFREH: 'Enable Refresh-Token support for Authorization Code',
        EDIT_GRANT_I: 'Implicit Grant',
        EDIT_GRANT_R: 'Resource Owner Credentials',
        EDIT_GRANT_R_REFRESH: 'Enable Refresh-Token support for Resource Owner Credentials',
        EDIT_BIND_USER: 'User Binding (Authorization Code, Implicit Grant and Resource Owner only)',
        EDIT_STATUS: 'Status',
        EDIT_ALLOWED_USERS_TEXT: 'Specify a comma separated list of allowed ILIAS User Ids (else every ILIAS user can authorize)',
        EDIT_ALLOWED_USERS: 'Allowed ILIAS users',
        EDIT_REDIRECT_MSG: 'Redirection URI and Consent Message (Authorization Code and Implicit Grant only)',
        EDIT_REDIRECT_URI: 'Redirection URI',
        EDIT_CONSENT_MSG: 'OAuth2 Consent Message',
        EDIT_CONSENT_SCOPE: 'Enable an additional page for the OAuth2 grant types "authcode" and "implicit grant" to inform the user about the scope of the application.',

        CHECKOUT_INPUT: 'Please enter a route:',
        CHECKOUT_OPEN_NEW_WINDOW: 'Send the request to a new window. This option can be necessary for routes involving downloads or redirects.'
    });
    
    // Ste default language
    $translateProvider.preferredLanguage('en-US');
});