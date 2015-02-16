'use strict';

/* Filters */
var filters = angular.module('myApp.filters', []);

filters.filter('interpolate', ['version', function(version) {
  return function(text) {
    return String(text).replace(/\%VERSION\%/mg, version);
  };
}]);

filters.filter('xx', function() {
  return function (value) {
    if (value==null) {
      return '\u00A0\u00A0';
    } else {
      return value;
    }
  }
});

filters.filter('format_permissions', function($sce) {
  return function (value) {
    var v = angular.fromJson(value);
    var resultHtml = "";
    for (var i=0;i< v.length;i++) {
      //resultHtml+= '<div>';
      resultHtml+= '<span class="label label-permission">'+ v[i].pattern + '</span>';
      //resultHtml+= v[i].pattern;
      switch (v[i].verb) {
        case "GET":
              resultHtml+= '<span class="label label-primary">GET</span>';
              break;
        case "POST":
              resultHtml+= '<span class="label label-success">POST</span>';
              break;
        case "PUT":
              resultHtml+= '<span class="label label-warning">UPDATE</span>';
              break;
        case "DELETE":
              resultHtml+= '<span class="label label-danger">DELETE</span>';
              break;
      }
      if (i< v.length) {
        resultHtml+= ' ';
      }
      //resultHtml+= '</div>';
    }
    return  $sce.trustAsHtml(resultHtml);
    //return $sce.trustAsHtml('<span class="label label-danger">DELETE</span>');
    //return resultHtml;
    //return v[0].pattern;//$sce.trustAsHtml()
   /* switch (v.type) {
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
   */
  }
});

