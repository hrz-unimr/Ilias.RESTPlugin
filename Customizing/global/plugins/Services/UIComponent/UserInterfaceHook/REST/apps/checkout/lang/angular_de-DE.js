// Use ECMAScript 5 restricted variant of Javascript
'use strict';


/*
 * This variable will manage all en-US translations
 */
var lang_de_de = angular.module('myApp.translate.de-DE', []);
 

/*
 * Load en-US translations
 */
lang_de_de.config(function($translateProvider) {
    // Supply translations
    $translateProvider.translations('de-DE', {
    });
});