/*
 View Directive
*/
var cardViewDirective = function () {
  'use strict';
  return {
    templateUrl: "card-view.html"
  }
}

angular.module("toppack").directive("cardView", cardViewDirective);