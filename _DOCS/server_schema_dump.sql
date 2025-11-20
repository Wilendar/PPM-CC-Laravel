/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: host379076_ppm
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('system','security','integration','user') NOT NULL DEFAULT 'system',
  `priority` enum('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  `channel` enum('web','email','both') NOT NULL DEFAULT 'web',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `is_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint(20) unsigned DEFAULT NULL,
  `related_type` varchar(255) DEFAULT NULL,
  `related_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recipients`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_notifications_acknowledged_by_foreign` (`acknowledged_by`),
  KEY `admin_notifications_created_by_foreign` (`created_by`),
  KEY `admin_notifications_type_priority_index` (`type`,`priority`),
  KEY `admin_notifications_is_read_created_at_index` (`is_read`,`created_at`),
  KEY `admin_notifications_related_type_related_id_index` (`related_type`,`related_id`),
  CONSTRAINT `admin_notifications_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`),
  CONSTRAINT `admin_notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_themes`
--

DROP TABLE IF EXISTS `admin_themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `theme_name` varchar(100) NOT NULL,
  `primary_color` varchar(7) NOT NULL DEFAULT '#3b82f6',
  `secondary_color` varchar(7) NOT NULL DEFAULT '#64748b',
  `accent_color` varchar(7) NOT NULL DEFAULT '#10b981',
  `layout_density` enum('compact','normal','spacious') NOT NULL DEFAULT 'normal',
  `sidebar_position` enum('left','right') NOT NULL DEFAULT 'left',
  `header_style` enum('fixed','static','floating') NOT NULL DEFAULT 'fixed',
  `custom_css` text DEFAULT NULL,
  `company_logo` varchar(255) DEFAULT NULL,
  `company_name` varchar(100) NOT NULL DEFAULT 'PPM Admin',
  `company_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`company_colors`)),
  `widget_layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`widget_layout`)),
  `dashboard_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dashboard_settings`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `settings_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings_json`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_theme_per_user` (`user_id`,`is_active`),
  KEY `admin_themes_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `admin_themes_is_default_index` (`is_default`),
  KEY `admin_themes_theme_name_index` (`theme_name`),
  CONSTRAINT `admin_themes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_usage_logs`
--

DROP TABLE IF EXISTS `api_usage_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `ip_address` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `api_key_id` varchar(255) DEFAULT NULL,
  `response_code` int(11) NOT NULL,
  `response_time_ms` int(11) NOT NULL,
  `response_size_bytes` int(11) DEFAULT NULL,
  `rate_limit_remaining` int(11) DEFAULT NULL,
  `rate_limited` tinyint(1) NOT NULL DEFAULT 0,
  `request_params` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_params`)),
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `error_message` text DEFAULT NULL,
  `suspicious` tinyint(1) NOT NULL DEFAULT 0,
  `security_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_usage_logs_endpoint_requested_at_index` (`endpoint`,`requested_at`),
  KEY `api_usage_logs_user_id_requested_at_index` (`user_id`,`requested_at`),
  KEY `api_usage_logs_ip_address_requested_at_index` (`ip_address`,`requested_at`),
  KEY `api_usage_logs_response_code_requested_at_index` (`response_code`,`requested_at`),
  KEY `api_usage_logs_suspicious_requested_at_index` (`suspicious`,`requested_at`),
  CONSTRAINT `api_usage_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attribute_types`
--

DROP TABLE IF EXISTS `attribute_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `display_type` enum('dropdown','radio','color','button') NOT NULL DEFAULT 'dropdown',
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attribute_types_code_unique` (`code`),
  KEY `idx_attr_type_code` (`code`),
  KEY `idx_attr_type_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attribute_values`
--

DROP TABLE IF EXISTS `attribute_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attribute_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_type_id` bigint(20) unsigned NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_code_per_type` (`attribute_type_id`,`code`),
  KEY `idx_attr_value_type` (`attribute_type_id`),
  KEY `idx_attr_value_active` (`is_active`),
  KEY `idx_attr_value_position` (`position`),
  CONSTRAINT `attribute_values_attribute_type_id_foreign` FOREIGN KEY (`attribute_type_id`) REFERENCES `attribute_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `auditable_type` varchar(100) NOT NULL,
  `auditable_id` bigint(20) unsigned NOT NULL,
  `event` varchar(50) NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `source` enum('web','api','import','sync') NOT NULL DEFAULT 'web',
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_polymorphic` (`auditable_type`,`auditable_id`),
  KEY `idx_audit_user_time` (`user_id`,`created_at`),
  KEY `idx_audit_event_time` (`event`,`created_at`),
  KEY `idx_audit_time_source` (`created_at`,`source`),
  KEY `idx_audit_created_at` (`created_at`),
  KEY `audit_logs_auditable_type_index` (`auditable_type`),
  KEY `audit_logs_auditable_id_index` (`auditable_id`),
  KEY `audit_logs_event_index` (`event`),
  KEY `audit_logs_source_index` (`source`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='Complete audit trail system for tracking all changes in PPM application';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `backup_jobs`
--

DROP TABLE IF EXISTS `backup_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('database','files','full') NOT NULL DEFAULT 'database',
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `size_bytes` bigint(20) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `backup_jobs_status_index` (`status`),
  KEY `backup_jobs_type_index` (`type`),
  KEY `backup_jobs_status_created_at_index` (`status`,`created_at`),
  KEY `backup_jobs_created_by_index` (`created_by`),
  CONSTRAINT `backup_jobs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(300) NOT NULL,
  `slug` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `level` tinyint(4) NOT NULL DEFAULT 0,
  `path` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `icon` varchar(200) DEFAULT NULL,
  `icon_path` varchar(500) DEFAULT NULL,
  `banner_path` varchar(500) DEFAULT NULL,
  `visual_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visual_settings`)),
  `visibility_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`visibility_settings`)),
  `default_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_values`)),
  `meta_title` varchar(300) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `og_title` varchar(300) DEFAULT NULL,
  `og_description` varchar(300) DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_level_sort_order_index` (`level`,`sort_order`),
  KEY `categories_path_index` (`path`),
  KEY `categories_is_active_level_index` (`is_active`,`level`),
  KEY `categories_slug_index` (`slug`),
  KEY `categories_created_at_index` (`created_at`),
  KEY `categories_is_active_index` (`is_active`),
  KEY `categories_parent_id_is_active_sort_order_index` (`parent_id`,`is_active`,`sort_order`),
  KEY `categories_path_is_active_index` (`path`,`is_active`),
  KEY `categories_level_is_active_sort_order_index` (`level`,`is_active`,`sort_order`),
  KEY `categories_is_featured_index` (`is_featured`),
  KEY `categories_active_featured_index` (`is_active`,`is_featured`),
  FULLTEXT KEY `ft_categories` (`name`,`description`),
  FULLTEXT KEY `categories_content_fulltext` (`name`,`description`,`short_description`,`meta_keywords`),
  CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category_preview`
--

DROP TABLE IF EXISTS `category_preview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_preview` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` char(36) NOT NULL COMMENT 'UUID linking to job_progress',
  `shop_id` bigint(20) unsigned NOT NULL,
  `category_tree_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Hierarchical category tree structure' CHECK (json_valid(`category_tree_json`)),
  `total_categories` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Total number of categories in tree',
  `user_selection_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'User-selected category IDs after preview' CHECK (json_valid(`user_selection_json`)),
  `import_context_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Original import context (mode, category_id, product_ids)' CHECK (json_valid(`import_context_json`)),
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending' COMMENT 'Preview approval status',
  `expires_at` timestamp NOT NULL COMMENT 'Auto-expiration timestamp (cleanup after 1h)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_shop` (`job_id`,`shop_id`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  KEY `category_preview_job_id_index` (`job_id`),
  KEY `category_preview_status_index` (`status`),
  KEY `category_preview_expires_at_index` (`expires_at`),
  CONSTRAINT `category_preview_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Temporary category preview storage dla bulk import workflow';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compatibility_attributes`
--

DROP TABLE IF EXISTS `compatibility_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compatibility_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `color` varchar(7) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_auto_generated` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compatibility_attributes_code_unique` (`code`),
  KEY `idx_compat_attr_code` (`code`),
  KEY `idx_compat_attr_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `compatibility_sources`
--

