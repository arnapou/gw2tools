
CREATE TABLE IF NOT EXISTS `cache` (
    `hash` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
    `value` longblob NOT NULL,
    `expiration` bigint(20) NOT NULL,
    PRIMARY KEY (`hash`),
    KEY `expiration` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
