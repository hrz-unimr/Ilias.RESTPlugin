'use strict';

/* Controllers */

var app = angular.module('mobileApp.controllers', []);

app.controller("defaultCtrl", function(baseUrl, $rootScope, $scope, $window, $resource, $location, authentication) {
    $scope.logindata = {};
    $scope.pageClass = 'page-main';
    $scope.checked = false;

    $rootScope.swipeType ="<None>";
    $scope.handleSwipe = function(direction) {
        if ($scope.pageClass=='page-main') {
            console.log("Swipe from left to right in page-main");
            $rootScope.swipeType = direction;
            $scope.viewInfo();
            /*console.log("Loading info ...");
            $scope.pageClass = 'info';
            $location.path("/info");*/
        } else {
            console.log("Swipe event detected ...");
            $rootScope.swipeType = direction;
        }
    }

    $scope.pageChanged = function(newPage) {
        $scope.currentPage = newPage;
        //getResultsPage(newPage);
    };


    $scope.isAuthenticated = function() {
        //return true;
        return authentication.isAuthenticated;
    }

    $scope.getUsername = function() {
        return authentication.user;
    }

    $scope.logout = function() {
        authentication.isAuthenticated = false;
        $location.url("/login");
    }

    $scope.getAccessToken = function() {
        return authentication.access_token;
    }

    $scope.getOverview = function() {
        //$location.url("/overview");
        console.log("Loading overview");
        $rootScope.pageClass = 'page-main';
        $scope.pageClass = 'page-main';
        $location.path("/overview");
    }

    $scope.getMyCoursesView = function() {
        //$location.url("/overview");
        console.log("Loading my courses view");
        $rootScope.pageClass = 'page-mycourses';
        $scope.pageClass = 'page-mycourses';
        $location.path("/mycourses");
    }

    $scope.viewCalendar = function() {
        console.log("Loading calendar ... ");
        $scope.pageClass = 'calendar';
        $location.path("/calendar");
    }

    $scope.viewInfo = function() {
        console.log("Loading info ...");
        $scope.pageClass = 'info';
        $location.path("/info");
    }

    $scope.viewContacts = function() {
        console.log("Loading contacts ...");
        $scope.pageClass = 'contacts';
        $location.path("/contacts");
    }

    $scope.viewFile = function(id) {
        console.log("Viewing file ...");

        var url = baseUrl + "/restplugin.php/v1/files/" + id;
        //var url = "http://137.248.3.49:8888/ilias5beta/restplugin.php/v1/files/"+id;
        $window.open(url,'_self');
    }

    /**
     * This function calls different views depending on the item type.
     * E.g. container items will be "opened" and the contents will be displayed.
     * @param id
     */
    $scope.viewItem = function(id) {
        console.log("Open Item with id = " + id);
        var v = $rootScope.repositoryItems[id];
        console.log("Item type : "+v.type);
        if (v.type == 'crs' || v.type == 'grp') {
            $scope.viewContainer(id);
        } else
        if (v.type == 'file') {
            $scope.viewFile(id);
        }
    }

    $scope.viewContainer = function(id) {
        console.log("Viewing container with id = " + id);
        $rootScope.currentContainerId = id;
        $location.path("/containercontent");
    }


});

app.controller('AuthCtrl', function($scope, $filter, $http, AuthFactory) {
    $scope.data = {};

    AuthFactory.authorize(function(data) {
        console.log('Query auth factory');
        return AuthFactory.auth();
    });

});


app.controller('NavBarCtrl', function ($scope) {
    $scope.isCollapsed = true;
});

app.controller('LoginCtrl', function($scope, authentication, $location, AuthFactory) {
    $scope.pageClass = 'page-login';
    $scope.loginHasFailed = false;

    $scope.loginFailed = function () {
        return $scope.loginHasFailed;
    }

    $scope.loginUC = function () {

        var v_user_name = $scope.logindata.user_name;
        var v_password = $scope.logindata.password;


        /*AuthFactory.auth({grant_type: 'password', username: v_user_name, password: v_password }, function (data) {
            console.log('Auth Callback : ',data);
            if (data.token_type == "bearer") {
                $scope.token = data.access_token;
                $scope.access_token = $scope.token;//.access_token
                console.log($scope.token.access_token);
                authentication.isAuthenticated = true;
                authentication.access_token = $scope.access_token;
                authentication.user = $scope.logindata.user_name;
                $location.url("/overview");
                $scope.loadWorkspaces();
            } else {
                authentication.isAuthenticated = false;

                //$location.url("/login");
            }
        }, function (errorResult){
               console.log('Auth failed recieved an error from server ',errorResult);
               if (errorResult.status = 401) {
                    $scope.loginHasFailed = true;
               }
        });
        */
        // Mockup
        authentication.isAuthenticated = true;
        authentication.access_token = $scope.access_token;
        authentication.user = "root";
        $location.url("/overview");

        console.log("Requesting mdesk data...");
        restMDesk.getResource().query({}, function(response){
            console.log("Mdesk REST response ",response);
            $rootScope.mdeskData = response;
            $rootScope.repositoryItems = response.ritems;
            $rootScope.personalDesktop = response.mypersonaldesktop;
            console.log("Mdesk Personal Desktop ",$rootScope.personalDesktop);
            console.log("MDsk PD 2 ", response.mypersonaldesktop);
            $rootScope.myCourses = response.mycourses;
            $rootScope.myGroups = response.mygroups;

            $rootScope.events = response.calendar.events;
            $rootScope.icalurl = response.calendar.ical_url;
            $rootScope.user = response.user;
            $rootScope.contacts = response.contacts.my_contacts;
            $location.path("/overview");
        });

    };
});



