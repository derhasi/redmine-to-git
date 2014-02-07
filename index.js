var redmine = require('./lib/redmine');
var redmineWikiIndex = require('./lib/redmine-wiki-index');
var config = require('./config.json');
var momentRange = require('moment-range');

var r = new redmine(config.host, config.port, config.key);

// Hardcoded project id.
var projectId = 329;

var fromDate = new Date('2014-01-01');
var toDate = Date.now();

var dateRange = momentRange().range(fromDate, toDate);
var dateRangeArray = [];

// Iterate over date range.
dateRange.by('days', function(moment) {
  dateRangeArray.push(moment);
});

redmineIssue.getAll(r, {project_id: projectId}, function(err, issues) {
  if (err) {
    console.log('ERROR', err);
  }
  console.log('Collected issues:', issues.length);


  // Prerun status, so it is present later on.
  r.getStatuses(function(err, statuses) {

    console.log('Statuses', statuses);

    var series = {};

    // We run through each
    for (var i in issues) {

      var issue = issues[i];

      for (var day in dateRangeArray) {
        var thisDate = dateRangeArray[day];

        var status = issue.getStatus(thisDate);

        // Only proceed if the issue has a valid status for that day.
        if (status != undefined && statuses[status] != undefined) {

          // Ensure status array is defined.
          if (series[status] == undefined) {
            series[status] = {};
          }
          // Ensure status for the day is present.
          if (series[status][day] == undefined) {
            series[status][day] = 0;
          }

          // Increment the status for that day for the given issue.
          series[status][day]++;
        }
      }
    }

    console.log('Series', series);
  });
});
