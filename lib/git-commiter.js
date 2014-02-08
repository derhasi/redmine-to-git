
var fs = require('fs');
var gitWrapper = require('git-wrapper');


var gitCommiter = function() {

  var git = new gitWrapper();
  this.git = git;
  this.directory = process.cwd();
}

gitCommiter.prototype.commitFile = function(fileName, content, message, author, date, callback) {

  var git = this.git;

  fs.writeFile(this.directory + '/' + fileName, content, function(err) {
    if (err) {
      callback(err);
      return;
    }

    git.exec("add", {}, [fileName], function(err, output) {
      if (err) {
        callback(err, output);
        return;
      }
      console.log('Add', output);

      var commitOptions = {
        m: gitCommiterPrepareCLIString(message)
      }

      if (author != undefined) {
        commitOptions.author = gitCommiterPrepareCLIString(author);
      }

      if (date !=  undefined) {
        commitOptions.date = date.toISOString()
      }

      git.exec("commit", commitOptions, [], function(err, output) {
        if (err) {
          callback(err, output);
          return;
        }

        console.log('Commit', output);

        callback();
      });
    });
  });
};

/**
 * Helper to replace double quotes and encapsulate string for usage in CLI with
 * double quotes.
 *
 * @param input
 * @returns {string}
 */
function gitCommiterPrepareCLIString(input) {
  return '"' + input.replace(/\"/g, "'") + '"';
}

module.exports = gitCommiter;
