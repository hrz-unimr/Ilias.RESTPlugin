'use strict';

/* Filters */
var filters = angular.module('myApp.filters', []);

filters.filter('interpolate', [ 'version', function(version) {
	return function(text) {
		return String(text).replace(/\%VERSION\%/mg, version);
	};
} ]);

filters.filter('xx', function() {
	return function(value) {
		if (value == null) {
			return '\u00A0\u00A0';
		} else {
			return value;
		}
	}
});

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
	}
});
