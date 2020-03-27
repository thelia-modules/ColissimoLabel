# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- colissimo_label
-- ---------------------------------------------------------------------

ALTER TABLE `colissimo_label` ADD COLUMN `order_ref` VARCHAR(255) NOT NULL AFTER `order_id`;
ALTER TABLE `colissimo_label` ADD COLUMN `error` TINYINT NOT NULL AFTER `order_id`;
ALTER TABLE `colissimo_label` ADD COLUMN `error_message` VARCHAR(255) AFTER `order_ref`;
ALTER TABLE `colissimo_label` CHANGE COLUMN `number` `tracking_number` VARCHAR(255) NOT NULL;
ALTER TABLE `colissimo_label` ADD COLUMN `label_type` VARCHAR(4) NOT NULL AFTER `error_message`;
ALTER TABLE `colissimo_label` ADD COLUMN `with_customs_invoice` TINYINT NOT NULL AFTER `signed`;
ALTER TABLE `colissimo_label` MODIFY `tracking_number` VARCHAR(255) AFTER `error_message`;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;