DROP TABLE IF EXISTS `compatibility_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compatibility_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `trust_level` enum('low','medium','high','verified') NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compatibility_sources_code_unique` (`code`),
  KEY `idx_compat_source_code` (`code`),
  KEY `idx_compat_source_trust` (`trust_level`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `erp_connections`
--

DROP TABLE IF EXISTS `erp_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `erp_connections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `erp_type` enum('baselinker','subiekt_gt','dynamics','insert','custom') NOT NULL COMMENT 'Typ systemu ERP',
  `instance_name` varchar(200) NOT NULL COMMENT 'Nazwa instancji (dla multi-instance)',
  `description` varchar(1000) DEFAULT NULL COMMENT 'Opis połączenia ERP',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy połączenie jest aktywne',
  `priority` int(11) NOT NULL DEFAULT 1 COMMENT 'Priorytet synchronizacji (1=najwyższy)',
  `connection_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Konfiguracja połączenia (encrypted)' CHECK (json_valid(`connection_config`)),
  `auth_status` enum('authenticated','expired','failed','pending') NOT NULL DEFAULT 'pending' COMMENT 'Status uwierzytelnienia',
  `auth_expires_at` timestamp NULL DEFAULT NULL COMMENT 'Wygaśnięcie uwierzytelnienia',
  `last_auth_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnie uwierzytelnienie',
  `connection_status` enum('connected','disconnected','error','maintenance','rate_limited') NOT NULL DEFAULT 'disconnected' COMMENT 'Status połączenia',
  `last_health_check` timestamp NULL DEFAULT NULL COMMENT 'Ostatnie sprawdzenie zdrowia',
  `last_response_time` decimal(8,3) DEFAULT NULL COMMENT 'Czas odpowiedzi (ms)',
  `consecutive_failures` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba niepowodzeń z rzędu',
  `last_error_message` text DEFAULT NULL COMMENT 'Ostatni błąd połączenia',
  `rate_limit_per_minute` int(11) DEFAULT NULL COMMENT 'Limit zapytań per minuta',
  `current_api_usage` int(11) NOT NULL DEFAULT 0 COMMENT 'Aktualne wykorzystanie API',
  `rate_limit_reset_at` timestamp NULL DEFAULT NULL COMMENT 'Reset limitu API',
  `sync_mode` enum('bidirectional','push_only','pull_only','disabled') NOT NULL DEFAULT 'bidirectional' COMMENT 'Tryb synchronizacji',
  `sync_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ustawienia synchronizacji' CHECK (json_valid(`sync_settings`)),
  `auto_sync_products` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync produktów',
  `auto_sync_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync stanów',
  `auto_sync_prices` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync cen',
  `auto_sync_orders` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Auto sync zamówień',
  `field_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie pól między systemami' CHECK (json_valid(`field_mappings`)),
  `transformation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Reguły transformacji danych' CHECK (json_valid(`transformation_rules`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Reguły walidacji danych' CHECK (json_valid(`validation_rules`)),
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnia synchronizacja',
  `next_scheduled_sync` timestamp NULL DEFAULT NULL COMMENT 'Następna zaplanowana synchronizacja',
  `sync_success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba udanych synchronizacji',
  `sync_error_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba błędów synchronizacji',
  `records_synced_total` int(11) NOT NULL DEFAULT 0 COMMENT 'Łączna liczba zsynchronizowanych rekordów',
  `avg_sync_time` decimal(10,3) DEFAULT NULL COMMENT 'Średni czas synchronizacji (s)',
  `avg_response_time` decimal(8,3) DEFAULT NULL COMMENT 'Średni czas odpowiedzi API',
  `data_volume_mb` int(11) NOT NULL DEFAULT 0 COMMENT 'Wolumen przesłanych danych (MB)',
  `max_retry_attempts` int(11) NOT NULL DEFAULT 3 COMMENT 'Maksymalna liczba prób ponawiania',
  `retry_delay_seconds` int(11) NOT NULL DEFAULT 60 COMMENT 'Opóźnienie między próbami',
  `auto_disable_on_errors` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Auto wyłączenie przy błędach',
  `error_threshold` int(11) NOT NULL DEFAULT 10 COMMENT 'Próg błędów do auto wyłączenia',
  `webhook_url` varchar(500) DEFAULT NULL COMMENT 'URL webhooka dla real-time updates',
  `webhook_secret` varchar(200) DEFAULT NULL COMMENT 'Secret dla weryfikacji webhook',
  `webhook_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy webhook jest włączony',
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ustawienia powiadomień' CHECK (json_valid(`notification_settings`)),
  `notify_on_errors` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Powiadomienia o błędach',
  `notify_on_sync_complete` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Powiadomienia po sync',
  `notify_on_auth_expire` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Powiadomienia o wygaśnięciu auth',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_erp_instance` (`erp_type`,`instance_name`),
  KEY `idx_erp_type` (`erp_type`),
  KEY `idx_erp_active` (`is_active`),
  KEY `idx_erp_connection_status` (`connection_status`),
  KEY `idx_erp_auth_status` (`auth_status`),
  KEY `idx_erp_priority` (`priority`),
  KEY `idx_erp_last_sync` (`last_sync_at`),
  KEY `idx_erp_scheduled_sync` (`next_scheduled_sync`),
  KEY `idx_erp_failures` (`consecutive_failures`),
  KEY `idx_erp_auth_expires` (`auth_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feature_templates`
--

DROP TABLE IF EXISTS `feature_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`features`)),
  `is_predefined` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template_predefined` (`is_predefined`),
  KEY `idx_template_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feature_types`
--

DROP TABLE IF EXISTS `feature_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `value_type` enum('text','number','bool','select') NOT NULL DEFAULT 'text',
  `group` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_types_code_unique` (`code`),
  KEY `idx_feature_type_code` (`code`),
  KEY `idx_feature_type_active` (`is_active`),
  KEY `idx_feature_group` (`group`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `feature_values`
--

DROP TABLE IF EXISTS `feature_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `feature_type_id` bigint(20) unsigned NOT NULL,
  `value` varchar(255) NOT NULL,
  `display_value` varchar(255) DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_feature_value_type` (`feature_type_id`,`value`),
  KEY `idx_feature_value_position` (`position`),
  CONSTRAINT `feature_values_feature_type_id_foreign` FOREIGN KEY (`feature_type_id`) REFERENCES `feature_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uploadable_type` varchar(100) NOT NULL COMMENT 'Container, Order, Product, User',
  `uploadable_id` bigint(20) unsigned NOT NULL COMMENT 'ID powiązanego obiektu',
  `file_name` varchar(300) NOT NULL COMMENT 'Nazwa pliku w storage',
  `original_name` varchar(300) NOT NULL COMMENT 'Oryginalna nazwa uploadowanego pliku',
  `file_path` varchar(500) NOT NULL COMMENT 'Ścieżka do pliku w storage',
  `file_size` bigint(20) unsigned NOT NULL COMMENT 'Rozmiar w bajtach',
  `mime_type` varchar(100) NOT NULL COMMENT 'pdf, xlsx, zip, xml, docx, etc.',
  `file_type` enum('document','spreadsheet','archive','certificate','manual','other') NOT NULL DEFAULT 'document' COMMENT 'Typ dokumentu dla filtrowania',
  `access_level` enum('admin','manager','all') NOT NULL DEFAULT 'all' COMMENT 'Kto może zobaczyć plik',
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `description` text DEFAULT NULL COMMENT 'Opis dokumentu',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dodatkowe metadane (rozmiar, hash, etc.)' CHECK (json_valid(`metadata`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uploads_polymorphic` (`uploadable_type`,`uploadable_id`),
  KEY `idx_uploads_type` (`uploadable_type`,`uploadable_id`,`file_type`),
  KEY `idx_uploads_access` (`uploadable_type`,`uploadable_id`,`access_level`),
  KEY `idx_uploads_active` (`uploadable_type`,`uploadable_id`,`is_active`),
  KEY `idx_uploads_user` (`uploaded_by`),
  KEY `idx_uploads_created` (`created_at`),
  KEY `idx_uploads_access_control` (`uploadable_type`,`uploadable_id`,`access_level`,`is_active`),
  KEY `idx_uploads_type_access` (`uploadable_type`,`uploadable_id`,`file_type`,`access_level`),
  KEY `idx_uploads_user_audit` (`uploaded_by`,`created_at`),
  KEY `idx_uploads_cleanup` (`file_size`,`created_at`),
  KEY `idx_uploads_mime_active` (`mime_type`,`is_active`),
  CONSTRAINT `file_uploads_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `import_jobs`
--

DROP TABLE IF EXISTS `import_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` char(36) NOT NULL COMMENT 'UUID zadania',
  `job_type` varchar(255) NOT NULL DEFAULT 'prestashop_import' COMMENT 'Typ zadania',
  `job_name` varchar(255) NOT NULL COMMENT 'Nazwa zadania',
  `source_type` varchar(255) NOT NULL DEFAULT 'prestashop' COMMENT 'Źródło danych',
  `target_type` varchar(255) NOT NULL DEFAULT 'ppm' COMMENT 'Cel danych',
  `source_id` bigint(20) unsigned NOT NULL COMMENT 'ID sklepu PrestaShop',
  `trigger_type` varchar(255) NOT NULL DEFAULT 'manual' COMMENT 'Sposób wywołania',
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'ID użytkownika',
  `scheduled_at` timestamp NULL DEFAULT NULL COMMENT 'Data zaplanowania',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'Data rozpoczęcia',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Data zakończenia',
  `job_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Konfiguracja importu' CHECK (json_valid(`job_config`)),
  `rollback_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane dla rollback' CHECK (json_valid(`rollback_data`)),
  `status` varchar(255) NOT NULL DEFAULT 'pending' COMMENT 'Status zadania',
  `progress` tinyint(3) unsigned DEFAULT NULL COMMENT 'Postęp w procentach (0-100)',
  `error_message` text DEFAULT NULL COMMENT 'Komunikat błędu',
  `records_total` int(10) unsigned DEFAULT NULL COMMENT 'Całkowita liczba rekordów',
  `records_processed` int(10) unsigned DEFAULT NULL COMMENT 'Liczba przetworzonych',
  `records_failed` int(10) unsigned DEFAULT NULL COMMENT 'Liczba błędnych',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `import_jobs_job_id_unique` (`job_id`),
  KEY `import_jobs_job_type_index` (`job_type`),
  KEY `import_jobs_source_id_index` (`source_id`),
  KEY `import_jobs_user_id_index` (`user_id`),
  KEY `import_jobs_scheduled_at_index` (`scheduled_at`),
  KEY `import_jobs_created_at_index` (`created_at`),
  KEY `import_jobs_status_created_at_index` (`status`,`created_at`),
  KEY `import_jobs_status_index` (`status`),
  CONSTRAINT `import_jobs_source_id_foreign` FOREIGN KEY (`source_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `import_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `integration_logs`
--

DROP TABLE IF EXISTS `integration_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_level` enum('debug','info','notice','warning','error','critical','alert','emergency') NOT NULL COMMENT 'Poziom ważności logu',
  `log_type` varchar(100) NOT NULL COMMENT 'Typ operacji (api_call, sync, auth, webhook)',
  `category` varchar(100) DEFAULT NULL,
  `subcategory` varchar(100) DEFAULT NULL COMMENT 'Podkategoria (products, orders, stock)',
  `integration_type` enum('prestashop','baselinker','subiekt_gt','dynamics','internal','webhook') NOT NULL COMMENT 'Typ integracji',
  `integration_id` varchar(200) DEFAULT NULL COMMENT 'ID instancji integracji',
  `external_system` varchar(200) DEFAULT NULL COMMENT 'Nazwa systemu zewnętrznego',
  `operation` varchar(200) NOT NULL COMMENT 'Nazwa operacji',
  `method` varchar(20) DEFAULT NULL COMMENT 'HTTP method (GET, POST, PUT, DELETE)',
  `endpoint` varchar(500) DEFAULT NULL COMMENT 'Endpoint URL lub ścieżka',
  `description` text DEFAULT NULL COMMENT 'Opis operacji',
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane żądania (headers, params, body)' CHECK (json_valid(`request_data`)),
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane odpowiedzi' CHECK (json_valid(`response_data`)),
  `http_status` int(11) DEFAULT NULL COMMENT 'Status HTTP odpowiedzi',
  `response_time_ms` int(11) DEFAULT NULL COMMENT 'Czas odpowiedzi w ms',
  `response_size_bytes` int(11) DEFAULT NULL COMMENT 'Rozmiar odpowiedzi w bajtach',
  `error_code` varchar(100) DEFAULT NULL COMMENT 'Kod błędu',
  `error_message` text DEFAULT NULL COMMENT 'Komunikat błędu',
  `error_details` longtext DEFAULT NULL COMMENT 'Szczegółowe informacje o błędzie',
  `stack_trace` text DEFAULT NULL COMMENT 'Stack trace dla błędów',
  `entity_type` varchar(100) DEFAULT NULL COMMENT 'Typ encji (Product, Category, Order)',
  `entity_id` varchar(200) DEFAULT NULL COMMENT 'ID encji',
  `entity_reference` varchar(300) DEFAULT NULL COMMENT 'Referencja encji (SKU, name, code)',
  `affected_records` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba przetworzonych rekordów',
  `sync_job_id` varchar(100) DEFAULT NULL COMMENT 'ID zadania synchronizacji',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `session_id` varchar(200) DEFAULT NULL COMMENT 'ID sesji użytkownika',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Adres IP klienta',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'User agent klienta',
  `sensitive_data` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy log zawiera dane wrażliwe',
  `gdpr_relevant` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy dotyczy GDPR',
  `retention_until` timestamp NULL DEFAULT NULL COMMENT 'Data usunięcia logu (retention policy)',
  `memory_usage_mb` int(11) DEFAULT NULL COMMENT 'Zużycie pamięci w MB',
  `cpu_time_ms` decimal(10,3) DEFAULT NULL COMMENT 'Czas CPU w ms',
  `database_queries` int(11) DEFAULT NULL COMMENT 'Liczba zapytań DB',
  `database_time_ms` decimal(10,3) DEFAULT NULL COMMENT 'Czas zapytań DB w ms',
  `correlation_id` varchar(100) DEFAULT NULL COMMENT 'ID korelacji dla powiązanych operacji',
  `trace_id` varchar(100) DEFAULT NULL COMMENT 'ID trace dla distributed tracing',
  `span_id` varchar(100) DEFAULT NULL COMMENT 'ID span w distributed tracing',
  `parent_span_id` varchar(100) DEFAULT NULL COMMENT 'ID parent span',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Tagi dla kategoryzacji i filtrowania' CHECK (json_valid(`tags`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dodatkowe metadane' CHECK (json_valid(`metadata`)),
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Custom fields dla specific integrations' CHECK (json_valid(`custom_fields`)),
  `alert_triggered` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy wywołano alert',
  `alert_rule` varchar(200) DEFAULT NULL COMMENT 'Nazwa reguły alertu',
  `alert_sent_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy wysłano alert',
  `environment` varchar(50) NOT NULL DEFAULT 'production' COMMENT 'Środowisko (dev, staging, production)',
  `server_name` varchar(200) DEFAULT NULL COMMENT 'Nazwa serwera',
  `application_version` varchar(50) DEFAULT NULL COMMENT 'Wersja aplikacji',
  `processing_status` enum('raw','processed','archived','deleted') NOT NULL DEFAULT 'raw' COMMENT 'Status przetwarzania logu',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy przetworzono log',
  `archived_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy zarchiwizowano',
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Kiedy wystąpiło zdarzenie',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_logs_level` (`log_level`),
  KEY `idx_logs_type` (`log_type`),
  KEY `idx_logs_category` (`category`),
  KEY `idx_logs_integration` (`integration_type`),
  KEY `idx_logs_integration_id` (`integration_id`),
  KEY `idx_logs_operation` (`operation`),
  KEY `idx_logs_logged_at` (`logged_at`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_sync_job` (`sync_job_id`),
  KEY `idx_logs_entity` (`entity_type`,`entity_id`),
  KEY `idx_logs_correlation` (`correlation_id`),
  KEY `idx_logs_trace` (`trace_id`),
  KEY `idx_logs_processing_status` (`processing_status`),
  KEY `idx_logs_retention` (`retention_until`),
  KEY `idx_logs_level_time` (`log_level`,`logged_at`),
  KEY `idx_logs_integration_level` (`integration_type`,`log_level`),
  KEY `idx_logs_cat_op_time` (`category`,`operation`,`logged_at`),
  KEY `idx_logs_error_time` (`error_code`,`logged_at`),
  KEY `idx_logs_alert_time` (`alert_triggered`,`logged_at`),
  FULLTEXT KEY `fulltext_logs_error_desc` (`error_message`,`description`),
  CONSTRAINT `integration_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=116 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `integration_mappings`
--

DROP TABLE IF EXISTS `integration_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mappable_type` varchar(100) NOT NULL COMMENT 'Product, Category, PriceGroup, Warehouse, User',
  `mappable_id` bigint(20) unsigned NOT NULL COMMENT 'ID obiektu w systemie PPM',
  `integration_type` enum('prestashop','baselinker','subiekt_gt','dynamics','custom') NOT NULL COMMENT 'System zewnętrzny',
  `integration_identifier` varchar(200) NOT NULL COMMENT 'Identyfikator systemu (shop_id, instance_name, etc.)',
  `external_id` varchar(200) NOT NULL COMMENT 'ID w systemie zewnętrznym',
  `external_reference` varchar(300) DEFAULT NULL COMMENT 'Dodatkowa referencja (SKU, code, etc.)',
  `external_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Pełne dane z systemu zewnętrznego' CHECK (json_valid(`external_data`)),
  `sync_status` enum('pending','synced','error','conflict','disabled') NOT NULL DEFAULT 'pending' COMMENT 'Status synchronizacji',
  `sync_direction` enum('both','to_external','from_external','disabled') NOT NULL DEFAULT 'both' COMMENT 'Kierunek synchronizacji',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnia synchronizacja',
  `next_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Następna zaplanowana synchronizacja',
  `error_message` text DEFAULT NULL COMMENT 'Szczegóły ostatniego błędu',
  `error_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba błędów z rzędu',
  `conflict_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane konfliktu do rozwiązania' CHECK (json_valid(`conflict_data`)),
  `conflict_detected_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy wykryto konflikt',
  `ppm_version_hash` varchar(64) DEFAULT NULL COMMENT 'Hash wersji danych w PPM',
  `external_version_hash` varchar(64) DEFAULT NULL COMMENT 'Hash wersji danych zewnętrznych',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping_per_integration` (`mappable_type`,`mappable_id`,`integration_type`,`integration_identifier`),
  KEY `idx_mappings_polymorphic` (`mappable_type`,`mappable_id`),
  KEY `idx_mappings_integration_type` (`integration_type`),
  KEY `idx_mappings_sync_status` (`sync_status`),
  KEY `idx_mappings_error_handling` (`sync_status`,`error_count`),
  KEY `idx_mappings_scheduled_sync` (`next_sync_at`,`sync_status`),
  KEY `idx_mappings_external_lookup` (`integration_type`,`external_id`),
  KEY `idx_mappings_conflicts` (`sync_status`,`conflict_detected_at`),
  KEY `idx_mappings_identifier` (`integration_type`,`integration_identifier`),
  KEY `idx_mappings_last_sync` (`last_sync_at`),
  KEY `idx_mappings_external_data` (`external_data`(768))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_progress`
--

DROP TABLE IF EXISTS `job_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_progress` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) NOT NULL COMMENT 'Laravel queue job ID',
  `job_type` enum('import','sync','export','category_delete') NOT NULL COMMENT 'Operation type',
  `shop_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Target PrestaShop shop',
  `status` enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
  `current_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Processed items count',
  `total_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Total items to process',
  `error_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'Failed items count',
  `error_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of errors with SKU/ID' CHECK (json_valid(`error_details`)),
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'Job start timestamp',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Job completion timestamp',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_progress_job_id_unique` (`job_id`),
  KEY `idx_job_id` (`job_id`),
  KEY `idx_shop_id` (`shop_id`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_shop_status` (`shop_id`,`status`),
  CONSTRAINT `job_progress_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=269 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=5352 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maintenance_tasks`
--

DROP TABLE IF EXISTS `maintenance_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('database_optimization','log_cleanup','cache_cleanup','security_check','file_cleanup','index_rebuild','stats_update') NOT NULL,
  `status` enum('pending','running','completed','failed','skipped') NOT NULL DEFAULT 'pending',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `result_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`result_data`)),
  `error_message` text DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_rule` varchar(255) DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_tasks_created_by_foreign` (`created_by`),
  KEY `maintenance_tasks_status_index` (`status`),
  KEY `maintenance_tasks_type_index` (`type`),
  KEY `maintenance_tasks_scheduled_at_index` (`scheduled_at`),
  KEY `maintenance_tasks_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `maintenance_tasks_is_recurring_index` (`is_recurring`),
  KEY `maintenance_tasks_next_run_at_index` (`next_run_at`),
  CONSTRAINT `maintenance_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mediable_type` varchar(100) NOT NULL COMMENT 'Product, ProductVariant',
  `mediable_id` bigint(20) unsigned NOT NULL COMMENT 'ID powiązanego obiektu',
  `file_name` varchar(300) NOT NULL COMMENT 'Nazwa pliku w storage',
  `original_name` varchar(300) DEFAULT NULL COMMENT 'Oryginalna nazwa uploadowanego pliku',
  `file_path` varchar(500) NOT NULL COMMENT 'Ścieżka do pliku w storage',
  `file_size` int(10) unsigned NOT NULL COMMENT 'Rozmiar w bajtach',
  `mime_type` varchar(100) NOT NULL COMMENT 'jpg, jpeg, png, webp, gif',
  `width` int(10) unsigned DEFAULT NULL COMMENT 'Szerokość obrazu w pikselach',
  `height` int(10) unsigned DEFAULT NULL COMMENT 'Wysokość obrazu w pikselach',
  `alt_text` varchar(300) DEFAULT NULL COMMENT 'Tekst alternatywny dla SEO/accessibility',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Kolejność wyświetlania',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Główne zdjęcie produktu/wariantu',
  `prestashop_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie per sklep PrestaShop' CHECK (json_valid(`prestashop_mapping`)),
  `sync_status` enum('pending','synced','error','ignored') NOT NULL DEFAULT 'pending' COMMENT 'Status synchronizacji z systemami zewnętrznymi',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_media_polymorphic` (`mediable_type`,`mediable_id`),
  KEY `idx_media_primary` (`mediable_type`,`mediable_id`,`is_primary`),
  KEY `idx_media_sort` (`mediable_type`,`mediable_id`,`sort_order`),
  KEY `idx_media_active` (`mediable_type`,`mediable_id`,`is_active`),
  KEY `idx_media_sync_status` (`sync_status`),
  KEY `idx_media_primary_active` (`mediable_type`,`mediable_id`,`is_primary`,`is_active`),
  KEY `idx_media_gallery_sort` (`mediable_type`,`mediable_id`,`is_active`,`sort_order`),
  KEY `idx_media_file_path` (`file_path`),
  KEY `idx_media_mime_active` (`mime_type`,`is_active`),
  KEY `idx_media_file_size` (`file_size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(200) NOT NULL,
  `notifiable_type` varchar(100) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_notifiable` (`notifiable_type`,`notifiable_id`),
  KEY `idx_notifications_unread` (`notifiable_type`,`notifiable_id`,`read_at`),
  KEY `idx_notifications_type_time` (`type`,`created_at`),
  KEY `idx_notifications_time_read` (`created_at`,`read_at`),
  KEY `notifications_type_index` (`type`),
  KEY `notifications_notifiable_type_index` (`notifiable_type`),
  KEY `notifications_notifiable_id_index` (`notifiable_id`),
  KEY `notifications_read_at_index` (`read_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`notifiable_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Laravel-compatible notifications system for PPM application';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oauth_audit_logs`
--

DROP TABLE IF EXISTS `oauth_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `oauth_provider` varchar(50) NOT NULL,
  `oauth_action` varchar(100) NOT NULL,
  `oauth_event_type` varchar(50) NOT NULL,
  `oauth_session_id` varchar(100) DEFAULT NULL,
  `oauth_state` varchar(255) DEFAULT NULL,
  `oauth_client_id` varchar(255) DEFAULT NULL,
  `oauth_redirect_uri` varchar(500) DEFAULT NULL,
  `oauth_email` varchar(255) DEFAULT NULL,
  `oauth_domain` varchar(100) DEFAULT NULL,
  `oauth_external_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `oauth_request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_request_data`)),
  `oauth_response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_response_data`)),
  `oauth_token_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_token_info`)),
  `oauth_profile_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_profile_data`)),
  `oauth_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_permissions`)),
  `security_level` varchar(20) NOT NULL DEFAULT 'normal',
  `security_indicators` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`security_indicators`)),
  `compliance_category` varchar(50) DEFAULT NULL,
  `requires_review` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL,
  `error_message` text DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `oauth_initiated_at` timestamp NULL DEFAULT NULL,
  `oauth_completed_at` timestamp NULL DEFAULT NULL,
  `processing_time_ms` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `retention_policy` varchar(50) NOT NULL DEFAULT 'standard',
  `is_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_oauth_audit_provider_action_time` (`oauth_provider`,`oauth_action`,`created_at`),
  KEY `idx_oauth_audit_user_provider_time` (`user_id`,`oauth_provider`,`created_at`),
  KEY `idx_oauth_audit_security_review` (`security_level`,`requires_review`,`created_at`),
  KEY `idx_oauth_audit_status_event` (`status`,`oauth_event_type`,`created_at`),
  KEY `idx_oauth_audit_domain_action` (`oauth_domain`,`oauth_action`,`created_at`),
  KEY `idx_oauth_audit_compliance` (`compliance_category`,`is_sensitive`),
  KEY `idx_oauth_audit_retention` (`archived_at`,`retention_policy`),
  KEY `oauth_audit_logs_user_id_index` (`user_id`),
  KEY `oauth_audit_logs_oauth_provider_index` (`oauth_provider`),
  KEY `oauth_audit_logs_oauth_action_index` (`oauth_action`),
  KEY `oauth_audit_logs_oauth_event_type_index` (`oauth_event_type`),
  KEY `oauth_audit_logs_oauth_session_id_index` (`oauth_session_id`),
  KEY `oauth_audit_logs_oauth_email_index` (`oauth_email`),
  KEY `oauth_audit_logs_oauth_domain_index` (`oauth_domain`),
  KEY `oauth_audit_logs_oauth_external_id_index` (`oauth_external_id`),
  KEY `oauth_audit_logs_ip_address_index` (`ip_address`),
  KEY `oauth_audit_logs_security_level_index` (`security_level`),
  KEY `oauth_audit_logs_compliance_category_index` (`compliance_category`),
  KEY `oauth_audit_logs_requires_review_index` (`requires_review`),
  KEY `oauth_audit_logs_status_index` (`status`),
  KEY `oauth_audit_logs_error_code_index` (`error_code`),
  KEY `oauth_audit_logs_attempt_number_index` (`attempt_number`),
  KEY `oauth_audit_logs_oauth_initiated_at_index` (`oauth_initiated_at`),
  KEY `oauth_audit_logs_oauth_completed_at_index` (`oauth_completed_at`),
  KEY `oauth_audit_logs_archived_at_index` (`archived_at`),
  KEY `oauth_audit_logs_retention_policy_index` (`retention_policy`),
  KEY `oauth_audit_logs_is_sensitive_index` (`is_sensitive`),
  CONSTRAINT `oauth_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth2 specific audit logging with security tracking and compliance features';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prestashop_attribute_group_mapping`
