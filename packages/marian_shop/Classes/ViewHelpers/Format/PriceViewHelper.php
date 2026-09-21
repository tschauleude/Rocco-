<?php

declare(strict_types=1);

namespace Marian\Shop\ViewHelpers\Format;

use Marian\Shop\Service\Money;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Gibt einen Cent-Betrag als Preis aus.
 *
 * <s:format.price>{item.lineGross}</s:format.price> → 29,90 €
 */
class PriceViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'int', 'Betrag in Cent');
        $this->registerArgument('withCurrency', 'bool', 'Währungszeichen anhängen', false, true);
    }

    public function render(): string
    {
        $cents = (int)($this->arguments['value'] ?? $this->renderChildren());

        $formatted = Money::format($cents);

        return (bool)$this->arguments['withCurrency']
            ? $formatted
            : trim(str_replace('€', '', $formatted));
    }
}
