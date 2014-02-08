var redmine = require('./lib/redmine');
var redmineWikiIndex = require('./lib/redmine-wiki-index');
var redmineUser = require('./lib/redmine-user');
var gitCommiter = require('./lib/git-commiter');

var config = require('./config.json');

// We change the working directory to the repo path.
process.chdir(config.path);

var gitC = new gitCommiter();

/**
 *
 * @type {redmineWikiPage[]}
 */
var allPages = [];
var authors = {};

var r = new redmine(config.host, config.port, config.key);

var rWI = redmineWikiIndex.load(r, config.project, function(err, index) {
  console.log('Index grabbed:', index.length);

  index.getAllPageVersions(function(err, pages) {
    //console.log('Pages:', pages);

    pages.sort(function(a,b) {
      if (a.updated == b.updated) {
        return (a.title > b.title) ? 1 : -1;
      }

      return (a.updated > b.updated) ? 1 : -1;
    });

    console.log('Pages grabbed!', pages.length);

    allPages = pages;

    commitAllPages(function(err, output) {
      console.log('Finish', err, output);
    })

  });
});

/**
 * Helper to run through the all pages queue, and commit each file.
 *
 * @param callback
 * @returns {*}
 */
function commitAllPages(callback) {

  // Do nothing if we got no pages.
  if (allPages.length == 0) {
    return callback();
  }

  var page = allPages.shift();

  var message = '';
  if (page.version == 1) {
    message = 'Created "' + page.title + '" on ' + page.updated.toISOString();
  }
  else {
    message = 'Updated "' + page.title + '" (version ' + page.version + ') on ' + page.updated.toISOString();
  }

  /**
   * Helper to load author information only once.
   *
   * @param {Int} id
   *   User id of the author.
   * @param {Function} callback
   *   Callback holding
   *   - err: Error oder undefined
   *   - user: redmineUser object
   */
  function getAuthor(id, callback) {
    if (authors[id] == undefined) {
      redmineUser.load(r, page.author, function(err, user) {
        authors[id] = user;
        callback(undefined, user);
      });
    }
    else {
      callback(undefined, authors[id]);
    }
  }

  // Commit page, after we got our
  getAuthor(page.author, function(err, user) {
    var commit_author = user.firstname + ' ' + user.lastname + ' <' + user.mail + '>';
    console.log("Commit Author:", commit_author);
    gitC.commitFile(page.title + '.textile', page.text, message, commit_author, page.updated, function(err) {
      if (err) {
        console.log('Error', err);
        callback(err);
        return;
      }
      commitAllPages(callback);
    });
  });

}
