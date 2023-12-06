<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
$refactor->setDryRun(false);
//filter
$richTextEditorFiles = array_map(
  fn($f) => str_replace(ABS_PATH_TO_ROOT, '', $f->getRealPath()),
  $refactor->searchPatternInFiles(
    'displayRichTextEditor',
    $folders = ['private/local'],
    $exclude = [],
    $filesNames = ['*.twig']
  )
);
if(!empty($richTextEditorFiles)) {
  $refactor->outputMessage('e', sprintf('ATTENTION: the following tmeplate files contains richTextEditor macro calls, editor is changed from TINYMCE to JODIT and might be necessary to check custom editor parameters: %s', implode("\n", $richTextEditorFiles)));
}
