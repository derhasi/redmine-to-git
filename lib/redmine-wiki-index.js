
var redmineWikiPage = require('./redmine-wiki-page');

/**
 * Builds a redmine issue handler.
 *
 * @param {Redmine} redmine
 * @constructor
 */
var redmineWikiIndex = function(redmine) {
  this.redmine = redmine;
  this.index = undefined;

  this.project = undefined;

  this.versionQueue = [];
  this.pageVersions = [];
}

/**
 * Static function to load issue from redmine.
 *
 * @param {Redmine} redmine
 * @param {Int} id
 * @param {Function} callback
 */
redmineWikiIndex.load = function(redmine, project, callback) {
  var path = 'projects/' + project + '/wiki/index';
  redmine.getRaw(path, {}, function(err, data) {

    if (err) {
      callback(err);
      return;
    }
    else if (data["wiki_pages"] == undefined) {
      var err = new Error('No wiki pages available for project:' +  project);
      callback(err);
      return;
    }

    // Build object an initialize.
    var rWikiIndex = new redmineWikiIndex(redmine);
    rWikiIndex.init(project, data["wiki_pages"]);
    callback(undefined, rWikiIndex);
  });
}

/**
 * Initialize object with default data.
 *
 * @param data
 */
redmineWikiIndex.prototype.init = function(project, index) {
  this.project = project;
  this.index = index;
}

redmineWikiIndex.prototype.getAllPageVersions = function(callback) {
  var obj = this;

  // Reset versions and queue.
  this.pageVersions = [];
  this.versionQueue = [];

  for (var p in obj.index) {

    var item = obj.index[p];
    var current_version = item.version;

    while (current_version > 0) {

      // We push all versions in a queue and start loading it later.
      obj.versionQueue.push({title: item.title, version: current_version});
      current_version--;
    }
  }

  // Start emptying the queue and grabbing real page data.
  obj.getPageVersionsFromQueue(callback);
}

redmineWikiIndex.prototype.getPageVersionsFromQueue = function(callback) {

  var obj = this;

  // In the case we have got no queue item anymore, we can pass the total
  // result pack to the callback.
  if (this.versionQueue.length == 0) {
    callback(undefined, this.pageVersions);
    return;
  }

  var item = obj.versionQueue.shift();

  redmineWikiPage.load(obj.redmine, obj.project, item.title, item.version, function(err, wikiPage) {

    if (err) {
      console.log('Error wiki page', err);
      return;
    }

    if (item.title !=  wikiPage.data.title) {
      console.log('Title differs', item.title, wikiPage.data.title);
    }

    obj.pageVersions.push(wikiPage);

    obj.getPageVersionsFromQueue(callback);
  });

}

module.exports = redmineWikiIndex;

