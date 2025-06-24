/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `account_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_balances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_uuid` uuid NOT NULL,
  `asset_code` varchar(20) NOT NULL,
  `balance` bigint(20) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_balances_account_uuid_asset_code_unique` (`account_uuid`,`asset_code`),
  KEY `account_balances_asset_code_index` (`asset_code`),
  KEY `account_balances_balance_index` (`balance`),
  KEY `idx_account_asset_balance` (`account_uuid`,`asset_code`,`balance`),
  CONSTRAINT `account_balances_account_uuid_foreign` FOREIGN KEY (`account_uuid`) REFERENCES `accounts` (`uuid`) ON DELETE CASCADE,
  CONSTRAINT `account_balances_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `user_uuid` uuid NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `frozen` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounts_uuid_unique` (`uuid`),
  KEY `accounts_user` (`user_uuid`),
  KEY `accounts_frozen_index` (`frozen`),
  KEY `idx_uuid_frozen` (`uuid`,`frozen`),
  KEY `idx_account_frozen_status` (`uuid`,`frozen`),
  CONSTRAINT `accounts_user` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('fiat','crypto','commodity','custom','basket') DEFAULT NULL,
  `precision` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_basket` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`code`),
  KEY `assets_type_index` (`type`),
  KEY `assets_is_active_index` (`is_active`),
  KEY `assets_is_basket_index` (`is_basket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` uuid DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `auditable_type` varchar(255) DEFAULT NULL,
  `auditable_id` varchar(255) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context like IP, user agent, etc' CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL COMMENT 'Comma-separated tags for filtering',
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_uuid_index` (`user_uuid`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_auditable_type_auditable_id_index` (`auditable_type`,`auditable_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_ip_address_index` (`ip_address`),
  KEY `audit_logs_tags_index` (`tags`),
  CONSTRAINT `audit_logs_user_uuid_foreign` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `basket_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `basket_assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('fixed','dynamic') NOT NULL DEFAULT 'fixed',
  `rebalance_frequency` enum('daily','weekly','monthly','quarterly','never') NOT NULL DEFAULT 'never',
  `last_rebalanced_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` char(36) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basket_assets_code_unique` (`code`),
  KEY `basket_assets_code_index` (`code`),
  KEY `basket_assets_is_active_index` (`is_active`),
  KEY `basket_assets_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `basket_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `basket_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `basket_asset_id` bigint(20) unsigned NOT NULL,
  `asset_code` varchar(20) NOT NULL,
  `weight` decimal(5,2) NOT NULL,
  `min_weight` decimal(5,2) DEFAULT NULL,
  `max_weight` decimal(5,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `basket_components_basket_asset_id_asset_code_unique` (`basket_asset_id`,`asset_code`),
  KEY `basket_components_basket_asset_id_index` (`basket_asset_id`),
  KEY `basket_components_asset_code_foreign` (`asset_code`),
  CONSTRAINT `basket_components_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`),
  CONSTRAINT `basket_components_basket_asset_id_foreign` FOREIGN KEY (`basket_asset_id`) REFERENCES `basket_assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `basket_performances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `basket_performances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `basket_asset_code` varchar(10) NOT NULL,
  `period_type` enum('hour','day','week','month','quarter','year','all_time') NOT NULL,
  `period_start` datetime NOT NULL,
  `period_end` datetime NOT NULL,
  `start_value` decimal(20,4) NOT NULL,
  `end_value` decimal(20,4) NOT NULL,
  `high_value` decimal(20,4) NOT NULL,
  `low_value` decimal(20,4) NOT NULL,
  `average_value` decimal(20,4) NOT NULL,
  `return_value` decimal(20,4) NOT NULL,
  `return_percentage` decimal(10,4) NOT NULL,
  `volatility` decimal(10,4) DEFAULT NULL,
  `sharpe_ratio` decimal(10,4) DEFAULT NULL,
  `max_drawdown` decimal(10,4) DEFAULT NULL,
  `value_count` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_basket_period` (`basket_asset_code`,`period_type`,`period_start`),
  KEY `basket_performances_basket_asset_code_index` (`basket_asset_code`),
  KEY `basket_performances_period_type_index` (`period_type`),
  KEY `basket_performances_basket_asset_code_period_type_index` (`basket_asset_code`,`period_type`),
  KEY `basket_performances_period_start_period_end_index` (`period_start`,`period_end`),
  CONSTRAINT `basket_performances_basket_asset_code_foreign` FOREIGN KEY (`basket_asset_code`) REFERENCES `basket_assets` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `basket_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `basket_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `basket_asset_code` varchar(20) NOT NULL,
  `value` decimal(20,8) NOT NULL,
  `calculated_at` timestamp NOT NULL,
  `component_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`component_values`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `basket_values_basket_asset_code_calculated_at_index` (`basket_asset_code`,`calculated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `component_performances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `component_performances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `basket_performance_id` bigint(20) unsigned NOT NULL,
  `asset_code` varchar(10) NOT NULL,
  `start_weight` decimal(8,4) NOT NULL,
  `end_weight` decimal(8,4) NOT NULL,
  `average_weight` decimal(8,4) NOT NULL,
  `contribution_value` decimal(20,4) NOT NULL,
  `contribution_percentage` decimal(10,4) NOT NULL,
  `return_value` decimal(20,4) NOT NULL,
  `return_percentage` decimal(10,4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `component_performances_basket_performance_id_index` (`basket_performance_id`),
  KEY `component_performances_asset_code_index` (`asset_code`),
  KEY `component_performances_basket_performance_id_asset_code_index` (`basket_performance_id`,`asset_code`),
  CONSTRAINT `component_performances_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`),
  CONSTRAINT `component_performances_basket_performance_id_foreign` FOREIGN KEY (`basket_performance_id`) REFERENCES `basket_performances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custodian_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custodian_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `account_uuid` uuid NOT NULL,
  `custodian_name` varchar(255) NOT NULL,
  `custodian_account_id` varchar(255) NOT NULL,
  `custodian_account_name` varchar(255) DEFAULT NULL,
  `status` enum('active','suspended','closed','pending') NOT NULL DEFAULT 'active',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custodian_accounts_unique` (`account_uuid`,`custodian_name`,`custodian_account_id`),
  UNIQUE KEY `custodian_accounts_uuid_unique` (`uuid`),
  KEY `custodian_accounts_account_uuid_custodian_name_index` (`account_uuid`,`custodian_name`),
  KEY `custodian_accounts_custodian_name_custodian_account_id_index` (`custodian_name`,`custodian_account_id`),
  KEY `custodian_accounts_account_uuid_is_primary_index` (`account_uuid`,`is_primary`),
  KEY `custodian_accounts_status_index` (`status`),
  CONSTRAINT `custodian_accounts_account_uuid_foreign` FOREIGN KEY (`account_uuid`) REFERENCES `accounts` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custodian_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custodian_transfers` (
  `id` varchar(255) NOT NULL,
  `from_account_uuid` uuid NOT NULL,
  `to_account_uuid` uuid NOT NULL,
  `from_custodian_account_id` bigint(20) unsigned NOT NULL,
  `to_custodian_account_id` bigint(20) unsigned NOT NULL,
  `amount` bigint(20) unsigned NOT NULL,
  `asset_code` varchar(10) NOT NULL,
  `transfer_type` enum('internal','external','bridge') NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `settlement_id` varchar(255) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `custodian_transfers_from_custodian_account_id_foreign` (`from_custodian_account_id`),
  KEY `custodian_transfers_to_custodian_account_id_foreign` (`to_custodian_account_id`),
  KEY `custodian_transfers_from_account_uuid_index` (`from_account_uuid`),
  KEY `custodian_transfers_to_account_uuid_index` (`to_account_uuid`),
  KEY `custodian_transfers_status_index` (`status`),
  KEY `custodian_transfers_transfer_type_index` (`transfer_type`),
  KEY `custodian_transfers_asset_code_status_index` (`asset_code`,`status`),
  KEY `custodian_transfers_created_at_index` (`created_at`),
  KEY `custodian_transfers_settlement_id_index` (`settlement_id`),
  CONSTRAINT `custodian_transfers_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`),
  CONSTRAINT `custodian_transfers_from_account_uuid_foreign` FOREIGN KEY (`from_account_uuid`) REFERENCES `accounts` (`uuid`),
  CONSTRAINT `custodian_transfers_from_custodian_account_id_foreign` FOREIGN KEY (`from_custodian_account_id`) REFERENCES `custodian_accounts` (`id`),
  CONSTRAINT `custodian_transfers_settlement_id_foreign` FOREIGN KEY (`settlement_id`) REFERENCES `settlements` (`id`),
  CONSTRAINT `custodian_transfers_to_account_uuid_foreign` FOREIGN KEY (`to_account_uuid`) REFERENCES `accounts` (`uuid`),
  CONSTRAINT `custodian_transfers_to_custodian_account_id_foreign` FOREIGN KEY (`to_custodian_account_id`) REFERENCES `custodian_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custodian_webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `custodian_webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `custodian_name` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `event_id` varchar(255) DEFAULT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `signature` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','processed','failed','ignored') NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `custodian_account_id` uuid DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custodian_webhooks_uuid_unique` (`uuid`),
  UNIQUE KEY `custodian_webhooks_custodian_name_event_id_unique` (`custodian_name`,`event_id`),
  KEY `custodian_webhooks_custodian_name_event_type_index` (`custodian_name`,`event_type`),
  KEY `custodian_webhooks_status_index` (`status`),
  KEY `custodian_webhooks_processed_at_index` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exchange_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exchange_rates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `from_asset_code` varchar(20) NOT NULL,
  `to_asset_code` varchar(20) NOT NULL,
  `rate` decimal(20,10) NOT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'manual',
  `valid_at` timestamp NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exchange_rates_from_asset_code_to_asset_code_valid_at_unique` (`from_asset_code`,`to_asset_code`,`valid_at`),
  KEY `exchange_rates_from_asset_code_to_asset_code_index` (`from_asset_code`,`to_asset_code`),
  KEY `exchange_rates_valid_at_expires_at_index` (`valid_at`,`expires_at`),
  KEY `exchange_rates_is_active_valid_at_index` (`is_active`,`valid_at`),
  KEY `exchange_rates_source_index` (`source`),
  KEY `exchange_rates_to_asset_code_foreign` (`to_asset_code`),
  CONSTRAINT `exchange_rates_from_asset_code_foreign` FOREIGN KEY (`from_asset_code`) REFERENCES `assets` (`code`),
  CONSTRAINT `exchange_rates_to_asset_code_foreign` FOREIGN KEY (`to_asset_code`) REFERENCES `assets` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `exports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_disk` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `exporter` varchar(255) NOT NULL,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `total_rows` int(10) unsigned NOT NULL,
  `successful_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exports_user_id_foreign` (`user_id`),
  CONSTRAINT `exports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_import_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_import_rows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `import_id` bigint(20) unsigned NOT NULL,
  `validation_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `failed_import_rows_import_id_foreign` (`import_id`),
  CONSTRAINT `failed_import_rows_import_id_foreign` FOREIGN KEY (`import_id`) REFERENCES `imports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `false`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `false` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `features_name_scope_unique` (`name`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `completed_at` timestamp NULL DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `importer` varchar(255) NOT NULL,
  `processed_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `total_rows` int(10) unsigned NOT NULL,
  `successful_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `imports_user_id_foreign` (`user_id`),
  CONSTRAINT `imports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kyc_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_documents` (
  `id` uuid NOT NULL,
  `user_uuid` uuid NOT NULL,
  `document_type` enum('passport','national_id','drivers_license','residence_permit','utility_bill','bank_statement','selfie','proof_of_income','other') NOT NULL,
  `status` enum('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `file_hash` varchar(255) DEFAULT NULL COMMENT 'SHA-256 hash for integrity',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional document metadata' CHECK (json_valid(`metadata`)),
  `rejection_reason` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL COMMENT 'Admin user who verified',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kyc_documents_document_type_index` (`document_type`),
  KEY `kyc_documents_status_index` (`status`),
  KEY `kyc_documents_user_uuid_status_index` (`user_uuid`,`status`),
  CONSTRAINT `kyc_documents_user_uuid_foreign` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledgers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledgers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid DEFAULT NULL,
  `aggregate_version` bigint(20) unsigned DEFAULT NULL,
  `event_version` int(11) NOT NULL DEFAULT 1,
  `event_class` varchar(255) NOT NULL,
  `event_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_properties`)),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ledgers_aggregate_uuid_version` (`aggregate_uuid`,`aggregate_version`),
  KEY `ledgers_event_class_index` (`event_class`),
  KEY `ledgers_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` uuid NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `required_participation` int(11) DEFAULT NULL,
  `voting_power_strategy` varchar(100) NOT NULL DEFAULT 'one_user_one_vote',
  `execution_workflow` varchar(255) DEFAULT NULL,
  `created_by` uuid NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `polls_uuid_unique` (`uuid`),
  KEY `polls_status_start_date_end_date_index` (`status`,`start_date`,`end_date`),
  KEY `polls_created_by_index` (`created_by`),
  KEY `polls_voting_power_strategy_index` (`voting_power_strategy`),
  CONSTRAINT `polls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_aggregates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bucket` int(10) unsigned NOT NULL,
  `period` mediumint(8) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `aggregate` varchar(255) NOT NULL,
  `value` decimal(20,2) NOT NULL,
  `count` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_aggregates_bucket_period_type_aggregate_key_hash_unique` (`bucket`,`period`,`type`,`aggregate`,`key_hash`),
  KEY `pulse_aggregates_period_bucket_index` (`period`,`bucket`),
  KEY `pulse_aggregates_type_index` (`type`),
  KEY `pulse_aggregates_period_type_aggregate_bucket_index` (`period`,`type`,`aggregate`,`bucket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pulse_entries_timestamp_index` (`timestamp`),
  KEY `pulse_entries_type_index` (`type`),
  KEY `pulse_entries_key_hash_index` (`key_hash`),
  KEY `pulse_entries_timestamp_type_key_hash_value_index` (`timestamp`,`type`,`key_hash`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pulse_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_values` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `key` mediumtext NOT NULL,
  `key_hash` binary(16) GENERATED ALWAYS AS (unhex(md5(`key`))) VIRTUAL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pulse_values_type_key_hash_unique` (`type`,`key_hash`),
  KEY `pulse_values_timestamp_index` (`timestamp`),
  KEY `pulse_values_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
DROP TABLE IF EXISTS `settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settlements` (
  `id` varchar(255) NOT NULL,
  `type` enum('realtime','batch','net') NOT NULL,
  `from_custodian` varchar(50) NOT NULL,
  `to_custodian` varchar(50) NOT NULL,
  `asset_code` varchar(10) NOT NULL,
  `gross_amount` bigint(20) unsigned NOT NULL,
  `net_amount` bigint(20) unsigned NOT NULL,
  `transfer_count` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('pending','processing','completed','failed') NOT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settlements_status_index` (`status`),
  KEY `settlements_type_index` (`type`),
  KEY `settlements_from_custodian_to_custodian_index` (`from_custodian`,`to_custodian`),
  KEY `settlements_asset_code_status_index` (`asset_code`,`status`),
  KEY `settlements_created_at_index` (`created_at`),
  CONSTRAINT `settlements_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid NOT NULL,
  `aggregate_version` bigint(20) unsigned NOT NULL,
  `state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`state`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `snapshots_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stablecoin_collateral_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stablecoin_collateral_positions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `account_uuid` uuid NOT NULL,
  `stablecoin_code` varchar(255) NOT NULL,
  `collateral_asset_code` varchar(255) NOT NULL,
  `collateral_amount` bigint(20) NOT NULL,
  `debt_amount` bigint(20) NOT NULL,
  `collateral_ratio` decimal(8,4) NOT NULL,
  `liquidation_price` decimal(20,8) DEFAULT NULL,
  `interest_accrued` bigint(20) NOT NULL DEFAULT 0,
  `status` enum('active','liquidated','closed') NOT NULL DEFAULT 'active',
  `last_interaction_at` timestamp NULL DEFAULT NULL,
  `liquidated_at` timestamp NULL DEFAULT NULL,
  `auto_liquidation_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `stop_loss_ratio` decimal(8,4) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scp_account_stablecoin_unique` (`account_uuid`,`stablecoin_code`),
  UNIQUE KEY `stablecoin_collateral_positions_uuid_unique` (`uuid`),
  KEY `scp_account_stablecoin_idx` (`account_uuid`,`stablecoin_code`),
  KEY `scp_stablecoin_status_idx` (`stablecoin_code`,`status`),
  KEY `scp_collateral_status_idx` (`collateral_asset_code`,`status`),
  KEY `scp_collateral_ratio_idx` (`collateral_ratio`),
  KEY `scp_liquidation_price_idx` (`liquidation_price`),
  CONSTRAINT `stablecoin_collateral_positions_account_uuid_foreign` FOREIGN KEY (`account_uuid`) REFERENCES `accounts` (`uuid`),
  CONSTRAINT `stablecoin_collateral_positions_stablecoin_code_foreign` FOREIGN KEY (`stablecoin_code`) REFERENCES `stablecoins` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stablecoins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stablecoins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `peg_asset_code` varchar(255) NOT NULL,
  `peg_ratio` decimal(20,8) NOT NULL DEFAULT 1.00000000,
  `target_price` decimal(20,8) NOT NULL,
  `stability_mechanism` enum('collateralized','algorithmic','hybrid') NOT NULL DEFAULT 'collateralized',
  `collateral_ratio` decimal(8,4) NOT NULL DEFAULT 1.5000,
  `min_collateral_ratio` decimal(8,4) NOT NULL DEFAULT 1.2000,
  `liquidation_penalty` decimal(8,4) NOT NULL DEFAULT 0.0500,
  `total_supply` bigint(20) NOT NULL DEFAULT 0,
  `max_supply` bigint(20) DEFAULT NULL,
  `total_collateral_value` bigint(20) NOT NULL DEFAULT 0,
  `mint_fee` decimal(8,6) NOT NULL DEFAULT 0.001000,
  `burn_fee` decimal(8,6) NOT NULL DEFAULT 0.001000,
  `precision` int(11) NOT NULL DEFAULT 8,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `minting_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `burning_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stablecoins_code_unique` (`code`),
  KEY `stablecoins_peg_asset_code_index` (`peg_asset_code`),
  KEY `stablecoins_is_active_minting_enabled_index` (`is_active`,`minting_enabled`),
  KEY `stablecoins_stability_mechanism_index` (`stability_mechanism`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stored_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `stored_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid DEFAULT NULL,
  `aggregate_version` bigint(20) unsigned DEFAULT NULL,
  `event_version` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `event_class` varchar(255) NOT NULL,
  `event_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_properties`)),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stored_events_aggregate_uuid_aggregate_version_unique` (`aggregate_uuid`,`aggregate_version`),
  KEY `stored_events_event_class_index` (`event_class`),
  KEY `stored_events_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscription_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `stripe_id` varchar(255) NOT NULL,
  `stripe_product` varchar(255) NOT NULL,
  `stripe_price` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_items_stripe_id_unique` (`stripe_id`),
  KEY `subscription_items_subscription_id_stripe_price_index` (`subscription_id`,`stripe_price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `stripe_id` varchar(255) NOT NULL,
  `stripe_status` varchar(255) NOT NULL,
  `stripe_price` varchar(255) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_id_unique` (`stripe_id`),
  KEY `subscriptions_user_id_stripe_status_index` (`user_id`,`stripe_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `team_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_invitations_team_id_email_unique` (`team_id`,`email`),
  CONSTRAINT `team_invitations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `team_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `team_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `team_user_team_id_user_id_unique` (`team_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `personal_team` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teams_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `batch_id` uuid NOT NULL,
  `family_hash` varchar(255) DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT 1,
  `type` varchar(20) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` uuid NOT NULL,
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_projections` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `account_uuid` uuid NOT NULL,
  `asset_code` varchar(10) NOT NULL DEFAULT 'USD',
  `amount` bigint(20) NOT NULL,
  `type` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `hash` varchar(128) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `related_account_uuid` uuid DEFAULT NULL,
  `transaction_group_uuid` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_projections_uuid_unique` (`uuid`),
  KEY `transaction_projections_account_uuid_created_at_index` (`account_uuid`,`created_at`),
  KEY `transaction_projections_account_uuid_asset_code_index` (`account_uuid`,`asset_code`),
  KEY `transaction_projections_type_created_at_index` (`type`,`created_at`),
  KEY `transaction_projections_hash_index` (`hash`),
  KEY `transaction_projections_transaction_group_uuid_index` (`transaction_group_uuid`),
  KEY `transaction_projections_asset_code_foreign` (`asset_code`),
  KEY `transaction_projections_account_uuid_index` (`account_uuid`),
  CONSTRAINT `transaction_projections_account_uuid_foreign` FOREIGN KEY (`account_uuid`) REFERENCES `accounts` (`uuid`) ON DELETE CASCADE,
  CONSTRAINT `transaction_projections_asset_code_foreign` FOREIGN KEY (`asset_code`) REFERENCES `assets` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transaction_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid NOT NULL,
  `aggregate_version` int(10) unsigned NOT NULL,
  `state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`state`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_snapshots_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid DEFAULT NULL,
  `aggregate_version` bigint(20) unsigned DEFAULT NULL,
  `event_version` int(11) NOT NULL DEFAULT 1,
  `event_class` varchar(255) NOT NULL,
  `event_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_properties`)),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_aggregate_uuid_version` (`aggregate_uuid`,`aggregate_version`),
  KEY `transactions_event_class_index` (`event_class`),
  KEY `transactions_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transfer_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid NOT NULL,
  `aggregate_version` int(10) unsigned NOT NULL,
  `state` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`state`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfer_snapshots_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `aggregate_uuid` uuid DEFAULT NULL,
  `aggregate_version` bigint(20) unsigned DEFAULT NULL,
  `event_version` int(11) NOT NULL DEFAULT 1,
  `event_class` varchar(255) NOT NULL,
  `event_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`event_properties`)),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfers_aggregate_uuid_version` (`aggregate_uuid`,`aggregate_version`),
  KEY `transfers_event_class_index` (`event_class`),
  KEY `transfers_aggregate_uuid_index` (`aggregate_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `turnovers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `turnovers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_uuid` uuid NOT NULL,
  `date` date NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_date` (`account_uuid`,`date`),
  CONSTRAINT `turnovers_account` FOREIGN KEY (`account_uuid`) REFERENCES `accounts` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_bank_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_bank_preferences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_uuid` uuid NOT NULL,
  `bank_code` varchar(50) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `allocation_percentage` decimal(5,2) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','pending','suspended') NOT NULL DEFAULT 'pending',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_bank_preferences_user_uuid_bank_code_unique` (`user_uuid`,`bank_code`),
  KEY `user_bank_preferences_user_uuid_index` (`user_uuid`),
  KEY `user_bank_preferences_user_uuid_bank_code_index` (`user_uuid`,`bank_code`),
  KEY `user_bank_preferences_status_index` (`status`),
  CONSTRAINT `user_bank_preferences_user_uuid_foreign` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` uuid NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `kyc_status` enum('not_started','pending','in_review','approved','rejected','expired') NOT NULL DEFAULT 'not_started',
  `kyc_submitted_at` timestamp NULL DEFAULT NULL,
  `kyc_approved_at` timestamp NULL DEFAULT NULL,
  `kyc_expires_at` timestamp NULL DEFAULT NULL,
  `kyc_level` enum('basic','enhanced','full') NOT NULL DEFAULT 'basic',
  `pep_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Politically Exposed Person',
  `risk_rating` varchar(255) DEFAULT NULL COMMENT 'low, medium, high',
  `kyc_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Encrypted KYC data' CHECK (json_valid(`kyc_data`)),
  `privacy_policy_accepted_at` timestamp NULL DEFAULT NULL,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `marketing_consent_at` timestamp NULL DEFAULT NULL,
  `data_retention_consent` tinyint(1) NOT NULL DEFAULT 0,
  `has_completed_onboarding` tinyint(1) NOT NULL DEFAULT 0,
  `onboarding_completed_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `current_team_id` bigint(20) unsigned DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_id` varchar(255) DEFAULT NULL,
  `pm_type` varchar(255) DEFAULT NULL,
  `pm_last_four` varchar(4) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_uuid_unique` (`uuid`),
  KEY `users_stripe_id_index` (`stripe_id`),
  KEY `users_kyc_status_index` (`kyc_status`),
  KEY `users_kyc_level_index` (`kyc_level`),
  KEY `users_risk_rating_index` (`risk_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` bigint(20) unsigned NOT NULL,
  `user_uuid` uuid NOT NULL,
  `selected_options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`selected_options`)),
  `voting_power` int(11) NOT NULL DEFAULT 1,
  `voted_at` timestamp NOT NULL,
  `signature` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_poll_vote` (`poll_id`,`user_uuid`),
  KEY `votes_poll_id_voted_at_index` (`poll_id`,`voted_at`),
  KEY `votes_user_uuid_index` (`user_uuid`),
  KEY `votes_voting_power_index` (`voting_power`),
  CONSTRAINT `votes_poll_id_foreign` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `votes_user_uuid_foreign` FOREIGN KEY (`user_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `uuid` uuid NOT NULL,
  `webhook_uuid` uuid NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `response_status` int(11) DEFAULT NULL,
  `response_body` text DEFAULT NULL,
  `response_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_headers`)),
  `duration_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `webhook_deliveries_webhook_uuid_status_index` (`webhook_uuid`,`status`),
  KEY `webhook_deliveries_event_type_index` (`event_type`),
  KEY `webhook_deliveries_created_at_index` (`created_at`),
  CONSTRAINT `webhook_deliveries_webhook_uuid_foreign` FOREIGN KEY (`webhook_uuid`) REFERENCES `webhooks` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `uuid` uuid NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`events`)),
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`headers`)),
  `secret` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `retry_attempts` int(11) NOT NULL DEFAULT 3,
  `timeout_seconds` int(11) NOT NULL DEFAULT 30,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `last_success_at` timestamp NULL DEFAULT NULL,
  `last_failure_at` timestamp NULL DEFAULT NULL,
  `consecutive_failures` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  KEY `webhooks_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_exceptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_exceptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stored_workflow_id` bigint(20) unsigned NOT NULL,
  `class` text NOT NULL,
  `exception` text NOT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_exceptions_stored_workflow_id_index` (`stored_workflow_id`),
  CONSTRAINT `workflow_exceptions_stored_workflow_id_foreign` FOREIGN KEY (`stored_workflow_id`) REFERENCES `workflows` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stored_workflow_id` bigint(20) unsigned NOT NULL,
  `index` bigint(20) unsigned NOT NULL,
  `now` timestamp(6) NOT NULL,
  `class` text NOT NULL,
  `result` text DEFAULT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_logs_stored_workflow_id_index_unique` (`stored_workflow_id`,`index`),
  KEY `workflow_logs_stored_workflow_id_index` (`stored_workflow_id`),
  CONSTRAINT `workflow_logs_stored_workflow_id_foreign` FOREIGN KEY (`stored_workflow_id`) REFERENCES `workflows` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_relationships` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_workflow_id` bigint(20) unsigned DEFAULT NULL,
  `parent_index` bigint(20) unsigned NOT NULL,
  `parent_now` timestamp NOT NULL,
  `child_workflow_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_relationships_parent_workflow_id_index` (`parent_workflow_id`),
  KEY `workflow_relationships_child_workflow_id_index` (`child_workflow_id`),
  CONSTRAINT `workflow_relationships_child_workflow_id_foreign` FOREIGN KEY (`child_workflow_id`) REFERENCES `workflows` (`id`),
  CONSTRAINT `workflow_relationships_parent_workflow_id_foreign` FOREIGN KEY (`parent_workflow_id`) REFERENCES `workflows` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_signals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stored_workflow_id` bigint(20) unsigned NOT NULL,
  `method` text NOT NULL,
  `arguments` text DEFAULT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_signals_stored_workflow_id_created_at_index` (`stored_workflow_id`,`created_at`),
  KEY `workflow_signals_stored_workflow_id_index` (`stored_workflow_id`),
  CONSTRAINT `workflow_signals_stored_workflow_id_foreign` FOREIGN KEY (`stored_workflow_id`) REFERENCES `workflows` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_timers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_timers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stored_workflow_id` bigint(20) unsigned NOT NULL,
  `index` int(11) NOT NULL,
  `stop_at` timestamp(6) NOT NULL,
  `created_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_timers_stored_workflow_id_created_at_index` (`stored_workflow_id`,`created_at`),
  KEY `workflow_timers_stored_workflow_id_index` (`stored_workflow_id`),
  CONSTRAINT `workflow_timers_stored_workflow_id_foreign` FOREIGN KEY (`stored_workflow_id`) REFERENCES `workflows` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflows` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `class` text NOT NULL,
  `arguments` text DEFAULT NULL,
  `output` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp(6) NULL DEFAULT NULL,
  `updated_at` timestamp(6) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflows_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2022_01_01_000000_create_workflows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2022_01_01_000001_create_workflow_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2022_01_01_000002_create_workflow_signals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2022_01_01_000003_create_workflow_timers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2022_01_01_000004_create_workflow_exceptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2022_01_01_000005_create_workflow_relationships_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_08_14_164415_add_two_factor_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_08_14_164423_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_08_14_164423_create_teams_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_08_14_164424_create_team_user_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_08_14_164425_create_team_invitations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_08_14_183849_create_customer_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_08_14_183850_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_08_14_183851_create_subscription_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2024_08_14_220424_create_oauth_auth_codes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_08_14_220425_create_oauth_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_08_14_220426_create_oauth_refresh_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_08_14_220427_create_oauth_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_08_14_220428_create_oauth_personal_access_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_08_14_220941_create_stored_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_08_14_220942_create_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2024_08_14_224008_create_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2024_08_14_224216_create_pulse_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2024_08_14_232158_create_telescope_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2024_08_21_203052_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2024_08_21_203644_add_uuid_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2024_08_27_164259_create_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2024_08_28_152655_create_transactions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2024_08_28_154128_create_transaction_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2024_08_28_154719_create_ledgers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2024_09_09_185036_create_turnovers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2024_09_24_102152_create_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2024_09_24_103210_create_transfer_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_01_14_000001_create_webhooks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_01_14_000002_create_webhook_deliveries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_01_16_000001_add_provider_columns_to_exchange_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_06_12_204401_add_frozen_column_to_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_06_14_120541_add_debit_credit_fields_to_turnovers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_06_14_230417_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_06_14_230424_create_imports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_06_14_230425_create_exports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_06_14_230426_create_failed_import_rows_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_06_15_183648_create_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_06_15_183654_create_account_balances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_06_15_195918_create_exchange_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_06_16_140029_create_polls_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_06_16_140046_create_votes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_06_16_232847_create_basket_assets_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_06_17_083000_add_basket_type_to_assets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_06_17_205334_create_custodian_accounts_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_06_17_213344_create_custodian_webhooks_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_06_17_215515_create_stablecoins_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_06_17_215546_create_stablecoin_collateral_positions_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_06_18_162835_increase_asset_code_length',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_06_19_170313_add_chf_jpy_assets',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_06_19_170620_create_user_bank_preferences_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_06_20_000838_add_additional_banks_to_user_preferences',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_06_20_235735_add_kyc_fields_to_users_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_06_20_235800_create_kyc_documents_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_06_20_235821_create_audit_logs_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_06_21_021310_create_custodian_transfers_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_06_21_022056_create_settlements_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_06_22_000001_add_performance_indexes',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_06_21_000001_create_transaction_projections_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_06_22_215752_create_basket_performance_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_06_23_095846_add_onboarding_completed_to_users_table',16);
