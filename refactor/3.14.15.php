<?php
/**
 * Refactor files get included into bin/refactor.php which passes $refactor object
 */
$refactor->setVerbose(true);
$refactor->outputMessage('e', 'WARNING: ERP top navbar has been rearranged and now it needs Bootstrap offcanvas, it is necessary to add it to ERP area Bootstrap file by enabling @import "bootstrap/scss/offcanvas";');
