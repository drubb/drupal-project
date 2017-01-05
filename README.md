# Composer template for Drupal projects

[![Build Status](https://travis-ci.org/drubb/drupal-project.svg?branch=8.x)](https://travis-ci.org/drubb/drupal-project)

This project template should provide a kickstart for managing your site
dependencies with [Composer](https://getcomposer.org/). Based on the excellent [Drupal Composer Project](https://github.com/drupal-composer/drupal-project)
it introduces some modifications:

- Public and private files are placed outside the web root
- The config sync folder is placed outside the webroot
- During installation, a local settings file is used to point to modified files and config location
- Creates "contrib" and "custom" folders for modules, themes, libraries, profiles and drush plugins by default
- Adds the [Composer Parallel Install](https://github.com/hirak/prestissimo) plugin to speed up downloads
- Adds the [Composer Merge](https://github.com/wikimedia/composer-merge-plugin) plugin to manage dependencies of custom modules

## Current status

This project template is still work in progress, but usable. It needs some work regarding file
and directory access rights, and some more testing love. Feel free to file [issues](https://github.com/drubb/drupal-project/issues)

## Usage

First you need to [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

After that you can create the project:

```
composer create-project drubb/drupal-project:8.x-dev some-dir --stability dev --no-interaction
```

With `composer require ...` you can download new dependencies to your 
installation.

```
cd some-dir
composer require drupal/devel:~1.0
```

## What does the template do?

When installing the given `composer.json` some tasks are taken care of:

* Drupal will be installed in the `web`-directory.
* Autoloader is implemented to use the generated composer autoloader in `vendor/autoload.php`,
  instead of the one provided by Drupal (`web/vendor/autoload.php`).
* Modules (packages of type `drupal-module`) will be placed in `web/modules/contrib/`
* Themes (packages of type `drupal-theme`) will be placed in `web/themes/contrib/`
* Profiles (packages of type `drupal-profile`) will be placed in `web/profiles/contrib/`
* Libraries (packages of type `drupal-library`) will be placed in `web/libraries/contrib/`
* Drush plugins (packages of type `drupal-drush`) will be placed in `drush/contrib`
* Creates default writable versions of `settings.php`, `settings.local.php` and `services.yml`.
* Creates `sites/default/files`-directory, using a symlink to `files/public`
* Latest version of Drush is installed locally for use at `vendor/bin/drush`.
* Latest version of DrupalConsole is installed locally for use at `vendor/bin/drupal`.

## Updating Drupal Core

This project will attempt to keep all of your Drupal Core files up-to-date; the 
project [drupal-composer/drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold) 
is used to ensure that your scaffold files are updated every time drupal/core is 
updated. If you customize any of the "scaffolding" files (eg .htaccess or robots.txt), 
you may need to merge conflicts if any of your modified files are updated in a 
new release of Drupal core.

Follow the steps below to update your core files.

1. Run `composer update drupal/core --with-dependencies` to update Drupal Core and its dependencies.
2. Run `git diff` to determine if any of the scaffolding files have changed. 
   Review the files for any changes and restore any customizations to 
  `.htaccess` or `robots.txt`.
3. Commit everything all together in a single commit, so `web` will remain in
   sync with the `core` when checking out branches or running `git bisect`.
4. In the event that there are non-trivial conflicts in step 2, you may wish 
   to perform these steps on a branch, and use `git merge` to combine the 
   updated core files with your customized files. This facilitates the use 
   of a [three-way merge tool such as kdiff3](http://www.gitshah.com/2010/12/how-to-setup-kdiff-as-diff-tool-for-git.html). This setup is not necessary if your changes are simple; 
   keeping all of your modifications at the beginning or end of the file is a 
   good strategy to keep merges easy.

### How can I apply patches to downloaded modules?

If you need to apply patches (depending on the project being modified, a pull 
request is often a better solution), you can do so with the 
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module foobar insert the patches section in the extra 
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "URL to patch"
        }
    }
}
```
