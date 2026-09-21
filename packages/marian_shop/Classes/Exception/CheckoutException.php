<?php

declare(strict_types=1);

namespace Marian\Shop\Exception;

/**
 * Die Bestellung konnte nicht angelegt werden – etwa weil zwischenzeitlich
 * der Bestand nicht mehr reicht.
 */
class CheckoutException extends \RuntimeException {}
