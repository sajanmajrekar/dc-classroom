INSERT INTO `lecture_resources` (`lecture_id`, `label`, `resource_url`)
SELECT l.id, 'Lecture material', l.resource_url
FROM `lectures` l
WHERE l.resource_url IS NOT NULL
  AND l.resource_url <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `lecture_resources` lr WHERE lr.lecture_id = l.id
  );

CREATE TABLE `lecture_resource_progress` (
  `user_id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `completed_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `resource_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`resource_id`) REFERENCES `lecture_resources`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
