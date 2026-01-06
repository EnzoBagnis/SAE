<?php

namespace Controllers\Import;

// Proxy controller to forward calls to Controllers\Analysis\ImportController
require_once __DIR__ . '/../../models/Database.php';
require_once __DIR__ . '/../Analysis/ImportController.php';

use Controllers\Analysis\ImportController as AnalysisImportController;

class ImportController extends AnalysisImportController
{
    // No changes required: inherit behavior from Analysis\ImportController
}
