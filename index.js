var redmine = require('./lib/redmine');
var redmineWikiIndex = require('./lib/redmine-wiki-index');
var config = require('./config.json');
var momentRange = require('moment-range');

var r = new redmine(config.host, config.port, config.key);

var rWI = redmineWikiIndex.load(r, config.project, function(err, index) {
  console.log('index:', index);

  index.getAllPageVersions(function(err, pages) {
    console.log('Pages:', pages);
  });

});
