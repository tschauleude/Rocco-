<?php

declare(strict_types=1);

namespace Marian\Shop\Exception;

/**
 * Fehler bei der Zahlungsabwicklung. Die Meldung ist für Kundenaugen
 * gedacht – technische Einzelheiten gehören ins Log, nicht ins Frontend.
 */
class PaymentException extends \RuntimeException {}