--

DROP TABLE IF EXISTS `prestashop_attribute_group_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestashop_attribute_group_mapping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_type_id` bigint(20) unsigned NOT NULL,
  `prestashop_shop_id` bigint(20) unsigned NOT NULL,
  `prestashop_attribute_group_id` int(10) unsigned DEFAULT NULL COMMENT 'PrestaShop ps_attribute_group.id_attribute_group',
  `prestashop_label` varchar(255) DEFAULT NULL COMMENT 'Label from PrestaShop (public_name)',
  `is_synced` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether attribute group is synchronized',
  `last_synced_at` timestamp NULL DEFAULT NULL COMMENT 'Last successful synchronization timestamp',
  `sync_status` enum('synced','pending','conflict','missing') NOT NULL DEFAULT 'pending' COMMENT 'Current synchronization status',
  `sync_notes` text DEFAULT NULL COMMENT 'Error messages, warnings, sync details',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_type_shop` (`attribute_type_id`,`prestashop_shop_id`),
  KEY `prestashop_attribute_group_mapping_prestashop_shop_id_foreign` (`prestashop_shop_id`),
  KEY `idx_group_sync_status` (`sync_status`),
  KEY `idx_group_last_synced` (`last_synced_at`),
  KEY `idx_group_ps_id` (`prestashop_attribute_group_id`),
  CONSTRAINT `prestashop_attribute_group_mapping_attribute_type_id_foreign` FOREIGN KEY (`attribute_type_id`) REFERENCES `attribute_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestashop_attribute_group_mapping_prestashop_shop_id_foreign` FOREIGN KEY (`prestashop_shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prestashop_attribute_value_mapping`
--

DROP TABLE IF EXISTS `prestashop_attribute_value_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestashop_attribute_value_mapping` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_value_id` bigint(20) unsigned NOT NULL,
  `prestashop_shop_id` bigint(20) unsigned NOT NULL,
  `prestashop_attribute_id` int(10) unsigned DEFAULT NULL COMMENT 'PrestaShop ps_attribute.id_attribute',
  `prestashop_label` varchar(255) DEFAULT NULL COMMENT 'Label from PrestaShop (name)',
  `prestashop_color` varchar(7) DEFAULT NULL COMMENT 'Color from PrestaShop (#ffffff format, NULL for non-color types)',
  `is_synced` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether attribute value is synchronized',
  `last_synced_at` timestamp NULL DEFAULT NULL COMMENT 'Last successful synchronization timestamp',
  `sync_status` enum('synced','conflict','missing','pending') NOT NULL DEFAULT 'pending' COMMENT 'Current synchronization status',
  `sync_notes` text DEFAULT NULL COMMENT 'Error messages, warnings, color mismatches, sync details',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_value_shop` (`attribute_value_id`,`prestashop_shop_id`),
  KEY `prestashop_attribute_value_mapping_prestashop_shop_id_foreign` (`prestashop_shop_id`),
  KEY `idx_value_sync_status` (`sync_status`),
  KEY `idx_value_last_synced` (`last_synced_at`),
  KEY `idx_value_ps_id` (`prestashop_attribute_id`),
  CONSTRAINT `prestashop_attribute_value_mapping_attribute_value_id_foreign` FOREIGN KEY (`attribute_value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestashop_attribute_value_mapping_prestashop_shop_id_foreign` FOREIGN KEY (`prestashop_shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prestashop_shop_price_mappings`
--

DROP TABLE IF EXISTS `prestashop_shop_price_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestashop_shop_price_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `prestashop_shop_id` bigint(20) unsigned NOT NULL,
  `prestashop_price_group_id` bigint(20) unsigned NOT NULL,
  `prestashop_price_group_name` varchar(255) NOT NULL,
  `ppm_price_group_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_ps_group_unique` (`prestashop_shop_id`,`prestashop_price_group_id`),
  KEY `prestashop_shop_price_mappings_prestashop_shop_id_index` (`prestashop_shop_id`),
  CONSTRAINT `prestashop_shop_price_mappings_prestashop_shop_id_foreign` FOREIGN KEY (`prestashop_shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `prestashop_shops`
--

DROP TABLE IF EXISTS `prestashop_shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestashop_shops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT 'Nazwa sklepu dla identyfikacji',
  `url` varchar(500) NOT NULL COMMENT 'URL sklepu PrestaShop',
  `description` varchar(1000) DEFAULT NULL COMMENT 'Opis sklepu',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy sklep jest aktywny',
  `api_key` text NOT NULL,
  `default_warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `api_version` varchar(20) NOT NULL DEFAULT '1.7' COMMENT 'Wersja API PrestaShop',
  `db_host` varchar(200) DEFAULT NULL COMMENT 'PrestaShop database host (dla category associations workaround)',
  `db_name` varchar(200) DEFAULT NULL COMMENT 'PrestaShop database name',
  `db_user` varchar(200) DEFAULT NULL COMMENT 'PrestaShop database user',
  `db_password` text DEFAULT NULL COMMENT 'PrestaShop database password (encrypted)',
  `enable_db_workaround` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable direct DB workaround for category associations',
  `ssl_verify` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Weryfikacja certyfikatu SSL',
  `timeout_seconds` int(11) NOT NULL DEFAULT 30 COMMENT 'Timeout połączenia API',
  `rate_limit_per_minute` int(11) NOT NULL DEFAULT 60 COMMENT 'Limit zapytań per minuta',
  `connection_status` enum('connected','disconnected','error','maintenance') NOT NULL DEFAULT 'disconnected' COMMENT 'Status połączenia',
  `last_connection_test` timestamp NULL DEFAULT NULL COMMENT 'Ostatni test połączenia',
  `last_response_time` decimal(8,3) DEFAULT NULL COMMENT 'Czas odpowiedzi (ms)',
  `consecutive_failures` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba niepowodzeń z rzędu',
  `last_error_message` text DEFAULT NULL COMMENT 'Ostatni błąd połączenia',
  `prestashop_version` varchar(50) DEFAULT NULL COMMENT 'Wykryta wersja PrestaShop',
  `version_compatible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy wersja jest kompatybilna',
  `supported_features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Lista wspieranych funkcji' CHECK (json_valid(`supported_features`)),
  `sync_frequency` enum('realtime','hourly','daily','manual') NOT NULL DEFAULT 'hourly' COMMENT 'Częstotliwość synchronizacji',
  `sync_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ustawienia synchronizacji' CHECK (json_valid(`sync_settings`)),
  `auto_sync_products` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync produktów',
  `auto_sync_categories` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync kategorii',
  `auto_sync_prices` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync cen',
  `auto_sync_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto sync stanów',
  `conflict_resolution` enum('ppm_wins','prestashop_wins','manual','newest_wins') NOT NULL DEFAULT 'ppm_wins' COMMENT 'Strategia rozwiązywania konfliktów',
  `category_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie kategorii PPM → PrestaShop' CHECK (json_valid(`category_mappings`)),
  `price_group_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie grup cenowych' CHECK (json_valid(`price_group_mappings`)),
  `warehouse_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie magazynów' CHECK (json_valid(`warehouse_mappings`)),
  `custom_field_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie custom fields' CHECK (json_valid(`custom_field_mappings`)),
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnia synchronizacja',
  `next_scheduled_sync` timestamp NULL DEFAULT NULL COMMENT 'Następna zaplanowana synchronizacja',
  `products_synced` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba zsynchronizowanych produktów',
  `sync_success_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba udanych synchronizacji',
  `sync_error_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba błędów synchronizacji',
  `avg_response_time` decimal(8,3) DEFAULT NULL COMMENT 'Średni czas odpowiedzi API',
  `api_quota_used` int(11) NOT NULL DEFAULT 0 COMMENT 'Wykorzystana quota API',
  `api_quota_limit` int(11) DEFAULT NULL COMMENT 'Limit quota API',
  `quota_reset_at` timestamp NULL DEFAULT NULL COMMENT 'Reset quota API',
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ustawienia powiadomień' CHECK (json_valid(`notification_settings`)),
  `notify_on_errors` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Powiadomienia o błędach',
  `notify_on_sync_complete` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Powiadomienia po sync',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tax_rules_group_id_23` int(11) DEFAULT NULL COMMENT 'PrestaShop tax_rules_group ID for 23% VAT (PL Standard Rate)',
  `tax_rules_group_id_8` int(11) DEFAULT NULL COMMENT 'PrestaShop tax_rules_group ID for 8% VAT (PL Reduced Rate)',
  `tax_rules_group_id_5` int(11) DEFAULT NULL COMMENT 'PrestaShop tax_rules_group ID for 5% VAT (PL Super Reduced Rate)',
  `tax_rules_group_id_0` int(11) DEFAULT NULL COMMENT 'PrestaShop tax_rules_group ID for 0% VAT (Exempt)',
  `tax_rules_last_fetched_at` timestamp NULL DEFAULT NULL COMMENT 'Last time tax rules were auto-detected from PrestaShop API',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shop_url` (`url`),
  KEY `idx_shops_active` (`is_active`),
  KEY `idx_shops_connection_status` (`connection_status`),
  KEY `idx_shops_sync_frequency` (`sync_frequency`),
  KEY `idx_shops_last_sync` (`last_sync_at`),
  KEY `idx_shops_scheduled_sync` (`next_scheduled_sync`),
  KEY `idx_shops_failures` (`consecutive_failures`),
  KEY `prestashop_shops_default_warehouse_id_index` (`default_warehouse_id`),
  CONSTRAINT `prestashop_shops_default_warehouse_id_foreign` FOREIGN KEY (`default_warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `price_groups`
--

DROP TABLE IF EXISTS `price_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Display name: Detaliczna, Dealer Standard, etc.',
  `code` varchar(50) NOT NULL COMMENT 'Unique code: retail, dealer_std, dealer_premium, etc.',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Only one group can be default',
  `margin_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Default margin % for this group (-100.00 to 999.99)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status for filtering',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order in UI',
  `prestashop_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'PrestaShop specific_price groups mapping per shop' CHECK (json_valid(`prestashop_mapping`)),
  `erp_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'ERP systems price groups mapping (Baselinker, Subiekt, Dynamics)' CHECK (json_valid(`erp_mapping`)),
  `description` text DEFAULT NULL COMMENT 'Group description and usage notes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `price_groups_code_unique` (`code`),
  KEY `idx_price_groups_active` (`is_active`),
  KEY `idx_price_groups_default` (`is_default`),
  KEY `idx_price_groups_sort_active` (`sort_order`,`is_active`),
  CONSTRAINT `chk_price_groups_margin` CHECK (`margin_percentage` >= -100.00 and `margin_percentage` <= 999.99),
  CONSTRAINT `chk_price_groups_sort` CHECK (`sort_order` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Price Groups: 8 pricing tiers dla wielopoziomowego systemu cenowego';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `price_history`
--

DROP TABLE IF EXISTS `price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `historyable_type` varchar(255) NOT NULL,
  `historyable_id` bigint(20) unsigned NOT NULL,
  `action` enum('created','updated','deleted','bulk_update','import','sync','restore') NOT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `change_reason` text DEFAULT NULL,
  `batch_id` varchar(100) DEFAULT NULL,
  `adjustment_percentage` decimal(8,2) DEFAULT NULL,
  `adjustment_type` enum('percentage','fixed_amount','set_margin','set_price') DEFAULT NULL,
  `affected_products_count` int(10) unsigned DEFAULT NULL,
  `source` enum('admin_panel','api','import','erp_sync','prestashop_sync','system','migration') NOT NULL DEFAULT 'admin_panel',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_historyable` (`historyable_type`,`historyable_id`),
  KEY `idx_created_action` (`created_at`,`action`),
  KEY `idx_user_date` (`created_by`,`created_at`),
  KEY `idx_batch_date` (`batch_id`,`created_at`),
  KEY `idx_source_date` (`source`,`created_at`),
  KEY `price_history_action_index` (`action`),
  KEY `price_history_batch_id_index` (`batch_id`),
  KEY `price_history_source_index` (`source`),
  KEY `price_history_created_at_index` (`created_at`),
  CONSTRAINT `price_history_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_attribute_values`
--

DROP TABLE IF EXISTS `product_attribute_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attribute_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `product_variant_id` bigint(20) unsigned DEFAULT NULL,
  `attribute_id` bigint(20) unsigned NOT NULL,
  `value_text` text DEFAULT NULL COMMENT 'Wartość tekstowa (Model: Yamaha YZ250F, Oryginał: OEM123, etc.)',
  `value_number` decimal(15,6) DEFAULT NULL COMMENT 'Wartość numeryczna (waga, wymiary, etc.)',
  `value_boolean` tinyint(1) DEFAULT NULL COMMENT 'Wartość tak/nie',
  `value_date` date DEFAULT NULL COMMENT 'Wartość daty',
  `value_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Złożone dane (multiselect, structured data)' CHECK (json_valid(`value_json`)),
  `is_inherited` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy dziedziczy z produktu głównego',
  `is_override` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy nadpisuje wartość z głównego produktu',
  `is_valid` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy wartość przeszła walidację',
  `validation_error` text DEFAULT NULL COMMENT 'Błędy walidacji',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_variant_attribute` (`product_id`,`product_variant_id`,`attribute_id`),
  KEY `product_attribute_values_attribute_id_foreign` (`attribute_id`),
  KEY `idx_values_product_attribute` (`product_id`,`attribute_id`),
  KEY `idx_values_variant_attribute` (`product_variant_id`,`attribute_id`),
  KEY `idx_values_text_search` (`value_text`(255)),
  KEY `idx_values_number` (`value_number`),
  KEY `idx_values_boolean` (`value_boolean`),
  KEY `idx_values_date` (`value_date`),
  KEY `idx_values_inheritance` (`product_id`,`is_inherited`),
  KEY `idx_values_valid` (`is_valid`),
  KEY `idx_values_json` (`value_json`(768)),
  KEY `idx_values_product_effective` (`product_id`,`is_inherited`,`is_valid`),
  KEY `idx_values_variant_override` (`product_variant_id`,`is_override`,`is_valid`),
  CONSTRAINT `product_attribute_values_attribute_id_foreign` FOREIGN KEY (`attribute_id`) REFERENCES `product_attributes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_attribute_values_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_attributes`
--

DROP TABLE IF EXISTS `product_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT 'Model, Oryginał, Zamiennik, Kolor, Rozmiar, Materiał',
  `code` varchar(100) NOT NULL COMMENT 'model, original, replacement, color, size, material',
  `attribute_type` enum('text','number','boolean','select','multiselect','date','json') NOT NULL COMMENT 'Typ pola dla formularzy',
  `is_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy wymagane przy dodawaniu produktu',
  `is_filterable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy można filtrować w wyszukiwaniu',
  `is_variant_specific` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy może różnić się między wariantami',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Kolejność wyświetlania w formularzu',
  `display_group` varchar(100) NOT NULL DEFAULT 'general' COMMENT 'Grupa wyświetlania: general, technical, compatibility',
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Reguły walidacji: min, max, pattern, etc.' CHECK (json_valid(`validation_rules`)),
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Opcje dla select/multiselect w formacie [{"value": "red", "label": "Czerwony"}]' CHECK (json_valid(`options`)),
  `default_value` varchar(500) DEFAULT NULL COMMENT 'Domyślna wartość',
  `help_text` text DEFAULT NULL COMMENT 'Tekst pomocy dla użytkownika',
  `unit` varchar(50) DEFAULT NULL COMMENT 'Jednostka miary: kg, cm, L, etc.',
  `format_pattern` varchar(100) DEFAULT NULL COMMENT 'Pattern formatowania wyświetlania',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_attributes_code_unique` (`code`),
  KEY `idx_attributes_code` (`code`),
  KEY `idx_attributes_active` (`is_active`),
  KEY `idx_attributes_type_active` (`attribute_type`,`is_active`),
  KEY `idx_attributes_filterable` (`is_filterable`,`is_active`),
  KEY `idx_attributes_sort` (`sort_order`),
  KEY `idx_attributes_group_sort` (`display_group`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `shop_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL = dane domyślne, NOT NULL = per-shop override',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_category_per_shop` (`product_id`,`category_id`,`shop_id`),
  KEY `product_categories_product_id_index` (`product_id`),
  KEY `product_categories_category_id_index` (`category_id`),
  KEY `product_categories_category_id_sort_order_index` (`category_id`,`sort_order`),
  KEY `product_categories_is_primary_index` (`is_primary`),
  KEY `product_categories_product_id_is_primary_index` (`product_id`,`is_primary`),
  KEY `product_categories_created_at_index` (`created_at`),
  KEY `idx_product_categories_shop_id` (`shop_id`),
  CONSTRAINT `fk_product_categories_shop` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_categories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_features`
--

DROP TABLE IF EXISTS `product_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `feature_type_id` bigint(20) unsigned NOT NULL,
  `feature_value_id` bigint(20) unsigned DEFAULT NULL,
  `custom_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_prod_feature_type` (`product_id`,`feature_type_id`),
  KEY `product_features_feature_type_id_foreign` (`feature_type_id`),
  KEY `idx_prod_feature_type` (`product_id`,`feature_type_id`),
  KEY `idx_prod_feature_value` (`feature_value_id`),
  CONSTRAINT `product_features_feature_type_id_foreign` FOREIGN KEY (`feature_type_id`) REFERENCES `feature_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_features_feature_value_id_foreign` FOREIGN KEY (`feature_value_id`) REFERENCES `feature_values` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_features_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_prices`
--

DROP TABLE IF EXISTS `product_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL COMMENT 'Products.id - REQUIRED',
  `product_variant_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Product_variants.id - OPTIONAL for variant-specific pricing',
  `price_group_id` bigint(20) unsigned NOT NULL COMMENT 'Price_groups.id - REQUIRED',
  `price_net` decimal(10,2) NOT NULL COMMENT 'Net price (before tax) in base currency',
  `price_gross` decimal(10,2) NOT NULL COMMENT 'Gross price (with tax) in base currency',
  `cost_price` decimal(10,2) DEFAULT NULL COMMENT 'Purchase/cost price - SENSITIVE DATA (Admin/Manager only)',
  `currency` varchar(3) NOT NULL DEFAULT 'PLN' COMMENT 'Price currency (ISO 4217)',
  `exchange_rate` decimal(8,4) NOT NULL DEFAULT 1.0000 COMMENT 'Exchange rate to base currency at time of pricing',
  `valid_from` timestamp NULL DEFAULT NULL COMMENT 'Price validity start date',
  `valid_to` timestamp NULL DEFAULT NULL COMMENT 'Price validity end date',
  `margin_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Profit margin % ((price_net - cost_price) / cost_price * 100)',
  `markup_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Markup % ((price_net - cost_price) / price_net * 100)',
  `prestashop_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                PrestaShop specific_price mapping per shop:\n                {\n                    "shop_1": {"specific_price_id": 123, "reduction": 0.15, "reduction_type": "percentage"},\n                    "shop_2": {"specific_price_id": 124, "reduction": 5.00, "reduction_type": "amount"}\n                }\n            ' CHECK (json_valid(`prestashop_mapping`)),
  `auto_calculate_gross` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto-calculate gross price from net + tax_rate',
  `auto_calculate_margin` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto-calculate margin from cost_price',
  `price_includes_tax` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether price_net already includes tax',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Price is active and should be used',
  `is_promotion` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Promotional/special price indicator',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who created this price',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who last updated this price',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_variant_price_group` (`product_id`,`product_variant_id`,`price_group_id`),
  KEY `product_prices_created_by_foreign` (`created_by`),
  KEY `product_prices_updated_by_foreign` (`updated_by`),
  KEY `idx_product_price_group` (`product_id`,`price_group_id`),
  KEY `idx_variant_price_group` (`product_variant_id`,`price_group_id`),
  KEY `idx_price_group_active` (`price_group_id`,`is_active`),
  KEY `idx_currency_active` (`currency`,`is_active`),
  KEY `idx_price_validity` (`valid_from`,`valid_to`,`is_active`),
  KEY `idx_active_promotion` (`is_active`,`is_promotion`),
  KEY `idx_product_active_prices` (`product_id`),
  CONSTRAINT `product_prices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_prices_price_group_id_foreign` FOREIGN KEY (`price_group_id`) REFERENCES `price_groups` (`id`),
  CONSTRAINT `product_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_prices_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_prices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_prices_positive` CHECK (`price_net` >= 0 and `price_gross` >= 0 and (`cost_price` is null or `cost_price` >= 0)),
  CONSTRAINT `chk_prices_gross_net` CHECK (`price_gross` >= `price_net`),
  CONSTRAINT `chk_prices_exchange_rate` CHECK (`exchange_rate` > 0),
  CONSTRAINT `chk_prices_margin_range` CHECK (`margin_percentage` >= -100.00 and `margin_percentage` <= 1000.00),
  CONSTRAINT `chk_prices_markup_range` CHECK (`markup_percentage` >= 0.00 and `markup_percentage` <= 100.00),
  CONSTRAINT `chk_prices_validity_dates` CHECK (`valid_to` is null or `valid_to` > `valid_from`)
) ENGINE=InnoDB AUTO_INCREMENT=10326 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Product Prices: Multi-tier pricing system z variant support';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_shop_data`
--

DROP TABLE IF EXISTS `product_shop_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_shop_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL COMMENT 'ID produktu w PPM',
  `shop_id` bigint(20) unsigned NOT NULL COMMENT 'ID sklepu PrestaShop',
  `prestashop_product_id` bigint(20) unsigned DEFAULT NULL COMMENT 'PrestaShop product ID (integer) - migrated from external_id',
  `name` varchar(500) DEFAULT NULL COMMENT 'Nazwa produktu specyficzna dla sklepu',
  `slug` varchar(600) DEFAULT NULL COMMENT 'Slug URL specyficzny dla sklepu',
  `sku` varchar(100) DEFAULT NULL COMMENT 'SKU specyficzne dla sklepu (override default)',
  `short_description` text DEFAULT NULL COMMENT 'Krótki opis specyficzny dla sklepu (max 800 znaków)',
  `long_description` longtext DEFAULT NULL COMMENT 'Długi opis specyficzny dla sklepu (max 21844 znaków)',
  `meta_title` varchar(255) DEFAULT NULL COMMENT 'SEO tytuł specyficzny dla sklepu',
  `meta_description` text DEFAULT NULL COMMENT 'SEO opis specyficzny dla sklepu',
  `product_type_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Typ produktu specyficzny dla sklepu',
  `manufacturer` varchar(200) DEFAULT NULL COMMENT 'Producent specyficzny dla sklepu',
  `supplier_code` varchar(100) DEFAULT NULL COMMENT 'Kod dostawcy specyficzny dla sklepu',
  `weight` decimal(8,3) DEFAULT NULL COMMENT 'Waga specyficzna dla sklepu (kg)',
  `height` decimal(8,2) DEFAULT NULL COMMENT 'Wysokość specyficzna dla sklepu (cm)',
  `width` decimal(8,2) DEFAULT NULL COMMENT 'Szerokość specyficzna dla sklepu (cm)',
  `length` decimal(8,2) DEFAULT NULL COMMENT 'Długość specyficzna dla sklepu (cm)',
  `ean` varchar(20) DEFAULT NULL COMMENT 'EAN specyficzny dla sklepu',
  `tax_rate` decimal(5,2) DEFAULT NULL COMMENT 'Stawka VAT specyficzna dla sklepu (%)',
  `tax_rate_override` decimal(5,2) DEFAULT NULL COMMENT 'Per-shop tax rate override (NULL = use products.tax_rate default)',
  `is_active` tinyint(1) DEFAULT NULL COMMENT 'Status aktywności specyficzny dla sklepu',
  `is_variant_master` tinyint(1) DEFAULT NULL COMMENT 'Czy posiada warianty - specyficzne dla sklepu',
  `sort_order` int(11) DEFAULT NULL COMMENT 'Kolejność sortowania specyficzna dla sklepu',
  `category_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie kategorii specyficzne dla sklepu' CHECK (json_valid(`category_mappings`)),
  `attribute_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Mapowanie atrybutów/cech specyficzne dla sklepu' CHECK (json_valid(`attribute_mappings`)),
  `image_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ustawienia zdjęć (kolejność, które wyświetlać)' CHECK (json_valid(`image_settings`)),
  `sync_status` enum('pending','syncing','synced','error','conflict','disabled') NOT NULL DEFAULT 'pending' COMMENT 'Status synchronizacji z tym sklepem',
  `pending_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of field names pending sync (e.g. ["name", "price"])' CHECK (json_valid(`pending_fields`)),
  `validation_warnings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_warnings`)),
  `has_validation_warnings` tinyint(1) NOT NULL DEFAULT 0,
  `validation_checked_at` timestamp NULL DEFAULT NULL,
  `sync_direction` enum('ppm_to_ps','ps_to_ppm','bidirectional') NOT NULL DEFAULT 'ppm_to_ps' COMMENT 'Kierunek synchronizacji: PPM→PrestaShop, PrestaShop→PPM, lub dwukierunkowa',
  `error_message` text DEFAULT NULL COMMENT 'Komunikat błędu synchronizacji (TEXT format)',
  `last_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnia synchronizacja z tym sklepem',
  `last_pulled_at` timestamp NULL DEFAULT NULL COMMENT 'Last time PrestaShop data was pulled to PPM',
  `last_push_at` timestamp NULL DEFAULT NULL COMMENT 'Last time PPM data was pushed to PrestaShop',
  `last_success_sync_at` timestamp NULL DEFAULT NULL COMMENT 'Ostatnia udana synchronizacja (success only)',
  `last_sync_hash` varchar(64) DEFAULT NULL COMMENT 'Hash danych z ostatniej synchronizacji (do wykrywania zmian)',
  `checksum` varchar(64) DEFAULT NULL COMMENT 'MD5 hash danych produktu dla wykrywania zmian (advanced detection)',
  `conflict_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane konfliktu do rozwiązania przez użytkownika' CHECK (json_valid(`conflict_data`)),
  `conflict_log` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`conflict_log`)),
  `has_conflicts` tinyint(1) NOT NULL DEFAULT 0,
  `conflicts_detected_at` timestamp NULL DEFAULT NULL,
  `conflict_detected_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy wykryto konflikt',
  `requires_resolution` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'TRUE = conflict awaiting user decision, FALSE = resolved or no conflict',
  `retry_count` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Liczba prób ponowienia synchronizacji',
  `max_retries` tinyint(3) unsigned NOT NULL DEFAULT 3 COMMENT 'Maksymalna liczba prób synchronizacji',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT 5 COMMENT 'Priorytet synchronizacji (1=najwyższy, 10=najniższy)',
  `is_published` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Czy produkt jest publikowany na tym sklepie',
  `published_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy opublikowano na tym sklepie',
  `unpublished_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy usunięto z tego sklepu',
  `external_reference` varchar(200) DEFAULT NULL COMMENT 'Dodatkowa referencja (SKU w PrestaShop)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_per_shop` (`product_id`,`shop_id`),
  UNIQUE KEY `unique_prestashop_product_per_shop` (`shop_id`,`prestashop_product_id`),
  KEY `idx_product_shop_lookup` (`product_id`,`shop_id`),
  KEY `idx_shop_products` (`shop_id`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_published_products` (`is_published`),
  KEY `idx_shop_sync_status` (`shop_id`,`sync_status`),
  KEY `idx_last_sync_monitoring` (`last_sync_at`),
  KEY `idx_conflict_resolution` (`sync_status`,`conflict_detected_at`),
  KEY `idx_external_lookup` (`shop_id`),
  KEY `idx_publishing_timeline` (`published_at`),
  KEY `idx_product_sync_aggregation` (`product_id`,`sync_status`),
  KEY `idx_shop_sku` (`sku`),
  KEY `idx_shop_product_type` (`product_type_id`),
  KEY `idx_shop_manufacturer` (`manufacturer`),
  KEY `idx_shop_supplier_code` (`supplier_code`),
  KEY `idx_shop_is_active` (`is_active`),
  KEY `idx_shop_sort_order` (`sort_order`),
  KEY `idx_ps_product_id` (`prestashop_product_id`),
  KEY `idx_retry_status` (`retry_count`,`max_retries`),
  KEY `idx_priority_status` (`priority`,`sync_status`),
  KEY `idx_sync_direction` (`sync_direction`),
  KEY `idx_requires_resolution` (`requires_resolution`),
  KEY `idx_conflicts_filter` (`has_conflicts`,`conflicts_detected_at`),
  CONSTRAINT `product_shop_data_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_shop_data_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_shop_data_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10395 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_stock`
--

DROP TABLE IF EXISTS `product_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL COMMENT 'Products.id - REQUIRED',
  `product_variant_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Product_variants.id - OPTIONAL for variant-specific stock',
  `warehouse_id` bigint(20) unsigned NOT NULL COMMENT 'Warehouses.id - REQUIRED',
  `shop_id` bigint(20) unsigned DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'Current stock quantity (can be negative if allowed)',
  `reserved_quantity` int(11) NOT NULL DEFAULT 0 COMMENT 'Reserved stock for orders/reservations',
  `available_quantity` int(11) GENERATED ALWAYS AS (`quantity` - `reserved_quantity`) STORED COMMENT 'Available stock (computed: quantity - reserved)',
  `minimum_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Minimum stock level dla reorder alerts',
  `maximum_stock` int(11) DEFAULT NULL COMMENT 'Maximum stock level dla warehouse capacity',
  `reorder_point` int(11) DEFAULT NULL COMMENT 'Auto-reorder trigger point',
  `reorder_quantity` int(11) DEFAULT NULL COMMENT 'Default quantity to reorder',
  `warehouse_location` text DEFAULT NULL COMMENT 'Physical locations in warehouse (semicolon-separated): A1-01;A1-02;B2-15',
  `bin_location` varchar(50) DEFAULT NULL COMMENT 'Primary bin/shelf location',
  `location_notes` text DEFAULT NULL COMMENT 'Special location instructions',
  `last_delivery_date` date DEFAULT NULL COMMENT 'Date of last stock delivery',
  `container_number` varchar(50) DEFAULT NULL COMMENT 'Container number dla import tracking',
  `delivery_status` enum('not_ordered','ordered','confirmed','in_production','ready_to_ship','shipped','in_container','in_transit','customs','delayed','receiving','received','available','cancelled') NOT NULL DEFAULT 'not_ordered' COMMENT 'Delivery workflow status',
  `expected_delivery_date` date DEFAULT NULL COMMENT 'Expected delivery date',
  `expected_quantity` int(11) DEFAULT NULL COMMENT 'Expected delivery quantity',
  `delivery_notes` text DEFAULT NULL COMMENT 'Delivery notes and special instructions',
  `average_cost` decimal(10,4) DEFAULT NULL COMMENT 'Average cost per unit (weighted average)',
  `last_cost` decimal(10,4) DEFAULT NULL COMMENT 'Cost from last delivery',
  `last_cost_update` timestamp NULL DEFAULT NULL COMMENT 'When cost was last updated',
  `erp_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                ERP systems stock mapping:\n                {\n                    "baselinker": {"stock_id": "12345", "sync_enabled": true},\n                    "subiekt_gt": {"stan_symbol": "ST001", "magazine_id": 1},\n                    "dynamics": {"item_ledger_entry": "ILE001"}\n                }\n            ' CHECK (json_valid(`erp_mapping`)),
  `movements_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Total number of stock movements',
  `last_movement_at` timestamp NULL DEFAULT NULL COMMENT 'Timestamp of last stock movement',
  `last_movement_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who made last stock movement',
  `low_stock_alert` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable low stock alerts',
  `out_of_stock_alert` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable out of stock alerts',
  `last_alert_sent` timestamp NULL DEFAULT NULL COMMENT 'When last alert was sent',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Stock record is active',
  `track_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether to track stock for this item',
  `allow_negative` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Allow negative stock levels',
  `notes` text DEFAULT NULL COMMENT 'General stock management notes',
  `created_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who created this stock record',
  `updated_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who last updated stock',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_product_variant_warehouse_shop` (`product_id`,`product_variant_id`,`warehouse_id`,`shop_id`),
  KEY `product_stock_created_by_foreign` (`created_by`),
  KEY `product_stock_updated_by_foreign` (`updated_by`),
  KEY `product_stock_last_movement_by_foreign` (`last_movement_by`),
  KEY `idx_product_warehouse` (`product_id`,`warehouse_id`),
  KEY `idx_variant_warehouse` (`product_variant_id`,`warehouse_id`),
  KEY `idx_warehouse_quantity` (`warehouse_id`,`quantity`),
  KEY `idx_warehouse_available` (`warehouse_id`,`available_quantity`),
  KEY `idx_delivery_status_date` (`delivery_status`,`expected_delivery_date`),
  KEY `idx_container_number` (`container_number`),
  KEY `idx_last_delivery` (`last_delivery_date`),
  KEY `idx_active_tracked` (`is_active`,`track_stock`),
  KEY `idx_low_stock_alert` (`minimum_stock`,`available_quantity`,`low_stock_alert`),
  KEY `idx_out_of_stock_alert` (`quantity`,`out_of_stock_alert`),
  KEY `idx_warehouse_positive_stock` (`warehouse_id`),
  KEY `idx_product_available_stock` (`product_id`),
  KEY `idx_product_warehouse_stock` (`product_id`,`warehouse_id`),
  KEY `idx_product_shop_stock` (`product_id`,`shop_id`),
  KEY `idx_shop_stock` (`shop_id`),
  CONSTRAINT `product_stock_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_stock_last_movement_by_foreign` FOREIGN KEY (`last_movement_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_stock_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_stock_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `chk_stock_reserved_logical` CHECK (`reserved_quantity` >= 0 and `reserved_quantity` <= abs(`quantity`)),
  CONSTRAINT `chk_stock_minimum_positive` CHECK (`minimum_stock` >= 0),
  CONSTRAINT `chk_stock_maximum_logical` CHECK (`maximum_stock` is null or `maximum_stock` >= `minimum_stock`),
  CONSTRAINT `chk_stock_reorder_logical` CHECK (`reorder_point` is null or `reorder_point` >= 0),
  CONSTRAINT `chk_stock_reorder_qty_positive` CHECK (`reorder_quantity` is null or `reorder_quantity` > 0),
  CONSTRAINT `chk_stock_expected_qty_positive` CHECK (`expected_quantity` is null or `expected_quantity` >= 0),
  CONSTRAINT `chk_stock_costs_positive` CHECK (`average_cost` is null or `average_cost` >= 0),
  CONSTRAINT `chk_stock_last_cost_positive` CHECK (`last_cost` is null or `last_cost` >= 0),
  CONSTRAINT `chk_stock_movements_count` CHECK (`movements_count` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Product Stock: Multi-warehouse inventory management z delivery tracking';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_types`
--

DROP TABLE IF EXISTS `product_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nazwa typu produktu',
  `slug` varchar(100) NOT NULL COMMENT 'URL-friendly slug',
  `description` text DEFAULT NULL COMMENT 'Opis typu produktu',
  `icon` varchar(100) DEFAULT NULL COMMENT 'Ikona typu (CSS class lub SVG)',
  `default_attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Domyślne atrybuty dla typu' CHECK (json_valid(`default_attributes`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Status aktywności typu',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Kolejność wyświetlania',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_types_slug_unique` (`slug`),
  KEY `idx_product_types_active_order` (`is_active`,`sort_order`),
  KEY `idx_product_types_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_variants_sku_unique` (`sku`),
  KEY `idx_variant_product_default` (`product_id`,`is_default`),
  KEY `idx_variant_active` (`is_active`),
  KEY `idx_variant_sku` (`sku`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) NOT NULL,
  `slug` varchar(500) DEFAULT NULL,
  `product_type_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(500) NOT NULL,
  `short_description` text DEFAULT NULL,
  `long_description` longtext DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `supplier_code` varchar(100) DEFAULT NULL,
  `weight` decimal(8,3) DEFAULT NULL,
  `height` decimal(8,2) DEFAULT NULL,
  `width` decimal(8,2) DEFAULT NULL,
  `length` decimal(8,2) DEFAULT NULL,
  `ean` varchar(20) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 23.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_variant_master` tinyint(1) NOT NULL DEFAULT 0,
  `available_from` datetime DEFAULT NULL COMMENT 'Product available from this date/time',
  `available_to` datetime DEFAULT NULL COMMENT 'Product available until this date/time',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether product is featured in listings',
  `meta_title` varchar(300) DEFAULT NULL,
  `meta_description` varchar(300) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `default_variant_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_is_active_product_type_index` (`is_active`),
  KEY `products_manufacturer_index` (`manufacturer`),
  KEY `products_created_at_index` (`created_at`),
  KEY `products_deleted_at_index` (`deleted_at`),
  KEY `products_supplier_code_index` (`supplier_code`),
  KEY `products_is_active_index` (`is_active`),
  KEY `products_is_active_product_type_manufacturer_index` (`is_active`,`manufacturer`),
  KEY `products_is_variant_master_is_active_index` (`is_variant_master`,`is_active`),
  KEY `products_supplier_code_is_active_index` (`supplier_code`,`is_active`),
  KEY `idx_products_product_type_id` (`product_type_id`),
  KEY `products_availability_index` (`available_from`,`available_to`),
  KEY `products_featured_index` (`is_featured`),
  KEY `idx_products_default_variant` (`default_variant_id`),
  FULLTEXT KEY `ft_products_main` (`name`,`short_description`,`manufacturer`),
  FULLTEXT KEY `ft_products_codes` (`sku`,`supplier_code`,`ean`),
  CONSTRAINT `products_default_variant_id_foreign` FOREIGN KEY (`default_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_product_type_id_foreign` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11035 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `shop_mappings`
--

DROP TABLE IF EXISTS `shop_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` bigint(20) unsigned NOT NULL,
  `mapping_type` enum('category','attribute','feature','warehouse','price_group','tax_rule') NOT NULL COMMENT 'Typ mapowania',
  `ppm_value` varchar(255) NOT NULL COMMENT 'Wartość w systemie PPM (ID lub nazwa)',
  `prestashop_id` bigint(20) unsigned NOT NULL COMMENT 'ID encji w PrestaShop',
  `prestashop_value` varchar(255) DEFAULT NULL COMMENT 'Wartość w PrestaShop (opcjonalna nazwa)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Czy mapowanie jest aktywne',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_shop_mapping` (`shop_id`,`mapping_type`,`ppm_value`),
  KEY `idx_shop_type` (`shop_id`,`mapping_type`),
  KEY `idx_type_ppm_value` (`mapping_type`,`ppm_value`),
  KEY `idx_mapping_active` (`is_active`),
  CONSTRAINT `shop_mappings_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_inheritance_logs`
--

DROP TABLE IF EXISTS `stock_inheritance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_inheritance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `shop_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned DEFAULT NULL,
  `action` enum('inherit','pull','override','sync') NOT NULL COMMENT 'Type of stock operation',
  `source` varchar(255) NOT NULL COMMENT 'Source of operation: warehouse, shop, manual, api',
  `quantity_before` int(11) DEFAULT NULL COMMENT 'Stock quantity before operation',
  `quantity_after` int(11) NOT NULL COMMENT 'Stock quantity after operation',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional operation context (user_id, api_call, etc.)' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_inheritance_logs_shop_id_foreign` (`shop_id`),
  KEY `idx_product_shop_logs` (`product_id`,`shop_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action_date` (`action`,`created_at`),
  KEY `idx_warehouse_logs` (`warehouse_id`),
  CONSTRAINT `stock_inheritance_logs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_inheritance_logs_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_inheritance_logs_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL COMMENT 'Products.id - REQUIRED',
  `product_variant_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Product_variants.id - for variant-specific movements',
  `warehouse_id` bigint(20) unsigned NOT NULL COMMENT 'Warehouses.id - REQUIRED',
  `product_stock_id` bigint(20) unsigned NOT NULL COMMENT 'Product_stock.id - reference to stock record',
  `movement_type` enum('in','out','transfer','reservation','release','adjustment','return','damage','lost','found','production','correction') NOT NULL COMMENT 'Type of stock movement',
  `quantity_before` int(11) NOT NULL COMMENT 'Stock quantity before movement',
  `quantity_change` int(11) NOT NULL COMMENT 'Quantity change (positive/negative)',
  `quantity_after` int(11) NOT NULL COMMENT 'Stock quantity after movement',
  `reserved_before` int(11) NOT NULL DEFAULT 0 COMMENT 'Reserved quantity before',
  `reserved_after` int(11) NOT NULL DEFAULT 0 COMMENT 'Reserved quantity after',
  `from_warehouse_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Source warehouse dla transfers',
  `to_warehouse_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Destination warehouse dla transfers',
  `unit_cost` decimal(10,4) DEFAULT NULL COMMENT 'Unit cost at time of movement',
  `total_cost` decimal(12,4) DEFAULT NULL COMMENT 'Total cost of movement (quantity * unit_cost)',
  `currency` varchar(3) NOT NULL DEFAULT 'PLN' COMMENT 'Currency code',
  `exchange_rate` decimal(8,4) NOT NULL DEFAULT 1.0000 COMMENT 'Exchange rate at movement time',
  `reference_type` enum('order','purchase_order','delivery','container','adjustment','return','transfer','production','inventory','correction','integration') DEFAULT NULL COMMENT 'Type of reference document',
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'Reference document ID/number',
  `reference_notes` text DEFAULT NULL COMMENT 'Additional reference information',
  `container_number` varchar(50) DEFAULT NULL COMMENT 'Container number dla import tracking',
  `delivery_date` date DEFAULT NULL COMMENT 'Actual delivery date',
  `delivery_document` varchar(100) DEFAULT NULL COMMENT 'Delivery document number',
  `location_from` varchar(100) DEFAULT NULL COMMENT 'Source location in warehouse',
  `location_to` varchar(100) DEFAULT NULL COMMENT 'Destination location in warehouse',
  `location_notes` text DEFAULT NULL COMMENT 'Location-specific notes',
  `reason` text DEFAULT NULL COMMENT 'Business reason for movement',
  `notes` text DEFAULT NULL COMMENT 'Additional notes and comments',
  `is_automatic` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Movement was created automatically',
  `is_correction` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Movement is a correction/reversal',
  `erp_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                ERP system data for this movement:\n                {\n                    "baselinker": {"document_id": "12345", "sync_status": "synced"},\n                    "subiekt_gt": {"document_number": "MM/001/2025", "magazine": "MAG01"},\n                    "dynamics": {"journal_entry": "INV001", "posting_date": "2025-09-17"}\n                }\n            ' CHECK (json_valid(`erp_data`)),
  `created_by` bigint(20) unsigned NOT NULL COMMENT 'User who created this movement',
  `approved_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who approved this movement',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'When movement was approved',
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Actual date/time of movement',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stock_movements_product_variant_id_foreign` (`product_variant_id`),
  KEY `stock_movements_product_stock_id_foreign` (`product_stock_id`),
  KEY `stock_movements_from_warehouse_id_foreign` (`from_warehouse_id`),
  KEY `stock_movements_to_warehouse_id_foreign` (`to_warehouse_id`),
  KEY `stock_movements_approved_by_foreign` (`approved_by`),
  KEY `idx_movements_product_date` (`product_id`,`movement_date`),
  KEY `idx_movements_warehouse_date` (`warehouse_id`,`movement_date`),
  KEY `idx_movements_type_date` (`movement_type`,`movement_date`),
  KEY `idx_movements_reference` (`reference_type`,`reference_id`),
  KEY `idx_movements_container` (`container_number`),
  KEY `idx_movements_delivery_date` (`delivery_date`),
  KEY `idx_movements_user_date` (`created_by`,`movement_date`),
  KEY `idx_movements_auto_date` (`is_automatic`,`movement_date`),
  KEY `idx_movements_recent` (`movement_date`),
  KEY `idx_movements_transfer` (`movement_type`,`from_warehouse_id`,`to_warehouse_id`),
  CONSTRAINT `stock_movements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_movements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_movements_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_product_stock_id_foreign` FOREIGN KEY (`product_stock_id`) REFERENCES `product_stock` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_to_warehouse_id_foreign` FOREIGN KEY (`to_warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `stock_movements_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `chk_movements_quantity_change` CHECK (`quantity_change` <> 0),
  CONSTRAINT `chk_movements_quantity_logical` CHECK (`quantity_after` = `quantity_before` + `quantity_change`),
  CONSTRAINT `chk_movements_reserved_logical` CHECK (`reserved_before` >= 0 and `reserved_after` >= 0),
  CONSTRAINT `chk_movements_transfer_warehouses` CHECK (`movement_type` <> 'transfer' or `from_warehouse_id` is not null and `to_warehouse_id` is not null and `from_warehouse_id` <> `to_warehouse_id`),
  CONSTRAINT `chk_movements_costs_positive` CHECK ((`unit_cost` is null or `unit_cost` >= 0) and (`total_cost` is null or `total_cost` >= 0)),
  CONSTRAINT `chk_movements_exchange_rate` CHECK (`exchange_rate` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Stock Movements: Complete audit trail dla warehouse operations';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stock_reservations`
--

DROP TABLE IF EXISTS `stock_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL COMMENT 'Products.id - REQUIRED',
  `product_variant_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Product_variants.id - for variant-specific reservations',
  `warehouse_id` bigint(20) unsigned NOT NULL COMMENT 'Warehouses.id - REQUIRED',
  `product_stock_id` bigint(20) unsigned NOT NULL COMMENT 'Product_stock.id - reference to stock record',
  `reservation_number` varchar(50) NOT NULL COMMENT 'Unique reservation identifier',
  `reservation_type` enum('order','quote','pre_order','allocation','production','transfer','sample','warranty','exchange','temp') NOT NULL COMMENT 'Type of reservation',
  `quantity_requested` int(11) NOT NULL COMMENT 'Originally requested quantity',
  `quantity_reserved` int(11) NOT NULL COMMENT 'Actually reserved quantity',
  `quantity_fulfilled` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantity already fulfilled/shipped',
  `quantity_remaining` int(11) GENERATED ALWAYS AS (`quantity_reserved` - `quantity_fulfilled`) STORED COMMENT 'Remaining reserved quantity',
  `reserved_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When reservation was created',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'When reservation expires (NULL = no expiry)',
  `fulfilled_at` timestamp NULL DEFAULT NULL COMMENT 'When reservation was fully fulfilled',
  `duration_minutes` int(11) DEFAULT NULL COMMENT 'Reservation duration in minutes',
  `status` enum('pending','confirmed','partial','fulfilled','expired','cancelled','on_hold','processing') NOT NULL DEFAULT 'pending' COMMENT 'Reservation status',
  `priority` int(11) NOT NULL DEFAULT 5 COMMENT 'Reservation priority (1=highest, 10=lowest)',
  `auto_release` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Automatically release when expired',
  `reference_type` enum('sales_order','quote','internal_order','production_order','transfer_request','sample_request','warranty_claim','exchange_request','manual') DEFAULT NULL COMMENT 'Type of reference document',
  `reference_id` varchar(100) DEFAULT NULL COMMENT 'Reference document ID/number',
  `reference_line_id` varchar(100) DEFAULT NULL COMMENT 'Reference line item ID',
  `reference_notes` text DEFAULT NULL COMMENT 'Reference information',
  `customer_id` varchar(50) DEFAULT NULL COMMENT 'Customer identifier',
  `customer_name` varchar(200) DEFAULT NULL COMMENT 'Customer name',
  `sales_person` varchar(100) DEFAULT NULL COMMENT 'Responsible salesperson',
  `department` varchar(100) DEFAULT NULL COMMENT 'Requesting department',
  `unit_price` decimal(10,4) DEFAULT NULL COMMENT 'Unit price at reservation',
  `total_value` decimal(12,4) DEFAULT NULL COMMENT 'Total value of reservation',
  `currency` varchar(3) NOT NULL DEFAULT 'PLN' COMMENT 'Currency code',
  `price_group` varchar(50) DEFAULT NULL COMMENT 'Price group used',
  `requested_delivery_date` date DEFAULT NULL COMMENT 'Customer requested delivery date',
  `promised_delivery_date` date DEFAULT NULL COMMENT 'Promised delivery date',
  `delivery_method` varchar(100) DEFAULT NULL COMMENT 'Delivery method',
  `delivery_address` text DEFAULT NULL COMMENT 'Delivery address',
  `delivery_notes` text DEFAULT NULL COMMENT 'Special delivery instructions',
  `reason` text DEFAULT NULL COMMENT 'Business reason for reservation',
  `special_instructions` text DEFAULT NULL COMMENT 'Special handling instructions',
  `notes` text DEFAULT NULL COMMENT 'Additional notes',
  `is_firm` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Firm reservation (cannot be auto-released)',
  `allow_partial` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow partial fulfillment',
  `notify_expiry` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Send notification before expiry',
  `erp_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                ERP system data for this reservation:\n                {\n                    "baselinker": {"order_id": "12345", "status": "waiting"},\n                    "subiekt_gt": {"document_number": "ZZ/001/2025", "status": "active"},\n                    "dynamics": {"sales_order": "SO001", "line_number": 1}\n                }\n            ' CHECK (json_valid(`erp_data`)),
  `reserved_by` bigint(20) unsigned NOT NULL COMMENT 'User who created reservation',
  `confirmed_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who confirmed reservation',
  `confirmed_at` timestamp NULL DEFAULT NULL COMMENT 'When reservation was confirmed',
  `released_by` bigint(20) unsigned DEFAULT NULL COMMENT 'User who released reservation',
  `released_at` timestamp NULL DEFAULT NULL COMMENT 'When reservation was released',
  `release_reason` text DEFAULT NULL COMMENT 'Reason for release',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reservation_number` (`reservation_number`),
  UNIQUE KEY `stock_reservations_reservation_number_unique` (`reservation_number`),
  KEY `stock_reservations_product_variant_id_foreign` (`product_variant_id`),
  KEY `stock_reservations_product_stock_id_foreign` (`product_stock_id`),
  KEY `stock_reservations_reserved_by_foreign` (`reserved_by`),
  KEY `stock_reservations_confirmed_by_foreign` (`confirmed_by`),
  KEY `stock_reservations_released_by_foreign` (`released_by`),
  KEY `idx_reservations_product_warehouse_status` (`product_id`,`warehouse_id`,`status`),
  KEY `idx_reservations_status_expiry` (`status`,`expires_at`),
  KEY `idx_reservations_reference` (`reference_type`,`reference_id`),
  KEY `idx_reservations_customer_status` (`customer_id`,`status`),
  KEY `idx_reservations_date_status` (`reserved_at`,`status`),
  KEY `idx_reservations_priority_date` (`priority`,`reserved_at`),
  KEY `idx_reservations_warehouse_status` (`warehouse_id`,`status`),
  KEY `idx_reservations_auto_expiry` (`expires_at`,`auto_release`),
  KEY `idx_reservations_expiry_notify` (`notify_expiry`,`expires_at`),
  KEY `idx_reservations_active` (`status`,`product_id`,`warehouse_id`),
  CONSTRAINT `stock_reservations_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reservations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reservations_product_stock_id_foreign` FOREIGN KEY (`product_stock_id`) REFERENCES `product_stock` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reservations_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_reservations_released_by_foreign` FOREIGN KEY (`released_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `stock_reservations_reserved_by_foreign` FOREIGN KEY (`reserved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_reservations_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  CONSTRAINT `chk_reservations_quantities_logical` CHECK (`quantity_requested` > 0 and `quantity_reserved` >= 0 and `quantity_reserved` <= `quantity_requested` and `quantity_fulfilled` >= 0 and `quantity_fulfilled` <= `quantity_reserved`),
  CONSTRAINT `chk_reservations_priority_range` CHECK (`priority` between 1 and 10),
  CONSTRAINT `chk_reservations_duration_positive` CHECK (`duration_minutes` is null or `duration_minutes` > 0),
  CONSTRAINT `chk_reservations_prices_positive` CHECK ((`unit_price` is null or `unit_price` >= 0) and (`total_value` is null or `total_value` >= 0)),
  CONSTRAINT `chk_reservations_dates_logical` CHECK (`expires_at` is null or `expires_at` >= `reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Stock Reservations: Advanced stock allocation system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_jobs`
--

DROP TABLE IF EXISTS `sync_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(100) NOT NULL COMMENT 'Unique job identifier (UUID)',
  `job_type` varchar(50) NOT NULL COMMENT 'Typ zadania (product_sync, category_sync, etc.)',
  `job_name` varchar(200) NOT NULL COMMENT 'Nazwa zadania dla użytkownika',
  `source_type` enum('ppm','prestashop','baselinker','subiekt_gt','dynamics','manual','scheduled') NOT NULL COMMENT 'Źródło synchronizacji',
  `source_id` varchar(200) DEFAULT NULL COMMENT 'ID źródła (shop_id, erp_id, user_id)',
  `target_type` enum('ppm','prestashop','baselinker','subiekt_gt','dynamics','multiple') NOT NULL COMMENT 'Cel synchronizacji',
  `target_id` varchar(200) DEFAULT NULL COMMENT 'ID celu (shop_id, erp_id)',
  `status` enum('pending','running','paused','completed','completed_with_errors','failed','cancelled','timeout') DEFAULT 'pending' COMMENT 'Status zadania',
  `total_items` int(11) NOT NULL DEFAULT 0 COMMENT 'Łączna liczba elementów do przetworzenia',
  `processed_items` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba przetworzonych elementów',
  `successful_items` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba pomyślnie przetworzonych',
  `failed_items` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba błędnych elementów',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Procent ukończenia',
  `scheduled_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy zadanie było zaplanowane',
  `started_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy zadanie zostało rozpoczęte',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy zadanie zostało ukończone',
  `duration_seconds` int(11) DEFAULT NULL COMMENT 'Czas wykonania w sekundach',
  `timeout_seconds` int(11) NOT NULL DEFAULT 3600 COMMENT 'Limit czasu wykonania',
  `job_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Konfiguracja zadania' CHECK (json_valid(`job_config`)),
  `job_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane wejściowe zadania' CHECK (json_valid(`job_data`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Filtry dla synchronizacji' CHECK (json_valid(`filters`)),
  `mapping_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Reguły mapowania danych' CHECK (json_valid(`mapping_rules`)),
  `error_message` text DEFAULT NULL COMMENT 'Główny komunikat błędu',
  `error_details` longtext DEFAULT NULL COMMENT 'Szczegółowe informacje o błędzie',
  `stack_trace` text DEFAULT NULL COMMENT 'Stack trace błędu',
  `retry_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba prób ponawiania',
  `max_retries` int(11) NOT NULL DEFAULT 3 COMMENT 'Maksymalna liczba prób',
  `next_retry_at` timestamp NULL DEFAULT NULL COMMENT 'Kiedy następna próba',
  `avg_item_processing_time` decimal(8,3) DEFAULT NULL COMMENT 'Średni czas przetwarzania elementu (ms)',
  `memory_peak_mb` int(11) DEFAULT NULL COMMENT 'Szczytowe zużycie pamięci (MB)',
  `cpu_time_seconds` decimal(10,3) DEFAULT NULL COMMENT 'Czas CPU (sekundy)',
  `api_calls_made` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba wykonanych wywołań API',
  `database_queries` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba zapytań do bazy',
  `result_summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Podsumowanie wyników' CHECK (json_valid(`result_summary`)),
  `affected_records` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Lista przetworzonych rekordów' CHECK (json_valid(`affected_records`)),
  `validation_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Błędy walidacji' CHECK (json_valid(`validation_errors`)),
  `warnings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Ostrzeżenia' CHECK (json_valid(`warnings`)),
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `trigger_type` enum('manual','scheduled','webhook','event','api') NOT NULL COMMENT 'Sposób uruchomienia zadania',
  `queue_name` varchar(100) DEFAULT NULL COMMENT 'Nazwa kolejki Laravel',
  `queue_job_id` varchar(200) DEFAULT NULL COMMENT 'ID zadania w kolejce',
  `queue_attempts` int(11) NOT NULL DEFAULT 0 COMMENT 'Liczba prób w kolejce',
  `notify_on_completion` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Powiadomienie po zakończeniu',
  `notify_on_failure` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Powiadomienie przy błędzie',
  `notification_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Kanały powiadomień' CHECK (json_valid(`notification_channels`)),
  `last_notification_sent` timestamp NULL DEFAULT NULL COMMENT 'Ostatnie wysłane powiadomienie',
  `parent_job_id` varchar(100) DEFAULT NULL COMMENT 'ID zadania nadrzędnego',
  `dependent_jobs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Lista zadań zależnych' CHECK (json_valid(`dependent_jobs`)),
  `batch_id` varchar(100) DEFAULT NULL COMMENT 'ID batch dla grupowych operacji',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sync_jobs_job_id_unique` (`job_id`),
  KEY `idx_sync_jobs_status` (`status`),
  KEY `idx_sync_jobs_type` (`job_type`),
  KEY `idx_sync_jobs_source` (`source_type`,`source_id`),
  KEY `idx_sync_jobs_target` (`target_type`,`target_id`),
  KEY `idx_sync_jobs_scheduled` (`scheduled_at`),
  KEY `idx_sync_jobs_started` (`started_at`),
  KEY `idx_sync_jobs_completed` (`completed_at`),
  KEY `idx_sync_jobs_user` (`user_id`),
  KEY `idx_sync_jobs_trigger` (`trigger_type`),
  KEY `idx_sync_jobs_batch` (`batch_id`),
  KEY `idx_sync_jobs_parent` (`parent_job_id`),
  KEY `idx_sync_jobs_retry` (`next_retry_at`),
  KEY `idx_sync_jobs_queue` (`queue_name`,`queue_job_id`),
  KEY `idx_sync_jobs_status_scheduled` (`status`,`scheduled_at`),
  KEY `idx_sync_jobs_source_status` (`source_type`,`status`),
  KEY `idx_sync_jobs_type_status_created` (`job_type`,`status`,`created_at`),
  CONSTRAINT `sync_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=769 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sync_logs`
--

DROP TABLE IF EXISTS `sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `shop_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `operation` enum('sync_product','sync_category','sync_image','sync_stock','sync_price','webhook') NOT NULL COMMENT 'Rodzaj operacji synchronizacji',
  `direction` enum('ppm_to_ps','ps_to_ppm') NOT NULL COMMENT 'Kierunek synchronizacji',
  `status` enum('started','success','error','warning') NOT NULL COMMENT 'Status operacji',
  `message` text DEFAULT NULL COMMENT 'Komunikat operacji',
  `request_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dane wysłane do PrestaShop API' CHECK (json_valid(`request_data`)),
  `response_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Odpowiedź z PrestaShop API' CHECK (json_valid(`response_data`)),
  `execution_time_ms` int(10) unsigned DEFAULT NULL COMMENT 'Czas wykonania operacji (ms)',
  `api_endpoint` varchar(500) DEFAULT NULL COMMENT 'Endpoint PrestaShop API',
  `http_status_code` smallint(5) unsigned DEFAULT NULL COMMENT 'HTTP status code odpowiedzi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Data i czas operacji',
  PRIMARY KEY (`id`),
  KEY `idx_shop_operation` (`shop_id`,`operation`),
  KEY `idx_status_created` (`status`,`created_at`),
  KEY `idx_product_created` (`product_id`,`created_at`),
  KEY `idx_operation_direction` (`operation`,`direction`),
  KEY `idx_http_status` (`http_status_code`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `sync_logs_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sync_logs_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25430 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_reports`
--

DROP TABLE IF EXISTS `system_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('usage_analytics','performance','business_intelligence','integration_performance','security_audit') NOT NULL,
  `period` enum('daily','weekly','monthly','quarterly') NOT NULL DEFAULT 'daily',
  `report_date` date NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `summary` text DEFAULT NULL,
  `status` enum('generating','completed','failed') NOT NULL DEFAULT 'generating',
  `generated_at` timestamp NULL DEFAULT NULL,
  `generated_by` bigint(20) unsigned NOT NULL,
  `generation_time_seconds` int(11) DEFAULT NULL,
  `data_points_count` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_per_period` (`type`,`period`,`report_date`),
  KEY `system_reports_generated_by_foreign` (`generated_by`),
  KEY `system_reports_type_period_report_date_index` (`type`,`period`,`report_date`),
  KEY `system_reports_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `system_reports_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` enum('string','integer','boolean','json','email','url','file') NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_key_unique` (`key`),
  KEY `system_settings_created_by_foreign` (`created_by`),
  KEY `system_settings_updated_by_foreign` (`updated_by`),
  KEY `system_settings_category_index` (`category`),
  KEY `system_settings_category_key_index` (`category`,`key`),
  KEY `system_settings_is_encrypted_index` (`is_encrypted`),
  CONSTRAINT `system_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `system_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(300) DEFAULT NULL,
  `preferred_language` varchar(5) NOT NULL DEFAULT 'pl',
  `timezone` varchar(50) NOT NULL DEFAULT 'Europe/Warsaw',
  `date_format` varchar(20) NOT NULL DEFAULT 'Y-m-d',
  `ui_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ui_preferences`)),
  `dashboard_refresh_interval` int(11) NOT NULL DEFAULT 60 COMMENT 'Dashboard auto-refresh interval in seconds',
  `dashboard_widget_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Dashboard widget preferences and layout settings' CHECK (json_valid(`dashboard_widget_preferences`)),
  `notification_settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`notification_settings`)),
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_id` varchar(100) DEFAULT NULL,
  `oauth_email` varchar(255) DEFAULT NULL,
  `oauth_access_token` text DEFAULT NULL,
  `oauth_refresh_token` text DEFAULT NULL,
  `oauth_token_expires_at` timestamp NULL DEFAULT NULL,
  `oauth_provider_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_provider_data`)),
  `oauth_avatar_url` varchar(500) DEFAULT NULL,
  `oauth_verified` tinyint(1) NOT NULL DEFAULT 0,
  `oauth_linked_at` timestamp NULL DEFAULT NULL,
  `oauth_domain` varchar(100) DEFAULT NULL,
  `oauth_last_used_at` timestamp NULL DEFAULT NULL,
  `oauth_login_attempts` int(11) NOT NULL DEFAULT 0,
  `oauth_locked_until` timestamp NULL DEFAULT NULL,
  `oauth_linked_providers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`oauth_linked_providers`)),
  `primary_auth_method` varchar(20) NOT NULL DEFAULT 'local',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `uk_users_phone` (`phone`),
  UNIQUE KEY `uk_users_oauth_provider_id` (`oauth_provider`,`oauth_id`),
  KEY `idx_users_activity` (`is_active`,`last_login_at`),
  KEY `idx_users_company` (`company`),
  KEY `idx_users_locale` (`preferred_language`,`timezone`),
  KEY `idx_users_dashboard_refresh` (`dashboard_refresh_interval`),
  KEY `idx_users_oauth_provider` (`oauth_provider`,`oauth_id`),
  KEY `idx_users_oauth_email` (`oauth_email`),
  KEY `idx_users_oauth_verification` (`oauth_verified`,`oauth_domain`),
  KEY `idx_users_oauth_activity` (`oauth_last_used_at`),
  KEY `idx_users_auth_method` (`primary_auth_method`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Extended users table with OAuth2 integration fields for Google Workspace and Microsoft Entra ID';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variant_attributes`
--

DROP TABLE IF EXISTS `variant_attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_attributes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` bigint(20) unsigned NOT NULL,
  `attribute_type_id` bigint(20) unsigned NOT NULL,
  `value_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_variant_attr` (`variant_id`,`attribute_type_id`),
  KEY `variant_attributes_attribute_type_id_foreign` (`attribute_type_id`),
  KEY `idx_variant_attr_type` (`variant_id`,`attribute_type_id`),
  KEY `idx_variant_value_id` (`value_id`),
  CONSTRAINT `variant_attributes_attribute_type_id_foreign` FOREIGN KEY (`attribute_type_id`) REFERENCES `attribute_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `variant_attributes_value_id_foreign` FOREIGN KEY (`value_id`) REFERENCES `attribute_values` (`id`) ON DELETE CASCADE,
  CONSTRAINT `variant_attributes_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variant_images`
--

DROP TABLE IF EXISTS `variant_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` bigint(20) unsigned NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `image_thumb_path` varchar(500) DEFAULT NULL,
  `is_cover` tinyint(1) NOT NULL DEFAULT 0,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_variant_img_cover` (`variant_id`,`is_cover`),
  KEY `idx_variant_img_position` (`variant_id`,`position`),
  CONSTRAINT `variant_images_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variant_prices`
--

DROP TABLE IF EXISTS `variant_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_prices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` bigint(20) unsigned NOT NULL,
  `price_group_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `price_special` decimal(10,2) DEFAULT NULL,
  `special_from` date DEFAULT NULL,
  `special_to` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_variant_price_group` (`variant_id`,`price_group_id`),
  KEY `variant_prices_price_group_id_foreign` (`price_group_id`),
  KEY `idx_variant_price_group` (`variant_id`,`price_group_id`),
  KEY `idx_variant_price_special` (`special_from`,`special_to`),
  CONSTRAINT `variant_prices_price_group_id_foreign` FOREIGN KEY (`price_group_id`) REFERENCES `price_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `variant_prices_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variant_stock`
--

DROP TABLE IF EXISTS `variant_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `variant_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `variant_id` bigint(20) unsigned NOT NULL,
  `warehouse_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reserved` int(11) NOT NULL DEFAULT 0,
  `available` int(11) GENERATED ALWAYS AS (`quantity` - `reserved`) STORED,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_variant_stock_warehouse` (`variant_id`,`warehouse_id`),
  KEY `variant_stock_warehouse_id_foreign` (`warehouse_id`),
  KEY `idx_variant_stock_warehouse` (`variant_id`,`warehouse_id`),
  KEY `idx_variant_stock_available` (`available`),
  CONSTRAINT `variant_stock_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `variant_stock_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicle_compatibility`
--

DROP TABLE IF EXISTS `vehicle_compatibility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_compatibility` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `vehicle_model_id` bigint(20) unsigned NOT NULL,
  `compatibility_attribute_id` bigint(20) unsigned DEFAULT NULL,
  `compatibility_source_id` bigint(20) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_compat_product_vehicle` (`product_id`,`vehicle_model_id`),
  KEY `vehicle_compatibility_vehicle_model_id_foreign` (`vehicle_model_id`),
  KEY `vehicle_compatibility_compatibility_source_id_foreign` (`compatibility_source_id`),
  KEY `vehicle_compatibility_verified_by_foreign` (`verified_by`),
  KEY `idx_compat_product_vehicle` (`product_id`,`vehicle_model_id`),
  KEY `idx_compat_attr` (`compatibility_attribute_id`),
  KEY `idx_compat_verified` (`verified`),
  CONSTRAINT `vehicle_compatibility_compatibility_attribute_id_foreign` FOREIGN KEY (`compatibility_attribute_id`) REFERENCES `compatibility_attributes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicle_compatibility_compatibility_source_id_foreign` FOREIGN KEY (`compatibility_source_id`) REFERENCES `compatibility_sources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_compatibility_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_compatibility_vehicle_model_id_foreign` FOREIGN KEY (`vehicle_model_id`) REFERENCES `vehicle_models` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_compatibility_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicle_compatibility_cache`
--

DROP TABLE IF EXISTS `vehicle_compatibility_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_compatibility_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `prestashop_shop_id` bigint(20) unsigned DEFAULT NULL,
  `data` text NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicle_compatibility_cache_prestashop_shop_id_foreign` (`prestashop_shop_id`),
  KEY `idx_cache_product_shop` (`product_id`,`prestashop_shop_id`),
  KEY `idx_cache_expires` (`expires_at`),
  CONSTRAINT `vehicle_compatibility_cache_prestashop_shop_id_foreign` FOREIGN KEY (`prestashop_shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vehicle_compatibility_cache_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicle_models`
--

DROP TABLE IF EXISTS `vehicle_models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `variant` varchar(100) DEFAULT NULL,
  `year_from` year(4) DEFAULT NULL,
  `year_to` year(4) DEFAULT NULL,
  `engine_code` varchar(50) DEFAULT NULL,
  `engine_capacity` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_models_sku_unique` (`sku`),
  KEY `idx_vehicle_sku` (`sku`),
  KEY `idx_vehicle_brand_model` (`brand`,`model`),
  KEY `idx_vehicle_years` (`year_from`,`year_to`),
  KEY `idx_vehicle_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `warehouses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Display name: MPPTRADE, Pitbike.pl, Cameraman, etc.',
  `code` varchar(50) NOT NULL COMMENT 'Unique code: mpptrade, pitbike, cameraman, etc.',
  `type` enum('master','shop_linked','custom') NOT NULL DEFAULT 'custom',
  `address` text DEFAULT NULL COMMENT 'Full warehouse address dla logistics',
  `city` varchar(100) DEFAULT NULL COMMENT 'City dla geographic grouping',
  `postal_code` varchar(20) DEFAULT NULL COMMENT 'Postal code dla shipping zones',
  `country` varchar(50) NOT NULL DEFAULT 'PL' COMMENT 'Country code (ISO 3166-1)',
  `shop_id` bigint(20) unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Only one warehouse can be default',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status for operations',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order in UI',
  `allow_negative_stock` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Allow negative stock levels',
  `auto_reserve_stock` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Auto reserve stock for orders',
  `default_minimum_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Default minimum stock level',
  `inherit_from_shop` tinyint(1) NOT NULL DEFAULT 0,
  `prestashop_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                PrestaShop warehouses/shops mapping:\n                {\n                    "shop_1": {"warehouse_id": 1, "name": "Main Store"},\n                    "shop_2": {"warehouse_id": 2, "name": "Pitbike Store"}\n                }\n            ' CHECK (json_valid(`prestashop_mapping`)),
  `erp_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '\n                ERP systems warehouses mapping:\n                {\n                    "baselinker": {"warehouse_id": "12345", "name": "BL Warehouse 1"},\n                    "subiekt_gt": {"magazine_symbol": "MAG01", "name": "Magazyn Główny"},\n                    "dynamics": {"location_code": "MAIN", "name": "Main Location"}\n                }\n            ' CHECK (json_valid(`erp_mapping`)),
  `contact_person` varchar(100) DEFAULT NULL COMMENT 'Warehouse manager/contact person',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Contact phone number',
  `email` varchar(100) DEFAULT NULL COMMENT 'Warehouse email address',
  `operating_hours` text DEFAULT NULL COMMENT 'Working hours information',
  `special_instructions` text DEFAULT NULL COMMENT 'Special handling instructions',
  `notes` text DEFAULT NULL COMMENT 'General warehouse notes',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `warehouses_code_unique` (`code`),
  KEY `idx_warehouses_active` (`is_active`),
  KEY `idx_warehouses_default` (`is_default`),
  KEY `idx_warehouses_sort_active` (`sort_order`,`is_active`),
  KEY `idx_warehouses_city_active` (`city`,`is_active`),
  KEY `warehouses_shop_id_foreign` (`shop_id`),
  CONSTRAINT `warehouses_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `prestashop_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_warehouses_sort` CHECK (`sort_order` >= 0),
  CONSTRAINT `chk_warehouses_min_stock` CHECK (`default_minimum_stock` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='PPM Warehouses: Multi-warehouse inventory management z integration mapping';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-19 10:35:08
