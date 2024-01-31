<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
$refactor->outputMessage('e', 'WARNING: open/close labels have been added to ERP sidebar, CSS has been added to private/share/packagist/vukbgit/simplex/src/Erp/sass/navigation.css which is by default included into private/share/packagist/vukbgit/simplex/src/Erp/sass/ERP.css which is by default included into private/local/simplex/Backend/sass/backend.scss, so it is probably necessary to re-compile this file;');
