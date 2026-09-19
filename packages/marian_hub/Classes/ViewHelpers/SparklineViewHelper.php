<?php

declare(strict_types=1);

namespace Marian\Hub\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Zeichnet eine Messreihe als kleines Inline-SVG.
 *
 * Bewusst serverseitig und ohne Chart-Bibliothek: eine Sparkline ist ein Polygonzug,
 * dafür lädt niemand 200 KB JavaScript.
 *
 * <mh:sparkline points="{tile.points}" width="280" height="56" />
 */
class SparklineViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('points', 'array', 'Messreihe aus ["time" => int, "value" => float]', true);
        $this->registerArgument('width', 'int', 'Breite in Pixeln', false, 280);
        $this->registerArgument('height', 'int', 'Höhe in Pixeln', false, 56);
        $this->registerArgument('label', 'string', 'Beschriftung für Screenreader', false, 'Messverlauf');
    }

    public function render(): string
    {
        /** @var array<int, array{time: int, value: float}> $points */
        $points = $this->arguments['points'] ?? [];
        $width = max(40, (int)$this->arguments['width']);
        $height = max(20, (int)$this->arguments['height']);

        $values = array_map(static fn (array $point): float => (float)$point['value'], $points);
        if (count($values) < 2) {
            return sprintf(
                '<svg class="sparkline sparkline--empty" viewBox="0 0 %d %d" width="%d" height="%d" role="img" aria-label="Zu wenig Daten für einen Verlauf"><line x1="0" y1="%.1f" x2="%d" y2="%.1f" /></svg>',
                $width,
                $height,
                $width,
                $height,
                $height / 2,
                $width,
                $height / 2
            );
        }

        $min = min($values);
        $max = max($values);
        $span = $max - $min;
        // Eine flache Linie soll mittig liegen und nicht durch Null geteilt werden.
        $scale = $span > 0.0 ? $span : 1.0;

        $padding = 2.0;
        $usableHeight = $height - 2 * $padding;
        $lastIndex = count($values) - 1;

        $coordinates = [];
        foreach ($values as $index => $value) {
            $x = $lastIndex > 0 ? $index / $lastIndex * $width : 0.0;
            $y = $span > 0.0
                ? $padding + ($max - $value) / $scale * $usableHeight
                : $height / 2;
            $coordinates[] = sprintf('%.2f,%.2f', $x, $y);
        }

        $line = implode(' ', $coordinates);
        $area = sprintf('%.2f,%.2f %s %.2f,%.2f', 0.0, (float)$height, $line, (float)$width, (float)$height);

        return sprintf(
            '<svg class="sparkline" viewBox="0 0 %d %d" width="%d" height="%d" preserveAspectRatio="none" role="img" aria-label="%s">'
            . '<polygon class="sparkline__area" points="%s" />'
            . '<polyline class="sparkline__line" points="%s" />'
            . '</svg>',
            $width,
            $height,
            $width,
            $height,
            htmlspecialchars((string)$this->arguments['label'], ENT_QUOTES),
            $area,
            $line
        );
    }
}
