#
# Katalog
#
CREATE TABLE tx_marianshop_domain_model_product (
    title varchar(255) DEFAULT '' NOT NULL,
    slug varchar(255) DEFAULT '' NOT NULL,
    sku varchar(60) DEFAULT '' NOT NULL,
    subtitle varchar(255) DEFAULT '' NOT NULL,
    teaser text,
    description mediumtext,
    price decimal(10,2) DEFAULT '0.00' NOT NULL,
    price_old decimal(10,2) DEFAULT '0.00' NOT NULL,
    tax_rate decimal(5,2) DEFAULT '19.00' NOT NULL,
    stock int(11) DEFAULT '0' NOT NULL,
    stock_managed tinyint(1) unsigned DEFAULT '1' NOT NULL,
    max_per_order int(11) unsigned DEFAULT '10' NOT NULL,
    weight decimal(8,3) DEFAULT '0.000' NOT NULL,
    delivery_time varchar(80) DEFAULT '' NOT NULL,
    featured tinyint(1) unsigned DEFAULT '0' NOT NULL,
    images int(11) unsigned DEFAULT '0' NOT NULL,
    categories int(11) unsigned DEFAULT '0' NOT NULL,
    variants int(11) unsigned DEFAULT '0' NOT NULL,

    KEY slug (slug),
    KEY sku (sku)
);

CREATE TABLE tx_marianshop_domain_model_productvariant (
    product int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(80) DEFAULT '' NOT NULL,
    sku varchar(60) DEFAULT '' NOT NULL,
    price_delta decimal(10,2) DEFAULT '0.00' NOT NULL,
    stock int(11) DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL
);

#
# Versand und Zahlung
#
CREATE TABLE tx_marianshop_domain_model_shippingmethod (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    price decimal(10,2) DEFAULT '0.00' NOT NULL,
    free_from decimal(10,2) DEFAULT '0.00' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL
);

CREATE TABLE tx_marianshop_domain_model_paymentmethod (
    title varchar(255) DEFAULT '' NOT NULL,
    description text,
    provider varchar(40) DEFAULT 'invoice' NOT NULL,
    surcharge decimal(10,2) DEFAULT '0.00' NOT NULL,
    instructions text,
    sorting int(11) DEFAULT '0' NOT NULL
);

#
# Adressen. Bestelladressen sind Kopien: ändert der Kunde später sein
# Adressbuch, darf die alte Bestellung sich nicht mitverändern.
#
CREATE TABLE tx_marianshop_domain_model_address (
    fe_user int(11) unsigned DEFAULT '0' NOT NULL,
    kind varchar(10) DEFAULT 'billing' NOT NULL,
    salutation varchar(20) DEFAULT '' NOT NULL,
    first_name varchar(120) DEFAULT '' NOT NULL,
    last_name varchar(120) DEFAULT '' NOT NULL,
    company varchar(180) DEFAULT '' NOT NULL,
    street varchar(180) DEFAULT '' NOT NULL,
    house_number varchar(30) DEFAULT '' NOT NULL,
    zip varchar(20) DEFAULT '' NOT NULL,
    city varchar(120) DEFAULT '' NOT NULL,
    country varchar(2) DEFAULT 'DE' NOT NULL,
    is_default tinyint(1) unsigned DEFAULT '0' NOT NULL,
    is_archived tinyint(1) unsigned DEFAULT '0' NOT NULL,

    KEY fe_user (fe_user, is_archived)
);

#
# Bestellungen. Alle Beträge in Cent – Geld rechnet man nicht in Fließkomma.
#
CREATE TABLE tx_marianshop_domain_model_order (
    order_number varchar(20) DEFAULT '' NOT NULL,
    fe_user int(11) unsigned DEFAULT '0' NOT NULL,
    email varchar(255) DEFAULT '' NOT NULL,
    phone varchar(60) DEFAULT '' NOT NULL,

    billing_address int(11) unsigned DEFAULT '0' NOT NULL,
    shipping_address int(11) unsigned DEFAULT '0' NOT NULL,
    items int(11) unsigned DEFAULT '0' NOT NULL,

    shipping_method int(11) unsigned DEFAULT '0' NOT NULL,
    shipping_title varchar(255) DEFAULT '' NOT NULL,
    shipping_gross int(11) DEFAULT '0' NOT NULL,

    payment_method int(11) unsigned DEFAULT '0' NOT NULL,
    payment_title varchar(255) DEFAULT '' NOT NULL,
    payment_provider varchar(40) DEFAULT '' NOT NULL,
    payment_surcharge_gross int(11) DEFAULT '0' NOT NULL,

    subtotal_gross int(11) DEFAULT '0' NOT NULL,
    total_gross int(11) DEFAULT '0' NOT NULL,
    total_net int(11) DEFAULT '0' NOT NULL,
    tax_total int(11) DEFAULT '0' NOT NULL,
    tax_breakdown text,

    status varchar(20) DEFAULT 'new' NOT NULL,
    payment_status varchar(20) DEFAULT 'pending' NOT NULL,
    payment_reference varchar(255) DEFAULT '' NOT NULL,
    paid_at int(11) DEFAULT '0' NOT NULL,

    customer_note text,
    accepted_terms tinyint(1) unsigned DEFAULT '0' NOT NULL,
    accepted_withdrawal tinyint(1) unsigned DEFAULT '0' NOT NULL,
    ordered_at int(11) DEFAULT '0' NOT NULL,

    UNIQUE KEY order_number (order_number),
    KEY fe_user (fe_user, ordered_at),
    KEY payment_reference (payment_reference)
);

CREATE TABLE tx_marianshop_domain_model_orderitem (
    `order` int(11) unsigned DEFAULT '0' NOT NULL,
    product int(11) unsigned DEFAULT '0' NOT NULL,
    variant int(11) unsigned DEFAULT '0' NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL,
    variant_title varchar(80) DEFAULT '' NOT NULL,
    sku varchar(60) DEFAULT '' NOT NULL,
    quantity int(11) unsigned DEFAULT '1' NOT NULL,
    unit_gross int(11) DEFAULT '0' NOT NULL,
    line_gross int(11) DEFAULT '0' NOT NULL,
    line_tax int(11) DEFAULT '0' NOT NULL,
    tax_rate decimal(5,2) DEFAULT '19.00' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL
);

#
# Kundenkonto auf Basis der TYPO3-Frontend-Benutzer
#
CREATE TABLE fe_users (
    tx_marianshop_phone varchar(60) DEFAULT '' NOT NULL,
    tx_marianshop_addresses int(11) unsigned DEFAULT '0' NOT NULL,
    tx_marianshop_token varchar(64) DEFAULT '' NOT NULL,
    tx_marianshop_token_expires int(11) DEFAULT '0' NOT NULL
);

# Bewusst ohne Index auf tx_marianshop_token: einen Schlüssel auf eine Spalte
# zu legen, die einer fremden Tabelle erst hinzugefügt wird, legte hier den
# Index vor der Spalte an – und ein Index auf eine nicht existierende Spalte
# blockiert danach jeden weiteren Schemavergleich. Token werden ohnehin nur
# beim Bestätigen einer Registrierung nachgeschlagen.
