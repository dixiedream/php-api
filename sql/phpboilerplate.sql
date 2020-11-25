
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- api_key
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `api_key`;

CREATE TABLE `api_key`
(
    `expire_date` DATE NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `key_value` VARCHAR(255) NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`key_value`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
