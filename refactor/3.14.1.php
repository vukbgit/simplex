<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
//remove TinyMCE
$refactor->removeNpmPackage(
  'tinymce'
);
//remove Trix
$refactor->removeNpmPackage(
  '@mixtint/trix'
);
//instal Jodit
$refactor->installNpmPackage(
  'jodit',
  '>=4.0.0-beta.61'
);
$refactor->outputMessage('e', 'WARNING: JODIT text editor has been installed, it replaces TinyMCE so it could be necessary to manually remove language files stored into /public/share/rich-text-editor/locales/');
