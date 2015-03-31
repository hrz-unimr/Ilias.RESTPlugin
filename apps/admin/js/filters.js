// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * All filters will be stored in this module.
 */
var filters = angular.module('myApp.filters', []);


/*
 * Replace VERSION string with given version number.
 */
filters.filter('interpolate', [ 
    'version', 
    function(version) {
        return function(text) {
            return String(text).replace(/\%VERSION\%/mg, version);
        };
    } 
]);


/*
 * Used to format (prettify) client permission (clientlist.html)
 * by adding predefined css-classes for each permission.
 */
filters.filter('format_permissions', function($sce) {
    return function(value) {
        var v = angular.fromJson(value);
        var resultHtml = '<table>';
        
        for (var i = 0; i < v.length; i++) {
            resultHtml += '<tr><td style="width: 5em">';
            
            switch (v[i].verb) {
            case "GET":
                resultHtml += '<span class="label label-primary">GET</span>';
                break;
            case "POST":
                resultHtml += '<span class="label label-success">POST</span>';
                break;
            case "PUT":
                resultHtml += '<span class="label label-warning">UPDATE</span>';
                break;
            case "DELETE":
                resultHtml += '<span class="label label-danger">DELETE</span>';
                break;
            }
            
            resultHtml += '</td><td><span class="label label-permission">' + v[i].pattern + '</span></td></tr>';
        }
        
        resultHtml += '</table>';
        return $sce.trustAsHtml(resultHtml);
    };
});
