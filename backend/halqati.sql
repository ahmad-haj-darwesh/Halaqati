DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_log` (`id`,`log_name`,`description`,`subject_type`,`subject_id`,`event`,`causer_type`,`causer_id`,`properties`,`batch_uuid`,`created_at`,`updated_at`) VALUES (1,'default','تم إنشاء زيارة ميدانية','App\\Models\\SupervisorFieldVisit',1,'created','App\\Models\\User',8,'{\"attributes\":{\"teaching_skill_score\":7,\"plan_adherence_score\":8,\"student_engagement_score\":8,\"notes\":\"\\u0627\\u0644\\u0645\\u0639\\u0644\\u0645 \\u0644\\u0627 \\u064a\\u0639\\u0637\\u064a \\u0627\\u0644\\u062f\\u0631\\u0648\\u0633 \\u0645\\u0646 \\u0627\\u0644\\u0645\\u0646\\u0647\\u0627\\u062c \\u0627\\u0644\\u0645\\u0635\\u0627\\u062d\\u0628 \\u0648\\u0625\\u0646\\u0645\\u0627 \\u064a\\u0639\\u0637\\u064a \\u062f\\u0631\\u0648\\u0633 \\u0639\\u0634\\u0648\\u0627\\u0626\\u064a\\u0629\",\"recommendations\":\"\\u064a\\u062c\\u0628 \\u062a\\u0646\\u0628\\u064a\\u0647 \\u0627\\u0644\\u0645\\u0639\\u0644\\u0645\",\"status\":\"completed\"}}',NULL,'2026-04-04 14:45:59','2026-04-04 14:45:59');

DROP TABLE IF EXISTS `app_notifications`;
CREATE TABLE `app_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `app_notifications_user_id_read_at_index` (`user_id`,`read_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_notifications` (`id`,`user_id`,`title`,`body`,`type`,`data`,`read_at`,`created_at`,`updated_at`) VALUES (1,5,'زيارة إشرافية جديدة','تم تسجيل زيارة إشرافية بتاريخ 2026-04-04 — المتوسط: 7.7/10','supervisory_visit','{\"type\":\"supervisory_visit\",\"visit_id\":\"1\",\"click_action\":\"FLUTTER_NOTIFICATION_CLICK\"}',NULL,'2026-04-04 14:45:59','2026-04-04 14:45:59');

DROP TABLE IF EXISTS `attendance_records`;
CREATE TABLE `attendance_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` varchar(30) NOT NULL,
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_student_id_date_unique` (`student_id`,`date`),
  KEY `attendance_records_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  KEY `attendance_records_halaqah_id_date_index` (`halaqah_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (1,1,7,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (2,1,1,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (3,1,10,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (4,1,2,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (5,1,8,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (6,1,6,'2026-04-03','present',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (7,1,4,'2026-04-03','present',4,NULL,'2026-04-02 22:03:58','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (8,1,5,'2026-04-03','present',4,NULL,'2026-04-02 22:03:58','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (9,1,3,'2026-04-03','present',4,NULL,'2026-04-02 22:03:58','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (10,1,9,'2026-04-03','present',4,NULL,'2026-04-02 22:03:58','2026-04-02 22:03:58');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (11,2,12,'2026-04-04','unexcused_absence',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (12,2,13,'2026-04-04','present',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `attendance_records` (`id`,`halaqah_id`,`student_id`,`date`,`status`,`recorded_by_user_id`,`notes`,`created_at`,`updated_at`) VALUES (13,2,11,'2026-04-04','excused_absence',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');

DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cache` (`key`,`value`,`expiration`) VALUES ('laravel_cache_356a192b7913b04c54574d18c28d46e6395428ab','i:1;',1775262734);
INSERT INTO `cache` (`key`,`value`,`expiration`) VALUES ('laravel_cache_356a192b7913b04c54574d18c28d46e6395428ab:timer','i:1775262734;',1775262734);
INSERT INTO `cache` (`key`,`value`,`expiration`) VALUES ('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:2;',1775165291);
INSERT INTO `cache` (`key`,`value`,`expiration`) VALUES ('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1775165291;',1775165291);
INSERT INTO `cache` (`key`,`value`,`expiration`) VALUES ('laravel_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:21:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:10:\"view users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:12:\"create users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:10:\"edit users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"delete users\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:12:\"view regions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:14:\"create regions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:12:\"edit regions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:14:\"delete regions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:12:\"view centers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:2;i:1;i:3;i:2;i:4;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:14:\"create centers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:12:\"edit centers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:14:\"delete centers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:13:\"view halaqahs\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:15:\"create halaqahs\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:13:\"edit halaqahs\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:15:\"delete halaqahs\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:21:\"view teacher_profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:2;i:1;i:3;i:2;i:4;i:3;i:5;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:23:\"create teacher_profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:21:\"edit teacher_profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:23:\"delete teacher_profiles\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:2;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:22:\"edit teacher own photo\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:6;}}}s:5:\"roles\";a:5:{i:0;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:5:\"Admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:21:\"EducationalSupervisor\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:16:\"CenterSupervisor\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:8:\"Examiner\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:7:\"Teacher\";s:1:\"c\";s:3:\"web\";}}}',1775350626);

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `centers`;
CREATE TABLE `centers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `region_id` bigint(20) unsigned NOT NULL,
  `admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `centers_region_id_foreign` (`region_id`),
  KEY `centers_admin_user_id_foreign` (`admin_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `centers` (`id`,`name`,`region_id`,`admin_user_id`,`address`,`phone`,`created_at`,`updated_at`) VALUES (1,'مركز تجريبي',1,2,NULL,NULL,'2026-04-02 20:44:42','2026-04-02 20:44:42');
INSERT INTO `centers` (`id`,`name`,`region_id`,`admin_user_id`,`address`,`phone`,`created_at`,`updated_at`) VALUES (2,'مركز المتميزون',2,8,NULL,NULL,'2026-04-04 00:32:35','2026-04-04 14:34:37');

DROP TABLE IF EXISTS `daily_evaluation_reason`;
CREATE TABLE `daily_evaluation_reason` (
  `daily_evaluation_id` bigint(20) unsigned NOT NULL,
  `evaluation_reason_id` bigint(20) unsigned NOT NULL,
  UNIQUE KEY `de_eval_reason_unique` (`daily_evaluation_id`,`evaluation_reason_id`),
  KEY `der_evaluation_reason_id_idx` (`evaluation_reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `daily_evaluations`;
CREATE TABLE `daily_evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `overall` varchar(30) NOT NULL DEFAULT 'none',
  `recorded_by_user_id` bigint(20) unsigned NOT NULL,
  `general_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_evaluations_student_id_date_unique` (`student_id`,`date`),
  KEY `daily_evaluations_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  KEY `daily_evaluations_halaqah_id_date_index` (`halaqah_id`,`date`),
  KEY `daily_evaluations_overall_date_index` (`overall`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (1,1,7,'2026-04-03','good',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (2,1,4,'2026-04-03','needs_improvement',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (3,1,1,'2026-04-03','needs_improvement',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (4,1,10,'2026-04-03','good',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (5,1,2,'2026-04-03','needs_improvement',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (6,1,8,'2026-04-03','needs_improvement',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (7,1,6,'2026-04-03','good',4,NULL,'2026-04-02 21:56:50','2026-04-02 22:03:58');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (8,1,5,'2026-04-03','excellent',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (9,1,3,'2026-04-03','good',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (10,1,9,'2026-04-03','excellent',4,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (11,2,12,'2026-04-04','none',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (12,2,13,'2026-04-04','excellent',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `daily_evaluations` (`id`,`halaqah_id`,`student_id`,`date`,`overall`,`recorded_by_user_id`,`general_note`,`created_at`,`updated_at`) VALUES (13,2,11,'2026-04-04','none',5,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `enrolled_at` date NOT NULL DEFAULT curdate(),
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `left_at` date DEFAULT NULL,
  `leave_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enrollments_student_id_status_index` (`student_id`,`status`),
  KEY `enrollments_halaqah_id_status_index` (`halaqah_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (1,1,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (2,2,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (3,3,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (4,4,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (5,5,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (6,6,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (7,7,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (8,8,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (9,9,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (10,10,1,'2026-04-02','active',NULL,NULL,'2026-04-02 20:44:44','2026-04-02 20:44:44');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (11,13,2,'2026-04-04','active',NULL,NULL,'2026-04-04 00:37:22','2026-04-04 00:37:22');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (12,11,2,'2026-04-04','active',NULL,NULL,'2026-04-04 00:37:31','2026-04-04 00:37:31');
INSERT INTO `enrollments` (`id`,`student_id`,`halaqah_id`,`enrolled_at`,`status`,`left_at`,`leave_reason`,`created_at`,`updated_at`) VALUES (13,12,2,'2026-04-04','active',NULL,NULL,'2026-04-04 00:37:41','2026-04-04 00:37:41');

DROP TABLE IF EXISTS `evaluation_reasons`;
CREATE TABLE `evaluation_reasons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `type` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `evaluation_reasons_key_unique` (`key`),
  KEY `evaluation_reasons_type_is_active_sort_order_index` (`type`,`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (1,'ex_homework','واجب','excellence',1,10,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (2,'ex_adab','أدب','excellence',1,20,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (3,'ex_tarbiyah','تربية','excellence',1,30,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (4,'df_homework','واجب','deficiency',1,10,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (5,'df_memorization','حفظ','deficiency',1,20,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (6,'df_revision','مراجعة','deficiency',1,30,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (7,'df_tajweed','تجويد','deficiency',1,40,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `evaluation_reasons` (`id`,`key`,`label`,`type`,`is_active`,`sort_order`,`created_at`,`updated_at`) VALUES (8,'df_discipline','انضباط','deficiency',1,50,'2026-04-02 20:44:41','2026-04-02 20:44:41');

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `halaqahs`;
CREATE TABLE `halaqahs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` smallint(5) unsigned NOT NULL DEFAULT 20,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `halaqahs_center_id_foreign` (`center_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `halaqahs` (`id`,`name`,`center_id`,`description`,`capacity`,`created_at`,`updated_at`) VALUES (1,'حلقة تجريبية',1,'',20,'2026-04-02 20:44:42','2026-04-02 20:44:42');
INSERT INTO `halaqahs` (`id`,`name`,`center_id`,`description`,`capacity`,`created_at`,`updated_at`) VALUES (2,'منارات الهدى',2,NULL,20,'2026-04-04 00:33:01','2026-04-04 00:33:01');

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `memorization_entries`;
CREATE TABLE `memorization_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `date` date NOT NULL,
  `memorization_from` varchar(255) DEFAULT NULL,
  `memorization_to` varchar(255) DEFAULT NULL,
  `revision_from` varchar(255) DEFAULT NULL,
  `revision_to` varchar(255) DEFAULT NULL,
  `mistakes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `memorization_entries_student_id_date_unique` (`student_id`,`date`),
  KEY `memorization_entries_halaqah_id_date_index` (`halaqah_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (1,1,7,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (2,1,4,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (3,1,1,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (4,1,10,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (5,1,2,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (6,1,8,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (7,1,6,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (8,1,5,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (9,1,3,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (10,1,9,'2026-04-03',NULL,NULL,NULL,NULL,NULL,'2026-04-02 21:56:50','2026-04-02 21:56:50');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (11,2,12,'2026-04-04',NULL,NULL,NULL,NULL,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (12,2,13,'2026-04-04',NULL,NULL,NULL,NULL,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');
INSERT INTO `memorization_entries` (`id`,`halaqah_id`,`student_id`,`date`,`memorization_from`,`memorization_to`,`revision_from`,`revision_to`,`mistakes`,`created_at`,`updated_at`) VALUES (13,2,11,'2026-04-04',NULL,NULL,NULL,NULL,NULL,'2026-04-04 00:40:05','2026-04-04 00:40:05');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (4,'2026_04_01_124309_create_permission_tables',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (5,'2026_04_01_125531_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (6,'2026_04_01_200000_create_regions_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (7,'2026_04_01_200001_create_centers_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (8,'2026_04_01_200002_create_halaqahs_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (9,'2026_04_01_200003_create_teacher_profiles_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (10,'2026_04_01_210000_create_students_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (11,'2026_04_01_210001_create_enrollments_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (12,'2026_04_01_220000_create_attendance_records_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (13,'2026_04_01_220001_create_daily_evaluations_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (14,'2026_04_01_220002_create_evaluation_reasons_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (15,'2026_04_01_220003_create_daily_evaluation_reason_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (16,'2026_04_01_220004_create_memorization_entries_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (17,'2026_04_01_230000_create_tests_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (18,'2026_04_01_230001_create_test_rubrics_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (19,'2026_04_01_230002_create_test_assignments_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (20,'2026_04_01_230003_create_test_results_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (21,'2026_04_01_230004_create_test_result_items_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (22,'2026_04_01_240000_create_supervision_rubrics_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (23,'2026_04_01_240001_create_supervision_rubric_items_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (24,'2026_04_01_240002_create_supervisory_visits_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (25,'2026_04_01_240003_create_supervisory_visit_scores_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (26,'2026_04_01_240004_create_supervisory_visit_attachments_table',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (27,'2026_04_01_250000_add_reporting_indexes',1);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (28,'2026_04_03_100000_add_profile_workflow_to_students_table',2);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (29,'2026_04_03_100001_create_student_profile_submissions_table',2);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (30,'2026_04_04_120000_add_photo_path_to_teacher_profiles_table',3);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (31,'2026_04_05_100000_add_detail_scores_to_test_results_table',4);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (32,'2026_04_05_200000_create_supervisor_field_visits_table',5);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (33,'2026_04_04_130000_create_activity_log_table',6);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (34,'2026_04_04_130100_add_fcm_token_to_users_table',6);
INSERT INTO `migrations` (`id`,`migration`,`batch`) VALUES (35,'2026_04_04_130200_create_app_notifications_table',6);

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (1,'App\\Models\\User',1);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (2,'App\\Models\\User',2);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (3,'App\\Models\\User',3);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (4,'App\\Models\\User',8);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (5,'App\\Models\\User',6);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (5,'App\\Models\\User',7);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (6,'App\\Models\\User',4);
INSERT INTO `model_has_roles` (`role_id`,`model_type`,`model_id`) VALUES (6,'App\\Models\\User',5);

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (1,'view users','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (2,'create users','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (3,'edit users','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (4,'delete users','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (5,'view regions','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (6,'create regions','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (7,'edit regions','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (8,'delete regions','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (9,'view centers','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (10,'create centers','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (11,'edit centers','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (12,'delete centers','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (13,'view halaqahs','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (14,'create halaqahs','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (15,'edit halaqahs','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (16,'delete halaqahs','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (17,'view teacher_profiles','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (18,'create teacher_profiles','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (19,'edit teacher_profiles','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (20,'delete teacher_profiles','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `permissions` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (21,'edit teacher own photo','web','2026-04-04 00:51:09','2026-04-04 00:51:09');

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (1,'App\\Models\\User',4,'teacher-app','d6b4f16d9fe5de82a4e9c914d2dbd7fd35992f78ae5c7d331532d592d7498e64','[\"*\"]','2026-04-02 22:10:57',NULL,'2026-04-02 21:56:05','2026-04-02 22:10:57');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (2,'App\\Models\\User',4,'teacher-app','f98ec9a64c38d59a3b9202f925430b1c9723548f6fc2c453030e0620fb5c1a62','[\"*\"]','2026-04-02 22:19:58',NULL,'2026-04-02 22:19:02','2026-04-02 22:19:58');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (3,'App\\Models\\User',4,'teacher-app','b96a124cf982f716e2417a590d6b74c2131307b6a3db9d9c19a59322ed3a2f59','[\"*\"]','2026-04-02 22:30:56',NULL,'2026-04-02 22:29:07','2026-04-02 22:30:56');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (4,'App\\Models\\User',4,'teacher-app','ef343b900fee794f07d4b922dfa8d5324e8dabd9a08e8a921e079a462d9c3ffc','[\"*\"]','2026-04-02 22:35:37',NULL,'2026-04-02 22:33:51','2026-04-02 22:35:37');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (5,'App\\Models\\User',4,'teacher-app','d459e917435600951ad95b27414971dccce33f1bc076896937102ca7f6e0a6a1','[\"*\"]','2026-04-02 22:54:57',NULL,'2026-04-02 22:52:49','2026-04-02 22:54:57');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (6,'App\\Models\\User',4,'teacher-app','44ccea8702b01ed4bb7565994bf5c80055cb84619c378b59750b3f11af149fac','[\"*\"]','2026-04-02 23:02:33',NULL,'2026-04-02 23:02:11','2026-04-02 23:02:33');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (7,'App\\Models\\User',4,'teacher-app','7f9455ccc219babd38fcdf8ee0a37a50618fdd1bdee28c4e2199ad89f4a70fbb','[\"*\"]','2026-04-02 23:26:09',NULL,'2026-04-02 23:24:10','2026-04-02 23:26:09');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (9,'App\\Models\\User',5,'teacher-app','b0b1075e887ed05537ebbb016796fcc00a5967c85684b384ab58b6c68115eff9','[\"*\"]','2026-04-04 00:42:21',NULL,'2026-04-04 00:26:32','2026-04-04 00:42:21');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (10,'App\\Models\\User',5,'teacher-app','6150f63437ddf9e7e0fc08637464c93f27b97535bf3aec527b6adde153d4488f','[\"*\"]','2026-04-04 00:46:33',NULL,'2026-04-04 00:44:39','2026-04-04 00:46:33');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (11,'App\\Models\\User',5,'teacher-app','83073de039b235d25640ffa9103bcbcbec04736cb21d5a7493234738fb28972d','[\"*\"]','2026-04-04 00:57:26',NULL,'2026-04-04 00:57:05','2026-04-04 00:57:26');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (15,'App\\Models\\User',8,'mobile-app','b4fa6299904f956aed9e25b4eb7e87166b4b2828b055a7d322ca2468510b75ea','[\"*\"]','2026-04-04 01:41:46',NULL,'2026-04-04 01:41:34','2026-04-04 01:41:46');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (16,'App\\Models\\User',5,'mobile-app','5c59163b10bce5e93fc3f27816b78d8166656ad14a77b8f4cbb242ceb8000a70','[\"*\"]','2026-04-04 01:52:29',NULL,'2026-04-04 01:52:27','2026-04-04 01:52:29');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (17,'App\\Models\\User',7,'mobile-app','562a94044f143ca1b4dd3058f95f934f56daa5c90d0bfbcc951a6c4f8669a07c','[\"*\"]','2026-04-04 02:10:26',NULL,'2026-04-04 02:08:19','2026-04-04 02:10:26');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (18,'App\\Models\\User',7,'mobile-app','0410a87c9fe35c5848221e9af492266755fff380641ca6bb5e262f8c000dc407','[\"*\"]','2026-04-04 02:28:34',NULL,'2026-04-04 02:11:30','2026-04-04 02:28:34');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (19,'App\\Models\\User',7,'mobile-app','f3cd70547c9196ae2ca26494759200c30f096b09d06ea9b382edca0b14c44ba4','[\"*\"]','2026-04-04 02:32:27',NULL,'2026-04-04 02:31:43','2026-04-04 02:32:27');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (20,'App\\Models\\User',8,'mobile-app','371b37914ef95c32b01e5212651005a6e48d7c0b8068ca97f64d1c0f368bca98','[\"*\"]','2026-04-04 02:49:28',NULL,'2026-04-04 02:47:35','2026-04-04 02:49:28');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (21,'App\\Models\\User',8,'mobile-app','e41f2d47bbb97c53c232a6b83c22a336d393b64d981a70ecd664498dfce0f946','[\"*\"]','2026-04-04 02:54:43',NULL,'2026-04-04 02:54:36','2026-04-04 02:54:43');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (23,'App\\Models\\User',8,'mobile-app','cbfc543d51652bc06b71f39183b51a2f6aa53fd833f42c3d2ab930887f1bb154','[\"*\"]','2026-04-04 14:47:59',NULL,'2026-04-04 14:42:23','2026-04-04 14:47:59');
INSERT INTO `personal_access_tokens` (`id`,`tokenable_type`,`tokenable_id`,`name`,`token`,`abilities`,`last_used_at`,`expires_at`,`created_at`,`updated_at`) VALUES (24,'App\\Models\\User',8,'mobile-app','ddf8f7e5c7efb76d7efb1db9ab7ff34a505278e98b0b450e2348755df65c2bb8','[\"*\"]','2026-04-04 14:59:34',NULL,'2026-04-04 14:53:07','2026-04-04 14:59:34');

DROP TABLE IF EXISTS `regions`;
CREATE TABLE `regions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `regions` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES (1,'منطقة تجريبية','','2026-04-02 20:44:42','2026-04-02 20:44:42');
INSERT INTO `regions` (`id`,`name`,`description`,`created_at`,`updated_at`) VALUES (2,'دمشق',NULL,'2026-04-04 00:32:06','2026-04-04 00:32:06');

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (1,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (2,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (3,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (4,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (5,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (6,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (7,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (8,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (9,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (9,3);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (9,4);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (10,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (11,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (12,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (13,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (13,3);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (13,4);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (13,5);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (14,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (15,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (16,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (17,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (17,3);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (17,4);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (17,5);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (18,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (19,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (20,2);
INSERT INTO `role_has_permissions` (`permission_id`,`role_id`) VALUES (21,6);

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (1,'SuperAdmin','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (2,'Admin','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (3,'EducationalSupervisor','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (4,'CenterSupervisor','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (5,'Examiner','web','2026-04-02 20:44:40','2026-04-02 20:44:40');
INSERT INTO `roles` (`id`,`name`,`guard_name`,`created_at`,`updated_at`) VALUES (6,'Teacher','web','2026-04-02 20:44:40','2026-04-02 20:44:40');

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('19ap4l99qmV4RkIxmkfZu1LozkLaTzk1nOCiacT9',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWVpuWlB1Y2R1WVo5dWF0WDBWOHh5aFp5YXVIQkJVQWEwaTFCQm5DSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9sb2dpbiI7czo1OiJyb3V0ZSI7czoyNToiZmlsYW1lbnQuYWRtaW4uYXV0aC5sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1775267261);
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('54hmQepwq1lLlgtfnXLqfTGIZ1to4ktzi4QvttVa',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiTjgwN1FOU1R2aGRPbDZ1Rk1hbWZJSHlyTURMVUdIR0JGY2cyandoUiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMmExZTMxY2RiMTU2NDQyZWE2NDc0ZDFkODFjYjg5YWU0MDg3MTZkODdlYWE0ODU1ZDMzODgxYjkzMmQ0NGE3MCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi91c2Vycy84L2VkaXQiO3M6NToicm91dGUiO3M6MzU6ImZpbGFtZW50LmFkbWluLnJlc291cmNlcy51c2Vycy5lZGl0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo4OiJmaWxhbWVudCI7YTowOnt9fQ==',1775313744);
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('Ia9lcAYfis6OxkDV0Ffh1QSSIAEMdEAhMnJ8cPB7',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTo3OntzOjY6Il90b2tlbiI7czo0MDoidVM3aXcxN0NiRWdobFI1cnNGd1JKaENDWG5mQ1owdGZKSEhyZ1RhSiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMmExZTMxY2RiMTU2NDQyZWE2NDc0ZDFkODFjYjg5YWU0MDg3MTZkODdlYWE0ODU1ZDMzODgxYjkzMmQ0NGE3MCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDQ6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi90ZXN0LWFzc2lnbm1lbnRzIjtzOjU6InJvdXRlIjtzOjQ3OiJmaWxhbWVudC5hZG1pbi5yZXNvdXJjZXMudGVzdC1hc3NpZ25tZW50cy5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6ODoiZmlsYW1lbnQiO2E6MDp7fXM6MjI6IkFkbWluRGFzaGJvYXJkX2ZpbHRlcnMiO2E6NDp7czoxMDoic2NvcGVfdHlwZSI7czozOiJhbGwiO3M6ODoic2NvcGVfaWQiO047czo5OiJkYXRlX2Zyb20iO3M6MTA6IjIwMjYtMDQtMDEiO3M6NzoiZGF0ZV90byI7czoxMDoiMjAyNi0wNC0wNCI7fX0=',1775269712);
INSERT INTO `sessions` (`id`,`user_id`,`ip_address`,`user_agent`,`payload`,`last_activity`) VALUES ('JIyHewLIhFxqujwVtQfaVzPdyq9P2iMiYoDTdUsa',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiQmxGWDZsd1BxSFVweFJwRk9vMUFyQ3BLMGNZY21nVjZHYzdBbGo2TiI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2NDoiMmExZTMxY2RiMTU2NDQyZWE2NDc0ZDFkODFjYjg5YWU0MDg3MTZkODdlYWE0ODU1ZDMzODgxYjkzMmQ0NGE3MCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi9oYWxhcWFocyI7czo1OiJyb3V0ZSI7czozOToiZmlsYW1lbnQuYWRtaW4ucmVzb3VyY2VzLmhhbGFxYWhzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1775312623);

DROP TABLE IF EXISTS `student_profile_submissions`;
CREATE TABLE `student_profile_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint(20) unsigned NOT NULL,
  `teacher_user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(255) DEFAULT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `reviewed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewer_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_profile_submissions_teacher_user_id_foreign` (`teacher_user_id`),
  KEY `student_profile_submissions_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `student_profile_submissions_student_id_status_index` (`student_id`,`status`),
  KEY `student_profile_submissions_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(255) DEFAULT NULL,
  `national_id` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `profile_locked` tinyint(1) NOT NULL DEFAULT 0,
  `teacher_may_edit_profile` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `students_full_name_guardian_phone_index` (`full_name`,`guardian_phone`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (1,'Dr. Sigrid Brown Sr.','female','1999-07-28',NULL,'+1 (539) 867-6283',NULL,'Quia possimus aut sed.',NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (2,'Marlene Olson','male','2011-11-03','Monroe Green','936-709-8947',4034817117,NULL,NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (3,'Pinkie Kunde','male','2007-06-27','Lonie Hand',NULL,NULL,NULL,NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (4,'Beth McGlynn','female','1987-03-19','Ms. Anya Feeney','551.493.1980',NULL,NULL,NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (5,'Ms. Zoe Boyer III','male',NULL,'Foster Schaden','479-780-6839',6951065994,'Vel eveniet quia ipsa sed cum earum quidem unde.',NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (6,'Ms. Vilma Jones','male','2019-05-11',NULL,NULL,2041901466,NULL,NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (7,'أحمد درويش','male','2004-01-22','ضرار درويش',+963998663459,0100807812,'Ducimus a eaque eveniet esse.','student-photos/7/2e3c5409-a5e0-46e8-b13b-e382145a6b40.jpg',1,'2026-04-02 20:44:44','2026-04-02 23:02:28',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (8,'Mrs. Loren Cormier Jr.','male','1989-02-27','Citlalli Von','667-774-5138',1433199808,'Non et placeat ut inventore minus reprehenderit quos.',NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (9,'Vladimir Lowe','male','1996-02-09','Dillan Williamson',NULL,2360825190,NULL,NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (10,'Janice Smith','female',NULL,'Mrs. Luisa Huels III',NULL,1894699594,'Sed qui corrupti ducimus.',NULL,1,'2026-04-02 20:44:44','2026-04-02 20:44:44',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (11,'محمود الأحمد','male','2004-01-01',NULL,NULL,NULL,NULL,'student-photos/01KNAY9P8TCTYW04SE5ED7PNCK.jpg',1,'2026-04-04 00:29:14','2026-04-04 00:29:14',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (12,'أحمد حافض','male','2004-04-01',NULL,NULL,NULL,NULL,'student-photos/01KNAYBJD4N9RA2850BZ3EERSX.jpg',1,'2026-04-04 00:30:15','2026-04-04 00:30:15',0,1);
INSERT INTO `students` (`id`,`full_name`,`gender`,`birth_date`,`guardian_name`,`guardian_phone`,`national_id`,`notes`,`photo_path`,`is_active`,`created_at`,`updated_at`,`profile_locked`,`teacher_may_edit_profile`) VALUES (13,'جميل عوض','male','2004-04-02',NULL,NULL,NULL,NULL,'student-photos/01KNAYDE7ZZRJP1193T21TQNC9.jpg',1,'2026-04-04 00:31:17','2026-04-04 00:31:17',0,1);

DROP TABLE IF EXISTS `supervision_rubric_items`;
CREATE TABLE `supervision_rubric_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supervision_rubric_id` bigint(20) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `max_score` int(11) NOT NULL DEFAULT 5,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supervision_rubric_items_supervision_rubric_id_key_unique` (`supervision_rubric_id`,`key`),
  KEY `supervision_rubric_items_supervision_rubric_id_sort_order_index` (`supervision_rubric_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `supervision_rubrics`;
CREATE TABLE `supervision_rubrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervision_rubrics_created_by_user_id_foreign` (`created_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `supervisor_field_visits`;
CREATE TABLE `supervisor_field_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supervisor_user_id` bigint(20) unsigned NOT NULL,
  `teacher_user_id` bigint(20) unsigned NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `visit_date` date NOT NULL,
  `teaching_skill_score` tinyint(3) unsigned NOT NULL,
  `plan_adherence_score` tinyint(3) unsigned NOT NULL,
  `student_engagement_score` tinyint(3) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervisor_field_visits_supervisor_user_id_visit_date_index` (`supervisor_user_id`,`visit_date`),
  KEY `supervisor_field_visits_teacher_user_id_visit_date_index` (`teacher_user_id`,`visit_date`),
  KEY `supervisor_field_visits_center_id_visit_date_index` (`center_id`,`visit_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supervisor_field_visits` (`id`,`supervisor_user_id`,`teacher_user_id`,`center_id`,`visit_date`,`teaching_skill_score`,`plan_adherence_score`,`student_engagement_score`,`notes`,`recommendations`,`status`,`created_at`,`updated_at`) VALUES (1,8,5,2,'2026-04-04',7,8,8,'المعلم لا يعطي الدروس من المنهاج المصاحب وإنما يعطي دروس عشوائية','يجب تنبيه المعلم','completed','2026-04-04 14:45:59','2026-04-04 14:45:59');

DROP TABLE IF EXISTS `supervisory_visit_attachments`;
CREATE TABLE `supervisory_visit_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supervisory_visit_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size_bytes` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervisory_visit_attachments_supervisory_visit_id_foreign` (`supervisory_visit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `supervisory_visit_scores`;
CREATE TABLE `supervisory_visit_scores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supervisory_visit_id` bigint(20) unsigned NOT NULL,
  `supervision_rubric_item_id` bigint(20) unsigned NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sv_score_item_unique` (`supervisory_visit_id`,`supervision_rubric_item_id`),
  KEY `svs_item_id_idx` (`supervision_rubric_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `supervisory_visits`;
CREATE TABLE `supervisory_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supervision_rubric_id` bigint(20) unsigned NOT NULL,
  `supervisor_user_id` bigint(20) unsigned NOT NULL,
  `center_id` bigint(20) unsigned NOT NULL,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `teacher_user_id` bigint(20) unsigned NOT NULL,
  `visited_at` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `overall_level` enum('excellent','good','acceptable','weak') DEFAULT NULL,
  `overall_score` decimal(7,2) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `is_finalized` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supervisory_visits_supervision_rubric_id_foreign` (`supervision_rubric_id`),
  KEY `supervisory_visits_halaqah_id_foreign` (`halaqah_id`),
  KEY `supervisory_visits_supervisor_user_id_visited_at_index` (`supervisor_user_id`,`visited_at`),
  KEY `supervisory_visits_center_id_visited_at_index` (`center_id`,`visited_at`),
  KEY `supervisory_visits_teacher_user_id_visited_at_index` (`teacher_user_id`,`visited_at`),
  CONSTRAINT `supervisory_visits_supervision_rubric_id_foreign` FOREIGN KEY (`supervision_rubric_id`) REFERENCES `supervision_rubrics` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `teacher_profiles`;
CREATE TABLE `teacher_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `halaqah_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `qualification` varchar(255) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_profiles_user_id_unique` (`user_id`),
  KEY `teacher_profiles_halaqah_id_foreign` (`halaqah_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `teacher_profiles` (`id`,`user_id`,`halaqah_id`,`phone`,`qualification`,`hire_date`,`notes`,`photo_path`,`created_at`,`updated_at`) VALUES (1,4,1,NULL,NULL,NULL,NULL,NULL,'2026-04-02 20:44:42','2026-04-02 20:44:42');
INSERT INTO `teacher_profiles` (`id`,`user_id`,`halaqah_id`,`phone`,`qualification`,`hire_date`,`notes`,`photo_path`,`created_at`,`updated_at`) VALUES (2,5,2,NULL,NULL,'2026-01-11',NULL,'teacher-photos/5/4de1b5e4-38eb-4ce8-92d7-717868cf9702.jpg','2026-04-04 00:33:39','2026-04-04 00:57:15');

DROP TABLE IF EXISTS `test_assignments`;
CREATE TABLE `test_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `student_id` bigint(20) unsigned NOT NULL,
  `halaqah_id` bigint(20) unsigned NOT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assigned_by_user_id` bigint(20) unsigned NOT NULL,
  `status` enum('assigned','completed','absent_excused','absent_unexcused') NOT NULL DEFAULT 'assigned',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_assignments_test_id_student_id_unique` (`test_id`,`student_id`),
  KEY `test_assignments_student_id_foreign` (`student_id`),
  KEY `test_assignments_assigned_by_user_id_foreign` (`assigned_by_user_id`),
  KEY `test_assignments_test_id_status_index` (`test_id`,`status`),
  KEY `test_assignments_halaqah_id_index` (`halaqah_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `test_assignments` (`id`,`test_id`,`student_id`,`halaqah_id`,`assigned_at`,`assigned_by_user_id`,`status`,`created_at`,`updated_at`) VALUES (1,1,12,2,'2026-04-04 02:28:01',1,'assigned','2026-04-04 02:28:23','2026-04-04 02:28:23');
INSERT INTO `test_assignments` (`id`,`test_id`,`student_id`,`halaqah_id`,`assigned_at`,`assigned_by_user_id`,`status`,`created_at`,`updated_at`) VALUES (2,1,13,2,'2026-04-04 02:28:01',1,'assigned','2026-04-04 02:28:23','2026-04-04 02:28:23');
INSERT INTO `test_assignments` (`id`,`test_id`,`student_id`,`halaqah_id`,`assigned_at`,`assigned_by_user_id`,`status`,`created_at`,`updated_at`) VALUES (3,1,11,2,'2026-04-04 02:28:01',1,'assigned','2026-04-04 02:28:23','2026-04-04 02:28:23');

DROP TABLE IF EXISTS `test_result_items`;
CREATE TABLE `test_result_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_result_id` bigint(20) unsigned NOT NULL,
  `test_rubric_id` bigint(20) unsigned NOT NULL,
  `score` decimal(8,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_result_items_test_result_id_test_rubric_id_unique` (`test_result_id`,`test_rubric_id`),
  KEY `test_result_items_test_rubric_id_foreign` (`test_rubric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `test_results`;
CREATE TABLE `test_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_assignment_id` bigint(20) unsigned NOT NULL,
  `examiner_user_id` bigint(20) unsigned NOT NULL,
  `memorization_score` smallint(5) unsigned DEFAULT NULL,
  `tajweed_score` smallint(5) unsigned DEFAULT NULL,
  `review_score` smallint(5) unsigned DEFAULT NULL,
  `tested_surah` varchar(150) DEFAULT NULL,
  `total_score` decimal(6,2) DEFAULT NULL,
  `level` enum('excellent','good','acceptable','weak') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `tested_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `test_results_test_assignment_id_unique` (`test_assignment_id`),
  KEY `test_results_examiner_user_id_level_index` (`examiner_user_id`,`level`),
  KEY `tr_examiner_tested_at_idx` (`examiner_user_id`,`tested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `test_rubrics`;
CREATE TABLE `test_rubrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `test_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `max_score` int(10) unsigned NOT NULL DEFAULT 0,
  `weight` decimal(6,3) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `test_rubrics_test_id_sort_order_index` (`test_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('regular','sampling') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `scope_halaqah_id` bigint(20) unsigned DEFAULT NULL,
  `scope_center_id` bigint(20) unsigned DEFAULT NULL,
  `scope_region_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `sampling_strategy` varchar(30) DEFAULT NULL,
  `sampling_count` int(11) DEFAULT NULL,
  `sampling_percent` decimal(5,2) DEFAULT NULL,
  `sampling_seed` int(11) DEFAULT NULL,
  `sampling_active_only` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tests_scope_halaqah_id_foreign` (`scope_halaqah_id`),
  KEY `tests_scope_region_id_foreign` (`scope_region_id`),
  KEY `tests_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `tests_type_scheduled_at_index` (`type`,`scheduled_at`),
  KEY `tests_scope_center_id_scope_halaqah_id_index` (`scope_center_id`,`scope_halaqah_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tests` (`id`,`type`,`title`,`description`,`scope_halaqah_id`,`scope_center_id`,`scope_region_id`,`scheduled_at`,`created_by_user_id`,`is_published`,`sampling_strategy`,`sampling_count`,`sampling_percent`,`sampling_seed`,`sampling_active_only`,`created_at`,`updated_at`) VALUES (1,'sampling','اختبار عينات مركز منارات الهدى',NULL,2,2,2,'2026-04-04 05:13:18',1,1,'random',NULL,NULL,NULL,1,'2026-04-04 02:22:00','2026-04-04 02:22:00');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `fcm_token` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (1,'Super Admin','superadmin@gmail.com',NULL,'$2y$12$sUB5x9jrbauawS9drf0d1OYxehHnvr3MF8jMPn1srNm9Z5ZlckIGy',1,'uSs6uZ9kVXtUsj8a0E7LeJrCxDxhovYjIrryDWXXhORHeDajXhPD1KBDyaBN',NULL,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (2,'Admin','admin@gmail.com',NULL,'$2y$12$MZ/FMTssJJemiLf9chNKr.jeS70TdbjNsa6NNL438r5WpJYvSYr82',1,'CrHTCeGbjqJ13a94qIs7ofip4QgZJ3ZEAk1tvpBG1ShA8bWyKtHDSglkNSzW',NULL,'2026-04-02 20:44:41','2026-04-04 14:28:47');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (3,'Educational Supervisor','supervisor@gmail.com',NULL,'$2y$12$D49lr9.yKxyxu00nHp4NB.Ip4I3UmVyIqurklNH7WNH0ILcmB1HHC',1,NULL,NULL,'2026-04-02 20:44:41','2026-04-02 20:44:41');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (4,'Ahmad Darwesh','teacher@halqati.local',NULL,'$2y$12$XhirDPFKwI09yO/zum8DreohbCrZj9MF1pUBKGJPMO4thtscpAFDW',1,NULL,NULL,'2026-04-02 20:44:42','2026-04-02 20:44:42');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (5,'أحمد درويش','ahmad@gmail.com',NULL,'$2y$12$TPraerQoq7D1Mi6Mh3MTa.YNQYo/gHH7VKyuih4ujE/QndM/WVEea',1,NULL,NULL,'2026-04-04 00:24:37','2026-04-04 00:24:37');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (6,'مختبر','m@gmail.com',NULL,'$2y$12$UjecRB/hH81HVlYFtDJsW.uQFhnhOKvE0k8wwe5yGn/.Z0dFoYbyK',1,NULL,NULL,'2026-04-04 01:36:15','2026-04-04 01:36:15');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (7,'مختبر ','m1@gmail.com',NULL,'$2y$12$mmn4ceeZMbdHmqu/lLBOcejQ5BHiFEIvrMjD8YS4tldLvlAbdEqpm',1,NULL,NULL,'2026-04-04 01:38:31','2026-04-04 01:38:31');
INSERT INTO `users` (`id`,`name`,`email`,`email_verified_at`,`password`,`is_active`,`remember_token`,`fcm_token`,`created_at`,`updated_at`) VALUES (8,'مشرف مركز','mosh@gmail.com',NULL,'$2y$12$ZDxjgcavMarLDvJoCZXlseDf9ZX8PPLd7vDjKMDMzeeth7Pn5OWlu',1,NULL,NULL,'2026-04-04 01:41:14','2026-04-04 01:41:14');

