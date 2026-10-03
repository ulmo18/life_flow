CREATE TABLE IF NOT EXISTS `execution_energy_profiles` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `base_capacity` INT UNSIGNED NOT NULL DEFAULT 500,
  `effective_from` DATE NOT NULL,
  `body_state_note` VARCHAR(255) NULL,
  `mood_state_note` VARCHAR(255) NULL,
  `stress_state_note` VARCHAR(255) NULL,
  `injury_note` VARCHAR(255) NULL,
  `injury_until` DATE NULL,
  `inbody_note` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_execution_energy_profiles_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_execution_energy_profiles_base` CHECK (`base_capacity` >= 5 AND `base_capacity` <= 1000 AND MOD(`base_capacity`, 5) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `execution_energy_groups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(60) NOT NULL,
  `effect_type` VARCHAR(12) NOT NULL,
  `energy_value` INT UNSIGNED NOT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_execution_energy_groups_user` (`user_id`, `is_active`),
  CONSTRAINT `fk_execution_energy_groups_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_execution_energy_groups_type` CHECK (`effect_type` IN ('consume', 'recover')),
  CONSTRAINT `chk_execution_energy_groups_value` CHECK (`energy_value` >= 5 AND `energy_value` <= 1000 AND MOD(`energy_value`, 5) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `execution_energy_days` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `energy_date` DATE NOT NULL,
  `base_capacity_snapshot` INT UNSIGNED NOT NULL,
  `sleep_state` TINYINT NULL,
  `fatigue_state` TINYINT NULL,
  `mood_state` TINYINT NULL,
  `stress_state` TINYINT NULL,
  `burden_feedback` VARCHAR(12) NULL,
  `morning_adjustment` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_execution_energy_days_user_date` (`user_id`, `energy_date`),
  CONSTRAINT `fk_execution_energy_days_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_execution_energy_days_feedback` CHECK (`burden_feedback` IS NULL OR `burden_feedback` IN ('lower', 'similar', 'higher'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `execution_energy_event_settings` (
  `calendar_event_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `group_id_snapshot` BIGINT UNSIGNED NULL,
  `group_name_snapshot` VARCHAR(60) NULL,
  `effect_type` VARCHAR(12) NOT NULL,
  `energy_value` INT UNSIGNED NOT NULL,
  `is_override` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`calendar_event_id`),
  KEY `idx_execution_energy_event_settings_user` (`user_id`),
  CONSTRAINT `fk_execution_energy_event_settings_event`
    FOREIGN KEY (`calendar_event_id`) REFERENCES `calendar_events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_execution_energy_event_settings_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_execution_energy_event_settings_type` CHECK (`effect_type` IN ('consume', 'recover')),
  CONSTRAINT `chk_execution_energy_event_settings_value` CHECK (`energy_value` >= 5 AND `energy_value` <= 1000 AND MOD(`energy_value`, 5) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `execution_energy_adjustments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `energy_date` DATE NOT NULL,
  `time_index` SMALLINT UNSIGNED NOT NULL DEFAULT 144,
  `target_capacity` INT UNSIGNED NOT NULL,
  `reason` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_execution_energy_adjustments_user_date` (`user_id`, `energy_date`, `time_index`),
  CONSTRAINT `fk_execution_energy_adjustments_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_execution_energy_adjustments_index` CHECK (`time_index` >= 0 AND `time_index` <= 144),
  CONSTRAINT `chk_execution_energy_adjustments_target` CHECK (`target_capacity` >= 0 AND `target_capacity` <= 1000 AND MOD(`target_capacity`, 5) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `execution_energy_notices` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `energy_date` DATE NOT NULL,
  `notice_level` TINYINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `energy_date`, `notice_level`),
  CONSTRAINT `fk_execution_energy_notices_user`
    FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
