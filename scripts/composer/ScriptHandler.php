<?php

/**
 * @file
 * Contains \DrupalProject\composer\ScriptHandler.
 */

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler {

  /**
   * Determine the path to the web root, below the project root
   *
   * @param $project_root
   * @return string
   */
  protected static function getDrupalRoot($project_root) {
    return $project_root . '/web';
  }

  /**
   * Creates directories, links and files during project creation
   * or updates
   *
   * @param \Composer\Script\Event $event
   */
  public static function createRequiredFiles(Event $event) {

    $fs = new Filesystem();
    $project_root = getcwd();
    $web_root = static::getDrupalRoot($project_root);

    // List directories to be created during install
    $dirs = [
      "$web_root/libraries/contrib",
      "$web_root/libraries/custom",
      "$web_root/modules/contrib",
      "$web_root/modules/custom",
      "$web_root/profiles/contrib",
      "$web_root/profiles/custom",
      "$web_root/sites/default",
      "$web_root/themes/contrib",
      "$web_root/themes/custom",
      "$project_root/drush/contrib",
      "$project_root/drush/custom",
      "$project_root/files/private",
      "$project_root/files/public",
      "$project_root/config/sync",
    ];

    // Create the listed directories
    foreach ($dirs as $dir) {
      if (!$fs->exists($dir)) {
        $fs->mkdir($dir, 0755);
        $fs->touch($dir . '/.gitkeep');
      }
    }
    $fs->chmod("$project_root/files/private", 0777);
    $fs->chmod("$project_root/files/public", 0777);
    $fs->chmod("$project_root/config/sync", 0777);

    // Create a symbolic link to the public files directory
    if (!$fs->exists($web_root . '/sites/default/files')) {
      symlink($project_root . '/files/public', $web_root . '/sites/default/files');
    }

    // Prepare the settings file for installation
    if (!$fs->exists($web_root . '/sites/default/settings.php') and $fs->exists($web_root . '/sites/default/default.settings.php')) {
      $fs->copy($web_root . '/sites/default/default.settings.php', $web_root . '/sites/default/settings.php');
      $fs->chmod($web_root . '/sites/default/settings.php', 0777);
    }

    // Prepare the services file for installation
    if (!$fs->exists($web_root . '/sites/default/services.yml') and $fs->exists($web_root . '/sites/default/default.services.yml')) {
      $fs->copy($web_root . '/sites/default/default.services.yml', $web_root . '/sites/default/services.yml');
      $fs->chmod($web_root . '/sites/default/services.yml', 0777);
    }

    // Add a local settings file and include it
    if (!$fs->exists($web_root . '/sites/default/settings.local.php')) {
      $settings = '<?php' . PHP_EOL;
      $settings .= '$settings["file_public_path"] = "sites/default/files";' . PHP_EOL;
      $settings .= '$settings["file_private_path"] = "' . getcwd() . '/files/private";' . PHP_EOL;
      $settings .= '$config_directories[CONFIG_SYNC_DIRECTORY] = "' . getcwd() . '/config/sync";' . PHP_EOL;
      $fs->dumpFile($web_root . '/sites/default/settings.local.php', $settings);
      $fs->chmod($web_root . '/sites/default/settings.local.php', 0777);
      $line = "file_exists(__DIR__ . '/settings.local.php') and include __DIR__ . '/settings.local.php';";
      file_put_contents($web_root . '/sites/default/settings.php', $line, FILE_APPEND);
    }

  }

  /**
   * Remove some leftover project files after project creation
   *
   * @param \Composer\Script\Event $event
   */
  public static function cleanupComposer(Event $event) {
    $fs = new Filesystem();
    $fs->remove([
      'LICENSE',
      '.travis.yml',
      'README.md',
      'phpunit.xml.dist',
      '.git'
    ]);

  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

}
