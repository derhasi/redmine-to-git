var redmine = require('./lib/redmine');
var redmineWikiIndex = require('./lib/redmine-wiki-index');
var config = require('./config.json');
var momentRange = require('moment-range');

var r = new redmine(config.host, config.port, config.key);

var rWI = redmineWikiIndex.load(r, config.project, function(err, index) {
  console.log('index:', index);

  index.getAllPageVersions(function(err, pages) {
    //console.log('Pages:', pages);

    pages.sort(function(a,b) {
      if (a.updated == b.updated) {
        return (a.title > b.title) ? 1 : -1;
      }

      return (a.updated > b.updated) ? 1 : -1;
    });

    console.log('Pages:', pages);


  });

});
