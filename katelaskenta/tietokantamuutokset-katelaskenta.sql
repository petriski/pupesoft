ALTER TABLE `tuote`
	ADD COLUMN `myyntikate` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT 'prosentteina, ei desimaalina',
	ADD COLUMN `myymalakate` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT 'prosentteina, ei desimaalina',
	ADD COLUMN `nettokate` DECIMAL(10,0) NOT NULL DEFAULT '0' COMMENT 'prosentteina, ei desimaalina',
	ADD COLUMN `hintamuutospvm` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
