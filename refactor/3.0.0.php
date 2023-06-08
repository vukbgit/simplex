<?php
declare(strict_types=1);
use function outputMessage as om;
//do not include local constants to avoid collisions
$includeLocalContants = false;
//bootstrap
require_once __DIR__ . '/../bin/bootstrap.php'; 
$refactor = $DIContainer->get('refactor');
$refactor->setVerbose(true);
$refactor->setDryRun(false);
//delete config ini draft
$refactor->deleteFile(
  ABS_PATH_TO_ROOT . '/config.draft.ini'
);
//environment in .htaccess
$refactor->searchPatternDeleteLine(
  'ENVIRONMENT',
  $folders = ['.'],
  $exclude = ['private'],
  $filesNames = ['.htaccess']
);
//replace index
$refactor->copyFile(
  PRIVATE_SHARE_SIMPLEX_DIR . '/installation/index.php',
  ABS_PATH_TO_ROOT . '/index.php'
);
om('e', 'ATTENTION: you must set into index.php relative path to ini config file ($pathToIniConfig variable)');
//filter
$refactor->searchPatternInLinesReplace(
  'FILTER_SANITIZE_STRING',
  $folders = ['private/local'],
  $exclude = [],
  $filesNames = ['*.php'],
  'FILTER_SANITIZE_FULL_SPECIAL_CHARS'
);
$refactor->searchPatternInLinesReplace(
  'FILTER_SANITIZE_SPECIAL_CHARS',
  $folders = ['private/local'],
  $exclude = [],
  $filesNames = ['*.php'],
  'FILTER_SANITIZE_FULL_SPECIAL_CHARS'
);
$refactor->searchMissingPattern(
  'set-side-bar-state',
  $folders = ['private/local'],
  $exclude = [],
  $filesNames = ['routes.php']
);
//simplex public js files
$refactor->copyFile(
  PRIVATE_SHARE_SIMPLEX_DIR . '/installation/public/share/simplex/js/simplex.js',
  ABS_PATH_TO_ROOT . '/public/share/simplex/js/simplex.js'
);
$refactor->copyFile(
  PRIVATE_SHARE_SIMPLEX_DIR . '/installation/public/share/simplex/Erp/js/erp.js',
  ABS_PATH_TO_ROOT . '/public/share/simplex/Erp/js/erp.js'
);
//constants
$refactor->searchPatternDeleteLine(
  'ABS_PATH_TO_ROOT',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_BASE_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_PACKAGIST_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_SIMPLEX_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_LOCAL_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  "define('SHARE_CONFIG_DIR', sprintf('%s/config', PRIVATE_SHARE_DIR));",
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'LOCAL_CONFIG_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_SHARE_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_LOCAL_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_LOCAL_SIMPLEX_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternDeleteLine(
  'TMP_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
$refactor->searchPatternReplace(
  "if(getenv('REDIRECT_ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('REDIRECT_ENVIRONMENT'));
} else {
    echo 'No evironment defined, you must set up root .htaccess';
    exit;
}",
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants-old.php']
);
