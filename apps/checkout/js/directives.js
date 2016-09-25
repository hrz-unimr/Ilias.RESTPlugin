// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * All directives will be stored in this module.
 */
var directives = angular.module('myApp.directives', []);




/*
 * Alternative to ngBindHtml when the attribute-value (html-code)
 * also contains AngularJS directives. (eg. data-tooltip)
 * Those need to be compiled, since otherwise directives
 * won't work.
 */
directives.directive('ngBindHtmlCompile', function($compile) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            scope.$watch(attrs.ngBindHtmlCompile, function(newValue, oldValue) {
                element.html(newValue);
                $compile(element.contents())(scope);
            });
        }
    }
});
