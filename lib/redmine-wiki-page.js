
/**
 * Object for single page.
 *
 * @param redmine
 * @constructor
 */
var redmineWikiPage = function(redmine) {
  this.redmine = redmine;
  this.project = undefined;
  this.title = undefined;
  this.text = undefined;
  this.version = undefined;
  this.author = undefined;
  this.comments = '';
  this.created = undefined;
  this.updated = undefined;
}

redmineWikiPage.load = function(redmine, project, title, version, callback) {
  var path = 'projects/' + project + '/wiki/' + title + '/' + version;

  redmine.getRaw(path, function(err, data) {

    // Build object an initialize.
    var rWikiPage = new redmineWikiPage(redmine);
    rWikiPage.init(project, title, version, data);
    callback(undefined, rWikiPage);
  });
}


/**
 * Initialize object with default data.
 *
 * @param data
 */
redmineWikiPage.prototype.init = function(project, title, version, data) {
  this.index = index;
  this.project = project;
  this.title = title;
  this.version = version;
  this.data = data;
}
