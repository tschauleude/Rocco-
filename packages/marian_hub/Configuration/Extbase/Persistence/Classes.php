<?php

declare(strict_types=1);

use Marian\Hub\Domain\Model\AbiTask;
use Marian\Hub\Domain\Model\Article;
use Marian\Hub\Domain\Model\ArticleSource;
use Marian\Hub\Domain\Model\Committee;
use Marian\Hub\Domain\Model\LogEntry;
use Marian\Hub\Domain\Model\Milestone;
use Marian\Hub\Domain\Model\Project;
use Marian\Hub\Domain\Model\Reading;
use Marian\Hub\Domain\Model\Sensor;

/**
 * Extbase leitet Tabellennamen sonst aus dem Namespace ab: aus "Marian\Hub"
 * würde tx_hub_domain_model_*. Der Extension-Key ist aber marian_hub, und so
 * heißen auch die Tabellen – deshalb die Zuordnung hier ausdrücklich.
 */
return [
    AbiTask::class => [
        'tableName' => 'tx_marianhub_domain_model_abitask',
    ],
    Committee::class => [
        'tableName' => 'tx_marianhub_domain_model_committee',
    ],
    Milestone::class => [
        'tableName' => 'tx_marianhub_domain_model_milestone',
    ],
    Article::class => [
        'tableName' => 'tx_marianhub_domain_model_article',
    ],
    ArticleSource::class => [
        'tableName' => 'tx_marianhub_domain_model_articlesource',
    ],
    Sensor::class => [
        'tableName' => 'tx_marianhub_domain_model_sensor',
    ],
    Reading::class => [
        'tableName' => 'tx_marianhub_domain_model_reading',
    ],
    Project::class => [
        'tableName' => 'tx_marianhub_domain_model_project',
    ],
    LogEntry::class => [
        'tableName' => 'tx_marianhub_domain_model_logentry',
    ],
];
