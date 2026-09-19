<?php

declare(strict_types=1);

namespace Marian\Hub\Command;

use Marian\Hub\Service\SensorIngestService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Erzeugt ein neues API-Token für einen Sensor.
 *
 *   vendor/bin/typo3 marian:sensor:token balkon-temp
 *
 * Der Klartext wird genau einmal ausgegeben – danach steht in der Datenbank nur der Hash.
 */
class SensorTokenCommand extends Command
{
    private const TABLE = 'tx_marianhub_domain_model_sensor';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'identifier',
            InputArgument::REQUIRED,
            'Kennung des Sensors, z. B. "balkon-temp"'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $identifier = (string)$input->getArgument('identifier');

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $sensor = $queryBuilder
            ->select('uid', 'title')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($sensor === false) {
            $io->error(sprintf('Kein Sensor mit der Kennung "%s" gefunden.', $identifier));

            return Command::FAILURE;
        }

        ['token' => $token, 'hash' => $hash] = SensorIngestService::createToken();

        $this->connectionPool->getConnectionForTable(self::TABLE)->update(
            self::TABLE,
            ['token_hash' => $hash, 'tstamp' => time()],
            ['uid' => (int)$sensor['uid']],
            [Connection::PARAM_STR, Connection::PARAM_INT]
        );

        $io->success(sprintf('Neues Token für "%s" (%s).', $sensor['title'], $identifier));
        $io->writeln('Token (nur jetzt sichtbar, ab in den Sketch damit):');
        $io->writeln('');
        $io->writeln('  ' . $token);
        $io->writeln('');
        $io->note('Ein zuvor vergebenes Token für diesen Sensor gilt ab sofort nicht mehr.');

        return Command::SUCCESS;
    }
}
