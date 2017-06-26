CREATE TABLE IF NOT EXISTS `dongles` (
    `name`          TEXT,
    `state`         TEXT,
    `rssi`          INTEGER,
    `mode`          INTEGER,
    `submode`       INTEGER,
    `provider`      TEXT,
    `model`         TEXT,
    `firmware`      TEXT,
    `imei`          TEXT,
    `imsi`          TEXT,
    `updated`       INTEGER
);
CREATE TABLE IF NOT EXISTS `routes` (
    `hash`          TEXT,
    `user_id`       INTEGER,
    `user_dn`       TEXT,
    `client_ph`     TEXT,
    `client_dn`     TEXT,
    `created`       INTEGER,
    `expired`       INTEGER,
    `state`         TEXT
);
CREATE TABLE IF NOT EXISTS `tasks` (
    `id`            INTEGER PRIMARY KEY,
    `user_id`       INTEGER,
    `income_dongle` TEXT,
    `dongles`       TEXT,
    `expires_on`    INTEGER
);
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INTEGER PRIMARY KEY,
    `phone`         TEXT,
    `service`       TEXT
);
CREATE TABLE IF NOT EXISTS `calls` (
    `hash`          TEXT PRIMARY KEY,
    `route`         TEXT,
    `direction`     TEXT,
    `result`        TEXT,
    `dial_length`   INTEGER,
    `answ_length`   INTEGER,
    `created_on`    INTEGER,
    `rec_hash`      TEXT
);
