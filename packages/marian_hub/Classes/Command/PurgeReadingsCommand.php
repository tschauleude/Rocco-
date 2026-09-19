<?php

declare(strict_types=1);

namespace Marian\Hub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Räumt Messwerte auf, die älter sind als die Aufbewahrungsfrist des jeweiligen Sensors.
 *
 * Gedacht für den Scheduler – ein Sensor im Minutentakt produziert sonst
 * eine halbe Million Zeilen pro Jahr.
 */
class PurgeReadingsCommand extends Command
{
    private const SENSOR_TABLE = 'tx_marianhub_domain_model_sensor';
    private const READING_TABLE = 'tx_marianhub_domain_model_reading';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Nur zeigen, was gelöscht würde'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool)$input->getOption('dry-run');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::SENSOR_TABLE);
        $sensors = $queryBuilder
            ->select('uid', 'identifier', 'title', 'retention_days')
            ->from(self::SENSOR_TABLE)
            ->where(
                $queryBuilder->expr()->gt('retention_days', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $rows = [];
        $total = 0;

        foreach ($sensors as $sensor) {
            $threshold = time() - (int)$sensor['retention_days'] * 86400;
            $affected = $dryRun
                ? $this->countOlderThan((int)$sensor['uid'], $threshold)
                : $this->deleteOlderThan((int)$sensor['uid'], $threshold);

            $total += $affected;
            if ($affected > 0) {
                $rows[] = [$sensor['identifier'], $sensor['title'], $sensor['retention_days'] . ' Tage', $affected];
            }
        }

        if ($rows !== []) {
            $io->table(['Kennung', 'Sensor', 'Aufbewahrung', 'Messwerte'], $rows);
        }

        $io->success(sprintf(
            $dryRun ? '%d Messwerte würden gelöscht.' : '%d Messwerte gelöscht.',
            $total
        ));

        return Command::SUCCESS;
    }

    private function countOlderThan(int $sensorUid, int $threshold): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::READING_TABLE);

        return (int)$queryBuilder
            ->count('uid')
            ->from(self::READING_TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensorUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('measured_at', $queryBuilder->createNamedParameter($threshold, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function deleteOlderThan(int $sensorUid, int $threshold): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::READING_TABLE);

        return (int)$queryBuilder
            ->delete(self::READING_TABLE)
            ->where(
                $queryBuilder->expr()->eq('sensor', $queryBuilder->createNamedParameter($sensorUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->lt('measured_at', $queryBuilder->createNamedParameter($threshold, Connection::PARAM_INT)),
            )
            ->executeStatement();
    }
}
