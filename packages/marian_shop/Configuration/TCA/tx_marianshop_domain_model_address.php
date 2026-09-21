<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Anschrift',
        'label' => 'last_name',
        'label_alt' => 'city',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'last_name ASC',
        'hideTable' => true,
        'searchFields' => 'first_name,last_name,company,street,zip,city',
        'iconfile' => 'EXT:marian_shop/Resources/Public/Icons/address.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'fe_user' => [
            'label' => 'Kunde',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'fe_users',
                'items' => [['label' => '– Bestelladresse ohne Konto –', 'value' => 0]],
                'default' => 0,
            ],
        ],
        'kind' => [
            'label' => 'Art',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Rechnungsanschrift', 'value' => 'billing'],
                    ['label' => 'Lieferanschrift', 'value' => 'shipping'],
                ],
                'default' => 'billing',
            ],
        ],
        'salutation' => [
            'label' => 'Anrede',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'keine Angabe', 'value' => ''],
                    ['label' => 'Frau', 'value' => 'mrs'],
                    ['label' => 'Herr', 'value' => 'mr'],
                    ['label' => 'Divers', 'value' => 'divers'],
                ],
                'default' => '',
            ],
        ],
        'first_name' => ['label' => 'Vorname', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim', 'required' => true]],
        'last_name' => ['label' => 'Nachname', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim', 'required' => true]],
        'company' => ['label' => 'Firma', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim']],
        'street' => ['label' => 'Straße', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim', 'required' => true]],
        'house_number' => ['label' => 'Hausnummer', 'config' => ['type' => 'input', 'size' => 10, 'eval' => 'trim', 'required' => true]],
        'zip' => ['label' => 'PLZ', 'config' => ['type' => 'input', 'size' => 10, 'eval' => 'trim', 'required' => true]],
        'city' => ['label' => 'Ort', 'config' => ['type' => 'input', 'size' => 30, 'eval' => 'trim', 'required' => true]],
        'country' => [
            'label' => 'Land',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Deutschland', 'value' => 'DE'],
                    ['label' => 'Österreich', 'value' => 'AT'],
                    ['label' => 'Schweiz', 'value' => 'CH'],
                ],
                'default' => 'DE',
            ],
        ],
        'is_default' => [
            'label' => 'Standardanschrift',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle', 'default' => 0],
        ],
        'is_archived' => [
            'label' => 'Archiviert (gehört zu einer Bestellung)',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle', 'default' => 0],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'kind, salutation, first_name, last_name, company,
                street, house_number, zip, city, country,
                --div--;Zuordnung, fe_user, is_default, is_archived',
        ],
    ],
];
