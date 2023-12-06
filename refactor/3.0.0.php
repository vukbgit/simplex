<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
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
$refactor->outputMessage('e', 'ATTENTION: you must set into index.php relative path to ini config file ($pathToIniConfig variable)');
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
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_BASE_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_PACKAGIST_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_SHARE_SIMPLEX_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PRIVATE_LOCAL_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  "define('SHARE_CONFIG_DIR', sprintf('%s/config', PRIVATE_SHARE_DIR));",
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'LOCAL_CONFIG_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_SHARE_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_LOCAL_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'PUBLIC_LOCAL_SIMPLEX_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'TMP_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'SHARE_TEMPLATES_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'LOCAL_TEMPLATES_DIR',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'TEMPLATES_EXTENSION',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
);
$refactor->searchPatternDeleteLine(
  'TEMPLATES_DEFAULT_FOLDER',
  $folders = ['private/local/simplex/config'],
  $exclude = [],
  $filesNames = ['constants.php']
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
  $filesNames = ['constants.php']
);