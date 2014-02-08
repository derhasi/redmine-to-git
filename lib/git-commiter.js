
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
        callback(err);
        return;
      }
      console.log('Add', output);

      var commitOptions = {
        m: message
      }

      if (author != undefined) {

        author = author.replace('"', '');

        commitOptions.author = '"' + author + '"';
      }
      if (date !=  undefined) {
        commitOptions.date = date.toISOString()
      }

      git.exec("commit", commitOptions, [], function(err, output) {
        if (err) {
          callback(err);
          return;
        }

        console.log('Commit', output);

        callback();
      });
    });
  });
};

module.exports = gitCommiter;
