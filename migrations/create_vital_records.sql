-- Migration: create_vital_records
-- Run this in phpMyAdmin or via MySQL CLI against the `caresync` database.

CREATE TABLE IF NOT EXISTS `vital_records` (
  `id`                int(11)       NOT NULL AUTO_INCREMENT,
  `patient_code`      varchar(20)   NOT NULL,
  `attendee_code`     varchar(20)   DEFAULT NULL,
  `blood_pressure`    varchar(20)   DEFAULT NULL COMMENT 'e.g. 120/80',
  `heart_rate`        int(11)       DEFAULT NULL COMMENT 'bpm',
  `temperature`       decimal(4,1)  DEFAULT NULL COMMENT 'Celsius',
  `respiratory_rate`  int(11)       DEFAULT NULL COMMENT 'breaths/min',
  `oxygen_saturation` decimal(4,1)  DEFAULT NULL COMMENT 'percent',
  `blood_sugar`       decimal(6,1)  DEFAULT NULL COMMENT 'mg/dL',
  `weight_kg`         decimal(5,1)  DEFAULT NULL,
  `height_cm`         decimal(5,1)  DEFAULT NULL,
  `notes`             text          DEFAULT NULL,
  `recorded_at`       timestamp     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_patient_code` (`patient_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
