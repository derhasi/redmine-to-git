
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
  this.parent = undefined;
}

redmineWikiPage.load = function(redmine, project, title, version, callback) {
  var path = 'projects/' + project + '/wiki/' + title + '/' + version;

  redmine.getRaw(path, {}, function(err, data) {

    if (err) {
      callback(err);
      return;
    }
    else if (data["wiki_page"] == undefined) {
      var err = new Error('No wiki pages available for project:' +  project);
      callback(err);
      return;
    }

    // Build object an initialize.
    var rWikiPage = new redmineWikiPage(redmine);
    rWikiPage.init(project, title, version, data["wiki_page"]);
    callback(undefined, rWikiPage);
  });
}


/**
 * Initialize object with default data.
 *
 * @param data
 */
redmineWikiPage.prototype.init = function(project, title, version, page) {
  this.project = project;
  this.title = title;
  this.version = version;
  this.text = page.text;
  this.comments = page.comments;
  this.created = new Date(page.created_on);
  // Get the update data.
  if (page.updated_on == undefined) {
    this.updated = this.created;
  }
  else {
    this.updated = new Date(page.updated_on);
  }
  // Get the parent.
  if (page.parent != undefined) {
    this.parent = page.parent;
  }
  // Get original data for reference.
  // @todo: remove to avoid duplicate data. Check on title changes and co.
  this.data = page;
}

module.exports = redmineWikiPage;

