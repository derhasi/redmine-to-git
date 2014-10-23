# Redmine to git

This tool uses the [Redmine REST API](http://www.redmine.org/projects/redmine/wiki/Rest_api)
for fetching data from a specific project and push that to a git repository.

## Installation

* Install [composer](https://getcomposer.org/)
* Run `composer install` in this directory

## Usage

Currently the command is ...

`php redmine-to-git.php wiki redmine apikey project repo --subdir=subdir --maxFilesize=1234` 

* **redmine**: URL of your Redmine installation
* **apikey**: API Key for accessing the redmine API
* **project**: Machine  name of the Project to grab the wiki from
* **repo**: path to git working directory
* **subdir**: optional subdirectory to put files and index to
* **maxFilesize**: optional maximum filesize for attachments to download

Type `php redmine-to-git.php help wiki` for additional information.

## Help

The project is hosted on [github](https://github.com/derhasi/redmine-to-git), so
please file any issues or questions in the
[Issue Queue](https://github.com/derhasi/redmine-to-git/issues).
