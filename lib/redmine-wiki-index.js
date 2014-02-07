
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

redmineWikiIndex.prototype.getAllPageVersions = function(project, callback) {




}
