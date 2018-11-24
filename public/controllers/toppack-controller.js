function toppackController($scope, ToppackFactory) {
  'use strict';

  $scope.searchTerm = "";
  $scope.searchError = "";

  $scope.searchRepo = function(searchTerm) {
    var successCallback = function(data) {
      debugger;
      $scope.repositories = data;
    }
    var errorCallback = function(error) {
      $scope.searchError = "Couldnt find any repositories"
    }
    ToppackFactory.getRepositories(searchTerm, successCallback, errorCallback);
  };

  $scope.importRepository = function(repository, index) {
    var successCallback = function(response) {
      $scope.repositories[index].imported = true;
      $scope.repositories[index].importError = false;
      alert("Import Successful ", JSON.stringify(response));
    }

    var errorCallback = function(error) {
      $scope.repositories[index].importError = true;
      alert("No Package.Json file found");
    }
    debugger;
    ToppackFactory.importRepository(repository, successCallback, errorCallback);
  }
}

angular.module("toppack").controller("ToppackController", toppackController);
