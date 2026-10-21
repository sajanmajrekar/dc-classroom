ALTER TABLE `lectures`
ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0 AFTER `class_id`;

UPDATE `lectures`
SET `sort_order` = `id`
WHERE `sort_order` = 0;
