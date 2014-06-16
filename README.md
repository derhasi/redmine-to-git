# Redmine to git

This tool uses the [Redmine REST API](http://www.redmine.org/projects/redmine/wiki/Rest_api)
for fetching data from a specific project and push that to a git repository.

## Installation

* Install [composer](https://getcomposer.org/)
* Run `composer install` in this directory

## Usage

Currently the command is ...

`php script.php --redmine=https://yourredmineurl.com --apikey=YOURSECRETAPIKEY--project=PROJECTNAME`

* **redmine**: URL of your Redmine installation
* **apikey**: API Key for accessing the redmine API
* **project**: Machine  name of the Project to grab the wiki from

## @todo

* Reimplement with PHP
* Document code!!
* Command line options for data in config.json
* Creation and/or clone of a git repo
* Write data to a sub directory (defaults to project)
