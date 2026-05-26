# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- colissimo_label_product_customs
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `colissimo_label_product_customs`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `product_id` INTEGER NOT NULL,
    `hs_code` VARCHAR(50) DEFAULT '' NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `colissimo_label_product_customs_product_id_unique` (`product_id`),
    CONSTRAINT `colissimo_label_product_customs_fk_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `product` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
