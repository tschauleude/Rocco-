/**
 * Marians Werkstatt – das bisschen Interaktion, das die Seite braucht.
 *
 * Bewusst ohne Framework und ohne Chart-Bibliothek: ein Liniendiagramm ist
 * ein Pfad durch skalierte Punkte, und der Countdown ist eine Subtraktion.
 */
(function () {
    'use strict';

    /**
     * Liest eine CSS-Variable aus, damit das Diagramm im Dunkelmodus mitzieht.
     */
    function cssVar(element, name, fallback) {
        var value = getComputedStyle(element).getPropertyValue(name).trim();
        return value !== '' ? value : fallback;
    }

    function formatValue(value, decimals, unit) {
        var text = value.toLocaleString('de-DE', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        return unit ? text + ' ' + unit : text;
    }

    function formatTime(timestamp, spanHours) {
        var date = new Date(timestamp);
        if (spanHours > 48) {
            return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
        }
        return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    }

    /**
     * Zeichnet eine Messreihe als Liniendiagramm mit Achsenbeschriftung.
     */
    function drawChart(canvas) {
        var raw = canvas.dataset.series;
        if (!raw) {
            return;
        }

        var series;
        try {
            series = JSON.parse(raw);
        } catch (error) {
            return;
        }

        if (!Array.isArray(series) || series.length < 2) {
            return;
        }

        var ratio = window.devicePixelRatio || 1;
        var width = canvas.clientWidth || 640;
        var height = parseInt(canvas.getAttribute('height'), 10) || 280;

        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.height = height + 'px';

        var ctx = canvas.getContext('2d');
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, width, height);

        var decimals = parseInt(canvas.dataset.decimals, 10) || 0;
        var unit = canvas.dataset.unit || '';
        var accent = cssVar(canvas, '--mh-accent', '#4f46e5');
        var border = cssVar(canvas, '--mh-border', '#dfe2ee');
        var muted = cssVar(canvas, '--mh-text-muted', '#5d6478');
        var danger = cssVar(canvas, '--mh-danger', '#b42318');

        var padding = { top: 16, right: 12, bottom: 28, left: 56 };
        var plotWidth = width - padding.left - padding.right;
        var plotHeight = height - padding.top - padding.bottom;

        var times = series.map(function (point) { return point[0]; });
        var values = series.map(function (point) { return point[1]; });

        var minTime = Math.min.apply(null, times);
        var maxTime = Math.max.apply(null, times);
        var minValue = Math.min.apply(null, values);
        var maxValue = Math.max.apply(null, values);

        // Eine völlig flache Reihe bekommt trotzdem eine sichtbare Skala.
        if (minValue === maxValue) {
            minValue -= 1;
            maxValue += 1;
        }

        var valueSpan = maxValue - minValue;
        var padValue = valueSpan * 0.1;
        minValue -= padValue;
        maxValue += padValue;
        valueSpan = maxValue - minValue;

        var timeSpan = maxTime - minTime || 1;
        var spanHours = timeSpan / 3600000;

        function toX(time) {
            return padding.left + (time - minTime) / timeSpan * plotWidth;
        }

        function toY(value) {
            return padding.top + (maxValue - value) / valueSpan * plotHeight;
        }

        // Waagerechtes Raster mit Wertebeschriftung
        ctx.font = '11px system-ui, sans-serif';
        ctx.textBaseline = 'middle';
        ctx.textAlign = 'right';

        var gridLines = 4;
        for (var i = 0; i <= gridLines; i++) {
            var value = minValue + valueSpan * (i / gridLines);
            var y = toY(value);

            ctx.strokeStyle = border;
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding.left, y);
            ctx.lineTo(width - padding.right, y);
            ctx.stroke();

            ctx.fillStyle = muted;
            ctx.fillText(formatValue(value, decimals, ''), padding.left - 8, y);
        }

        // Warnschwellen als gestrichelte Linien
        [canvas.dataset.warnMin, canvas.dataset.warnMax].forEach(function (threshold) {
            var limit = parseFloat(threshold);
            if (isNaN(limit) || limit < minValue || limit > maxValue) {
                return;
            }

            ctx.save();
            ctx.strokeStyle = danger;
            ctx.setLineDash([4, 4]);
            ctx.beginPath();
            ctx.moveTo(padding.left, toY(limit));
            ctx.lineTo(width - padding.right, toY(limit));
            ctx.stroke();
            ctx.restore();
        });

        // Zeitachse
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        var timeLabels = 4;
        for (var t = 0; t <= timeLabels; t++) {
            var time = minTime + timeSpan * (t / timeLabels);
            ctx.fillStyle = muted;
            ctx.fillText(formatTime(time, spanHours), toX(time), height - padding.bottom + 8);
        }

        // Fläche unter der Kurve
        ctx.beginPath();
        ctx.moveTo(toX(series[0][0]), height - padding.bottom);
        series.forEach(function (point) {
            ctx.lineTo(toX(point[0]), toY(point[1]));
        });
        ctx.lineTo(toX(series[series.length - 1][0]), height - padding.bottom);
        ctx.closePath();
        ctx.fillStyle = accent;
        ctx.globalAlpha = 0.12;
        ctx.fill();
        ctx.globalAlpha = 1;

        // Die Linie selbst
        ctx.beginPath();
        series.forEach(function (point, index) {
            var x = toX(point[0]);
            var y = toY(point[1]);
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        ctx.strokeStyle = accent;
        ctx.lineWidth = 2;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.stroke();

        // Letzter Messwert als Punkt mit Beschriftung
        var last = series[series.length - 1];
        ctx.beginPath();
        ctx.arc(toX(last[0]), toY(last[1]), 3.5, 0, Math.PI * 2);
        ctx.fillStyle = accent;
        ctx.fill();

        canvas.setAttribute(
            'aria-label',
            (canvas.getAttribute('aria-label') || 'Messverlauf') +
            ': zuletzt ' + formatValue(last[1], decimals, unit)
        );
    }

    /**
     * Zählt den Countdown taggenau weiter, ohne dass jemand neu laden muss.
     */
    function updateCountdown(element) {
        var target = parseInt(element.dataset.countdownTarget, 10);
        if (!target) {
            return;
        }

        var output = element.querySelector('[data-countdown-days]');
        if (!output) {
            return;
        }

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var targetDate = new Date(target * 1000);
        targetDate.setHours(0, 0, 0, 0);

        var days = Math.round((targetDate - today) / 86400000);
        output.textContent = String(days);
    }

    /**
     * Lädt Dashboards in eingestelltem Takt neu – für den Blick aufs Regal.
     */
    function scheduleRefresh(element) {
        var seconds = parseInt(element.dataset.refresh, 10);
        if (!seconds || seconds < 5) {
            return;
        }

        window.setTimeout(function () {
            window.location.reload();
        }, seconds * 1000);
    }

    function init() {
        document.querySelectorAll('canvas.chart').forEach(drawChart);
        document.querySelectorAll('[data-countdown-target]').forEach(updateCountdown);
        document.querySelectorAll('[data-refresh]').forEach(scheduleRefresh);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Beim Drehen des Handys oder Ändern der Fensterbreite neu zeichnen.
    var resizeTimer = null;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(function () {
            document.querySelectorAll('canvas.chart').forEach(drawChart);
        }, 200);
    });
}());
