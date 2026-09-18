ALTER TABLE `lectures`
ADD COLUMN `subtitle` VARCHAR(255) NULL AFTER `title`;

CREATE TABLE `lecture_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lecture_id` int(11) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `resource_url` text NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`lecture_id`) REFERENCES `lectures`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
