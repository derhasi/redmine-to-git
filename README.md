# Redmine to git

This tool uses the [Redmine REST API](http://www.redmine.org/projects/redmine/wiki/Rest_api)
for fetching data from a specific project and push that to a git repository.

Currently only wiki pages are supported.

## Installation

The installation is simple by using [composer](https://getcomposer.org/). After [installing composer](https://getcomposer.org/doc/00-intro.md) you can either install the command globally or within a project.

### Global 

In the global installation `redmine-to-git` will be available as a command line tool.

* Run `composer global require derhasi/redmine-to-git` to install globally.
* Add `export PATH=~/.composer/vendor/bin:$PATH` to your `.bashrc`or `.profile`

After the installation you should be able to run `redmine-to-git wiki ...` from anywhere.

### Local

You can run `composer require derhasi/redmine-to-git` in any composer enabled project to add this project as a dependency.

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
