'use strict';

/* Filters */
var filters = angular.module('mobileApp.filters', []);

filters.filter('format_description', function() {
    return function (value) {
        if (value==null) {
            return '\u00A0\u00A0';
        } else {
            return value;
        }
    }
});

filters.filter('show_type_icon', function($sce) {
    return function (value) {
        switch (value) {
            case "file":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-file">');
            case "sess":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-time">');
            case "grp":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-screenshot">');
            default:
                return $sce.trustAsHtml(value);
        }
        /*if (value=='file') {
            return $sce.trustAsHtml('<span class="glyphicon glyphicon-file">');
        } else {
            return $sce.trustAsHtml(value);
        }*/
    }
});



filters.filter('display_type_icon_for_id', function($sce, $rootScope) {
    return function (value) {
        var v = $rootScope.repositoryItems[value];
        switch (v.type) {
            case "file":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-file">');
            case "sess":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-time">');
            case "grp":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-screenshot">');
            case "crs":
                return $sce.trustAsHtml('<span class="glyphicon glyphicon-bookmark">');

            default:
                return $sce.trustAsHtml(v.type);
        }
        /*if (value=='file') {
         return $sce.trustAsHtml('<span class="glyphicon glyphicon-file">');
         } else {
         return $sce.trustAsHtml(value);
         }*/
    }
});

filters.filter('secondsToHuman', function() {
   return function (value) {
       if (value==null) {
           return '\u00A0\u00A0';
       } else {
           // Convert seconds to human readable time string
           var min = value/60;
           if (min < 60) {
               return min + " Minuten";
           } else {
               var std = min/60;
               return std + " Stunden";
           }
           return value + " Sekunden";
       }
   }
});

filters.filter('getRepositoryItemTitle', function($rootScope) {
    return function (value) {
        var v = $rootScope.repositoryItems[value];
        return v.title;
    }
});

filters.filter('getRepositoryItemDescription', function($rootScope) {
    return function (value) {
        var v = $rootScope.repositoryItems[value];
        if (v.description==null) {
            return '\u00A0\u00A0';
        } else {
            return v.description;
        }
    }
});

filters.filter('getRepositoryItemNumChildren', function($rootScope) {
    return function (value) {
        var v = $rootScope.repositoryItems[value];
        if (v.type=='crs' || v.type=='cat') {
            return v.children_ref_ids.length;
        } else {
            return "";
        }
    }
});

