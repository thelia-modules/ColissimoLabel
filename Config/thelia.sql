
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- colissimo_label
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `colissimo_label`;

CREATE TABLE `colissimo_label`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `order_id` INTEGER NOT NULL,
    `order_ref` VARCHAR(255) NOT NULL,
    `error` TINYINT(1) DEFAULT 0 NOT NULL,
    `error_message` VARCHAR(255) DEFAULT '',
    `tracking_number` VARCHAR(255),
    `label_type` VARCHAR(4),
    `weight` DECIMAL(6,2) DEFAULT 0.00,
    `signed` TINYINT(1) DEFAULT 0 NOT NULL,
    `with_customs_invoice` TINYINT(1) DEFAULT 0 NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `colissimo_label_fi_75704f` (`order_id`),
    CONSTRAINT `colissimo_label_fk_75704f`
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
