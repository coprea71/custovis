<?php

namespace App\Services\Erp;

use RuntimeException;

/**
 * Raised for any ERP failure. Messages are generic on purpose so that no
 * remote payload or credential ends up in logs or the UI.
 */
class ErpLookupException extends RuntimeException {}
