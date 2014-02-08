# Redmine to git

This tool uses the [Redmine REST API](http://www.redmine.org/projects/redmine/wiki/Rest_api)
for fetching data from a specific project and push that to a git repository.

## Installation

* First install all npm packages: `npm install`
* Create config.json
  (e.g. by copying config.json.default: `cp config.json.default config.json`)
* Update config.json with specific data.
* Make sure the `path` specified is a valid git repository.

## Usage

Currently the command is simply executed with `node index.js`

## @todo

* Document code!!
* Command line options for data in config.json
* Creation and/or clone of a git repo
* Write data to a sub directory (defaults to project)
