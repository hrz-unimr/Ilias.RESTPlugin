// Use ECMAScript 5 restricted variant of Javascript
'use strict';

/*
 * All filters will be stored in this module.
 */
var filters = angular.module('myApp.filters', []);


/*
 * Replace VERSION string with given version number.
 */
filters.filter('interpolate', function(version) {
    return function(text) {
        return String(text).replace(/\%VERSION\%/mg, version);
    };
});


/*
 * Replace INFO variable with additional formated warning information.
 */
filters.filter('restInfo',function($sce) {
    return function(text, status, data) {
        var statusClean = status;
        var dataClean = (typeof data == "string") ? dataClean.replace(/"/g, '\\&quot;').replace(/'/g, '\\&#39;') : data;
        dataClean = $sce.trustAsHtml(dataClean);

        return String(text).replace(/\%INFO\%/mg, '<span class="restInfo">(Status: <u><span href="#" tooltip-html-unsafe="'+dataClean+'" tooltip-placement="left">'+statusClean+'</span></u>)</span>');
    };
});


/*
 * Used to format (prettify) client permission (clientlist.html)
 * by adding predefined css-classes for each permission.
 */
filters.filter('formatListPermissions', function($sce) {
    return function(value) {
        if (typeof value != 'undefined') {
            var jsonValue = angular.fromJson(value);

            var resultHtml = '<table>';
            for (var i = 0; i < jsonValue.length; i++) {
                resultHtml += '<tr><td style="width: 5em">';

                switch (jsonValue[i].verb) {
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

                resultHtml += '</td><td><span class="label label-permission">' + jsonValue[i].pattern + '</span></td></tr>';
            }
            resultHtml += '</table>';

            return $sce.trustAsHtml(resultHtml);
        }

        return "";
    };
});
filters.filter('formatIpRestriction', function($sce) {
    return function(value) {
           // console.log(value);
            var resultHtml = '<div class="text-center">';
            if (typeof value == 'undefined') {
                resultHtml += '<span class="fa fa-unlock black"></span>';
            } else
            if (value != "") {
                resultHtml += '<span class="fa fa-lock black"></span>';
            }

            resultHtml += '</div>';

            return $sce.trustAsHtml(resultHtml);
    };
});

filters.filter('formatEditPermission', function($sce) {
    return function(value) {
        if (typeof value != 'undefined') {
            var jsonValue = angular.fromJson(value);

            var resultHtml;
            switch (jsonValue.verb) {
            case "GET":
                resultHtml = '<span class="label label-primary">GET</span>';
                break;
            case "POST":
                resultHtml = '<span class="label label-success">POST</span>';
                break;
            case "PUT":
                resultHtml = '<span class="label label-warning">UPDATE</span>';
                break;
            case "DELETE":
                resultHtml = '<span class="label label-danger">DELETE</span>';
                break;
            }

            resultHtml += '<span class="label label-permission">' + jsonValue.pattern + '</span>';


            return $sce.trustAsHtml(resultHtml);
        }

        return "";
    };
});


/*
 * Convert a (html) string to a (possibly) unsafe but trusted string
 * such that it can be used in ng-bind-html (or else).
 */
filters.filter('toTrusted', function($sce) {
    return function(text) {
        return $sce.trustAsHtml(text);
    };
});
