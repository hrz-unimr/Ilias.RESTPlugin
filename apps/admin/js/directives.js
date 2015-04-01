// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * All directives will be stored in this module.
 */
var directives = angular.module('myApp.directives', []);


/*
 * Set version number on HTML-Element using data-<xxx>-version as tag.
 * When using eg <span data-app-version></span> make sure to use all 
 * lower-case letters, as '-' will convert the following letter into 
 * upper-case in order to conform to the camelCase notation (JS),
 * while the DOM is case-less.
 */
directives.directive('appVersion', [
    'version', 
    function(version) {
        return function setVersion(scope, element, attrs) {
            element.text(version);
        }
    }
]);
directives.directive('angularjsVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v"+angular.version.full;
        }
    }
]);
directives.directive('jqueryVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v"+jQuery.fn.jquery;
        }
    }
]);
directives.directive('modernizrVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v"+Modernizr._version;
        }
    }
]);
directives.directive('lessVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v"+less.version[0]+"."+less.version[1]+"."+less.version[2];
        }
    }
]);
directives.directive('bootstrapVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v3.3.4";
        }
    }
]);
directives.directive('normalizeVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v3.0.2";
        }
    }
]);
directives.directive('boilerplateVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v5.0.0";
        }
    }
]);
directives.directive('animatecssVersion', [
    function() {
        return function setVersion(scope, element, attrs) {
            attrs.tooltip = "v3.2.5";
        }
    }
]);