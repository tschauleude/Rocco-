#
# Abi-Organisation
#
CREATE TABLE tx_marianhub_domain_model_committee (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    lead varchar(255) DEFAULT '' NOT NULL,
    color varchar(7) DEFAULT '#6366f1' NOT NULL,
    members text,
    tasks int(11) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_marianhub_domain_model_abitask (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    phase varchar(20) DEFAULT 'idea' NOT NULL,
    priority tinyint(4) unsigned DEFAULT '1' NOT NULL,
    due_date int(11) DEFAULT '0' NOT NULL,
    assignee varchar(255) DEFAULT '' NOT NULL,
    estimate decimal(6,2) DEFAULT '0.00' NOT NULL,
    budget decimal(10,2) DEFAULT '0.00' NOT NULL,
    committee int(11) unsigned DEFAULT '0' NOT NULL,

    KEY phase (phase, deleted, hidden)
);

CREATE TABLE tx_marianhub_domain_model_milestone (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    date int(11) DEFAULT '0' NOT NULL,
    kind varchar(30) DEFAULT 'deadline' NOT NULL,
    is_countdown_target tinyint(1) unsigned DEFAULT '0' NOT NULL,

    KEY date (date, deleted, hidden)
);

#
# Wiki / journalistische Arbeit
#
CREATE TABLE tx_marianhub_domain_model_article (
    title varchar(255) DEFAULT '' NOT NULL,
    slug varchar(255) DEFAULT '' NOT NULL,
    subtitle varchar(255) DEFAULT '' NOT NULL,
    teaser text,
    bodytext mediumtext,
    status varchar(20) DEFAULT 'draft' NOT NULL,
    author varchar(255) DEFAULT '' NOT NULL,
    published_at int(11) DEFAULT '0' NOT NULL,
    revision int(11) unsigned DEFAULT '1' NOT NULL,
    change_note varchar(255) DEFAULT '' NOT NULL,
    categories int(11) unsigned DEFAULT '0' NOT NULL,
    sources int(11) unsigned DEFAULT '0' NOT NULL,
    cover_image int(11) unsigned DEFAULT '0' NOT NULL,
    related_project int(11) unsigned DEFAULT '0' NOT NULL,

    KEY slug (slug),
    KEY status (status, deleted, hidden)
);

CREATE TABLE tx_marianhub_domain_model_articlesource (
    article int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    url varchar(2048) DEFAULT '' NOT NULL,
    kind varchar(30) DEFAULT 'web' NOT NULL,
    accessed_at int(11) DEFAULT '0' NOT NULL,
    note text,
    sorting int(11) DEFAULT '0' NOT NULL
);

#
# Sensorik / Arduino
#
CREATE TABLE tx_marianhub_domain_model_sensor (
    title varchar(255) DEFAULT '' NOT NULL,
    identifier varchar(80) DEFAULT '' NOT NULL,
    description text,
    location varchar(255) DEFAULT '' NOT NULL,
    unit varchar(20) DEFAULT '' NOT NULL,
    kind varchar(40) DEFAULT 'generic' NOT NULL,
    decimals tinyint(3) unsigned DEFAULT '1' NOT NULL,
    token_hash varchar(64) DEFAULT '' NOT NULL,
    warn_min decimal(12,4) DEFAULT NULL,
    warn_max decimal(12,4) DEFAULT NULL,
    last_seen int(11) DEFAULT '0' NOT NULL,
    retention_days int(11) unsigned DEFAULT '90' NOT NULL,
    active tinyint(1) unsigned DEFAULT '1' NOT NULL,
    project int(11) unsigned DEFAULT '0' NOT NULL,
    readings int(11) unsigned DEFAULT '0' NOT NULL,

    UNIQUE KEY identifier (identifier)
);

CREATE TABLE tx_marianhub_domain_model_reading (
    sensor int(11) unsigned DEFAULT '0' NOT NULL,
    value decimal(12,4) DEFAULT '0.0000' NOT NULL,
    measured_at int(11) DEFAULT '0' NOT NULL,
    payload text,

    KEY sensor_time (sensor, measured_at)
);

#
# Projekte
#
CREATE TABLE tx_marianhub_domain_model_project (
    title varchar(255) DEFAULT '' NOT NULL,
    slug varchar(255) DEFAULT '' NOT NULL,
    subtitle varchar(255) DEFAULT '' NOT NULL,
    teaser text,
    description mediumtext,
    status varchar(20) DEFAULT 'idea' NOT NULL,
    progress tinyint(3) unsigned DEFAULT '0' NOT NULL,
    started_at int(11) DEFAULT '0' NOT NULL,
    finished_at int(11) DEFAULT '0' NOT NULL,
    repository_url varchar(2048) DEFAULT '' NOT NULL,
    bill_of_materials text,
    cover_image int(11) unsigned DEFAULT '0' NOT NULL,
    categories int(11) unsigned DEFAULT '0' NOT NULL,
    log_entries int(11) unsigned DEFAULT '0' NOT NULL,

    KEY slug (slug),
    KEY status (status, deleted, hidden)
);

CREATE TABLE tx_marianhub_domain_model_logentry (
    project int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    entry_date int(11) DEFAULT '0' NOT NULL,
    bodytext text,
    mood varchar(20) DEFAULT 'neutral' NOT NULL,
    hours_spent decimal(6,2) DEFAULT '0.00' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL
);
