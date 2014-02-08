/**
 * Builds a redmine user handler.
 *
 * @param {Redmine} redmine
 * @constructor
 */
var redmineUser = function(redmine) {
  this.redmine = redmine;
  this.id = undefined;
  this.username = undefined;
  this.firstname = undefined;
  this.lastname = undefined;
  this.mail = undefined;
  this.created = undefined;
  this.last_login = undefined;
  this.status = undefined;
}

/**
 * Static function to load user from redmine.
 *
 * @param {Redmine} redmine
 * @param {Int} id
 * @param {Function} callback
 */
redmineUser.load = function(redmine, id, callback) {
  redmine.getSingle('user', id, {}, function(err, issue) {
    // Build object an initialize.
    var rUser = new redmineUser(redmine);
    rUser.init(issue);
    callback(undefined, rUser);
  });
}

/**
 * Initialize object with default data.
 *
 * @param data
 */
redmineUser.prototype.init = function(data) {
  this.id = data.id;
  this.status = data.status;
  this.username = data.login;
  this.firstname = data.firstname;
  this.lastname = data.lastname;
  this.mail = data.mail;
  this.created = new Date(data.created_on);
  if (data.last_login_on != undefined) {
    this.last_login = new Date(data.last_login_on);
  }
}


module.exports = redmineUser;
