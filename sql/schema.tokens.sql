
CREATE TABLE IF NOT EXISTS `tokens` (
    `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
    `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `lastaccess` bigint(20) NOT NULL,
    `data` blob NOT NULL,
    PRIMARY KEY (`code`),
    UNIQUE KEY `token` (`token`),
    KEY `lastaccess` (`lastaccess`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
