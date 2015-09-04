
CREATE TABLE IF NOT EXISTS `tokens` (
    `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
    `code` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
    `token` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `lastaccess` bigint(20) NOT NULL,
    `data` blob NOT NULL,
    PRIMARY KEY (`code`),
    UNIQUE KEY `token` (`token`),
    KEY `lastaccess` (`lastaccess`),
    KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
