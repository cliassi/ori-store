/*
SQLyog Community v12.4.0 (64 bit)
MySQL - 10.2.8-MariaDB : Database - store
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `_bank` */

CREATE TABLE `_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `b_name` varchar(128) NOT NULL,
  `b_swift` varchar(8) DEFAULT NULL,
  `b_priority` tinyint(3) unsigned NOT NULL DEFAULT 50,
  `b_remarks` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `b_name` (`b_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `_icons` */

CREATE TABLE `_icons` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme` varchar(64) NOT NULL,
  `icon` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=767 DEFAULT CHARSET=latin1;

/*Table structure for table `account_category` */

CREATE TABLE `account_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` varchar(8) NOT NULL,
  `description` text DEFAULT NULL,
  `parent` int(10) unsigned NOT NULL,
  `group` tinyint(1) unsigned NOT NULL,
  `group_2` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;

/*Table structure for table `accounts` */

CREATE TABLE `accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `code` varchar(8) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `account_category` int(10) unsigned NOT NULL DEFAULT 6,
  `currency` varchar(32) NOT NULL DEFAULT 'MYR',
  `opening_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `group` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_category` (`account_category`),
  CONSTRAINT `account_category` FOREIGN KEY (`account_category`) REFERENCES `account_category` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=179 DEFAULT CHARSET=latin1;

/*Table structure for table `area` */

CREATE TABLE `area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `bank` */

CREATE TABLE `bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `account_name` varchar(128) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `opening_balance` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `show_in_expense` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `show_in_carwash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `show_in_hotel` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account` (`account_number`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

/*Table structure for table `bank_transaction` */

CREATE TABLE `bank_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `account` int(10) unsigned NOT NULL,
  `transactions` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `bank_account` (`account`),
  KEY `bt_entry_by` (`entry_by`),
  KEY `bt_modify_by` (`modify_by`),
  CONSTRAINT `bank_account` FOREIGN KEY (`account`) REFERENCES `bank` (`id`),
  CONSTRAINT `bt_entry_by` FOREIGN KEY (`entry_by`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `bt_modify_by` FOREIGN KEY (`modify_by`) REFERENCES `sys_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `bank_transaction_item` */

CREATE TABLE `bank_transaction_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_transaction` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `credit` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `debit` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `worker` int(10) unsigned DEFAULT NULL,
  `passport` varchar(16) DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `company_name` varchar(128) DEFAULT NULL,
  `expense` int(10) unsigned DEFAULT NULL,
  `particulars` text DEFAULT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `trash` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `removed_by` int(10) unsigned DEFAULT NULL,
  `removed_at` datetime DEFAULT NULL,
  `checked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hotel_expense` int(10) unsigned DEFAULT NULL,
  `expense_entry` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_transaction` (`bank_transaction`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `bd_handover` */

CREATE TABLE `bd_handover` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `particulars` text DEFAULT NULL,
  `payment_method` enum('Cash','Bank') NOT NULL DEFAULT 'Cash',
  `amount` decimal(10,2) DEFAULT NULL,
  `bank_amount` decimal(10,2) unsigned DEFAULT NULL,
  `account` int(10) unsigned NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_bd_handover_branch_created` (`branch_id`,`created_at`),
  KEY `idx_bd_handover_branch_date` (`branch_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=473 DEFAULT CHARSET=latin1;

/*Table structure for table `branch` */

CREATE TABLE `branch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` tinyint(4) NOT NULL DEFAULT 1,
  `division_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`,`division_id`),
  KEY `division_id` (`division_id`),
  KEY `status` (`status`),
  CONSTRAINT `branch_ibfk_1` FOREIGN KEY (`division_id`) REFERENCES `division` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `city` */

CREATE TABLE `city` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `state` varchar(64) DEFAULT NULL,
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=latin1;

/*Table structure for table `collection` */

CREATE TABLE `collection` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `payment_method` enum('Bank','Cash','Credit') NOT NULL,
  `payment_remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `approved` tinyint(4) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  `payment_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_collection_branch_method_created` (`branch_id`,`payment_method`,`created_at`),
  KEY `idx_collection_branch_method_date` (`branch_id`,`payment_method`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=5839 DEFAULT CHARSET=latin1;

/*Table structure for table `company` */

CREATE TABLE `company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `autobill` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `bill_amount` decimal(10,2) DEFAULT NULL,
  `bill_description` text DEFAULT NULL,
  `reg_no` varchar(20) NOT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `director` varchar(128) DEFAULT NULL,
  `director_phone` varchar(20) DEFAULT NULL,
  `contact_person` varchar(128) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `address1` text DEFAULT NULL,
  `address2` text DEFAULT NULL,
  `city` varchar(128) DEFAULT NULL,
  `state` varchar(128) DEFAULT NULL,
  `post` varchar(8) DEFAULT NULL,
  `country` int(10) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `fax` varchar(20) DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `website` varchar(128) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `category` varchar(128) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `seq` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `company_country` (`country`),
  KEY `company_account` (`account_id`),
  CONSTRAINT `company_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `company_country` FOREIGN KEY (`country`) REFERENCES `country` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=latin1;

/*Table structure for table `customer` */

CREATE TABLE `customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(128) DEFAULT NULL,
  `password` varchar(128) DEFAULT NULL,
  `code` varchar(12) DEFAULT NULL,
  `company` varchar(128) NOT NULL,
  `contact` varchar(128) NOT NULL,
  `mobile` varchar(32) NOT NULL,
  `city` varchar(64) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(64) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `location` text DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_branch` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=467 DEFAULT CHARSET=latin1;

/*Table structure for table `customer_order` */

CREATE TABLE `customer_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_date` date DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `delivered_by` int(10) unsigned DEFAULT NULL,
  `delivery_staff` varchar(128) DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `salesman_id` int(10) unsigned DEFAULT NULL,
  `salesman` varchar(128) DEFAULT NULL,
  `incentive` decimal(4,2) unsigned DEFAULT 5.00,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=161 DEFAULT CHARSET=latin1;

/*Table structure for table `customer_order_item` */

CREATE TABLE `customer_order_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_variance_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` decimal(10,2) unsigned DEFAULT NULL,
  `cost` decimal(10,2) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) unsigned DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivered` int(10) unsigned NOT NULL DEFAULT 0,
  `delivered_by` varchar(128) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `delivery_staff` varchar(128) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `price_updated_by` int(10) unsigned DEFAULT NULL,
  `price_updated_at` datetime DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  `collected_at` datetime DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assigned_by` int(10) unsigned DEFAULT NULL,
  `assigned_to` varchar(128) DEFAULT NULL,
  `assigned_to_name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=331 DEFAULT CHARSET=latin1;

/*Table structure for table `customer_product_variance` */

CREATE TABLE `customer_product_variance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `product_variance_id` int(10) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk` (`customer_id`,`product_variance_id`)
) ENGINE=MyISAM AUTO_INCREMENT=119 DEFAULT CHARSET=latin1;

/*Table structure for table `customer_remarks` */

CREATE TABLE `customer_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('High','Normal','Low') NOT NULL DEFAULT 'Normal',
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `entry_by` int(10) unsigned NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1706 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_bank` */

CREATE TABLE `cw_bank` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `particulars` text NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `company` int(10) unsigned NOT NULL,
  `cash_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cw_bank_branch_entry_time` (`branch_id`,`entry_time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `cw_cash` */

CREATE TABLE `cw_cash` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `particulars` text NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `company` int(10) unsigned NOT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cw_cash_branch_entry_time` (`branch_id`,`entry_time`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_cash_withdraw` */

CREATE TABLE `cw_cash_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `particulars` text DEFAULT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `account` int(10) DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL,
  `company` int(10) unsigned NOT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_cw_withdraw_branch_entry_time` (`branch_id`,`entry_time`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_company` */

CREATE TABLE `cw_company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `trash` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_company_user` */

CREATE TABLE `cw_company_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `site_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ucu` (`user_id`,`site_id`),
  KEY `site_u` (`site_id`),
  CONSTRAINT `cuuser` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `site_u` FOREIGN KEY (`site_id`) REFERENCES `cw_company` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_customer` */

CREATE TABLE `cw_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `brand` varchar(128) DEFAULT NULL,
  `model` varchar(128) DEFAULT NULL,
  `number` varchar(16) NOT NULL,
  `roadtax` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `photo_file` varchar(128) DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `isin` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `intime` time DEFAULT NULL,
  `outtime` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carnumber_branch` (`number`,`company`)
) ENGINE=InnoDB AUTO_INCREMENT=1953 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_handover` */

CREATE TABLE `cw_handover` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `particulars` text DEFAULT NULL,
  `payment_method` enum('Cash','Bank') NOT NULL DEFAULT 'Cash',
  `amount` decimal(10,2) DEFAULT NULL,
  `bank_amount` decimal(10,2) unsigned DEFAULT NULL,
  `account` int(10) unsigned NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `company` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=277 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_outlet` */

CREATE TABLE `cw_outlet` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `particulars` text NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `company` int(10) unsigned NOT NULL,
  `cash_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_package` */

CREATE TABLE `cw_package` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `cw_package_service` */

CREATE TABLE `cw_package_service` (
  `package_id` int(10) unsigned NOT NULL,
  `service_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`package_id`,`service_id`),
  KEY `service` (`service_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `cw_payment` */

CREATE TABLE `cw_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `particulars` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `discount` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `paid` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `company` int(10) unsigned NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `approve_by` int(10) unsigned DEFAULT NULL,
  `approve_time` datetime DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `customer` (`customer_id`),
  CONSTRAINT `cw_customer` FOREIGN KEY (`customer_id`) REFERENCES `cw_customer` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1983 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_petty_cash_report` */

CREATE TABLE `cw_petty_cash_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `report` longtext DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `handover_id` int(10) unsigned NOT NULL,
  `company` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=296 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_sales` */

CREATE TABLE `cw_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `particulars` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `discount` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `paid` decimal(10,2) unsigned NOT NULL DEFAULT 0.00,
  `date` date NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `company` int(10) unsigned NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer` (`customer_id`),
  CONSTRAINT `customer` FOREIGN KEY (`customer_id`) REFERENCES `cw_customer` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1979 DEFAULT CHARSET=latin1;

/*Table structure for table `cw_service` */

CREATE TABLE `cw_service` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `price` decimal(10,2) unsigned NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `company` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_name_company` (`name`,`company`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;

/*Table structure for table `damaged_item` */

CREATE TABLE `damaged_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_variance_id` int(11) NOT NULL,
  `quantity` int(10) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `branch_id` int(11) DEFAULT 1,
  `confirm_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_damage_branch_created` (`branch_id`,`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Table structure for table `division` */

CREATE TABLE `division` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serial` tinyint(4) NOT NULL DEFAULT 1,
  `parent_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `expense_account` */

CREATE TABLE `expense_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('Credit','Debit') NOT NULL DEFAULT 'Debit',
  `parent` int(10) unsigned DEFAULT NULL,
  `code` varchar(8) DEFAULT NULL,
  `parent_code` varchar(8) DEFAULT NULL,
  `fullcode` varchar(8) DEFAULT NULL,
  `path` varchar(64) DEFAULT NULL,
  `has_child` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `depth` int(10) unsigned NOT NULL DEFAULT 0,
  `sortorder` varchar(64) DEFAULT '0',
  `contextid` int(10) unsigned DEFAULT NULL,
  `contexttype` varchar(64) DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `breadcrumbs` text DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `hotel` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `parente` (`parent`),
  KEY `eba` (`entry_by`),
  KEY `mba` (`modify_by`),
  CONSTRAINT `eba` FOREIGN KEY (`entry_by`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `mba` FOREIGN KEY (`modify_by`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `parente` FOREIGN KEY (`parent`) REFERENCES `expense_account` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=698 DEFAULT CHARSET=latin1;

/*Table structure for table `expense_account_entry` */

CREATE TABLE `expense_account_entry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `accountid` int(10) unsigned DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text NOT NULL,
  `remarks` text DEFAULT NULL,
  `entry_type` varchar(128) DEFAULT NULL,
  `entry_id` int(10) unsigned DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  `tran_type` enum('Credit','Debit') NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `accountpath` longtext DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `month` varchar(7) DEFAULT NULL,
  `payment_method` enum('Cash','Online') NOT NULL DEFAULT 'Cash',
  `checked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `approve_by` int(10) unsigned DEFAULT NULL,
  `approve_time` datetime DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `particular_date` date DEFAULT NULL,
  `bank_transaction` int(10) unsigned DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `bank` int(10) unsigned DEFAULT NULL,
  `bank_tran_id` int(10) unsigned DEFAULT NULL,
  `hotel` int(10) unsigned DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  `capex_or_opex` varchar(20) NOT NULL DEFAULT 'Capex',
  `opex_or_capex` varchar(20) NOT NULL DEFAULT 'Capex',
  `investment_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account` (`accountid`),
  KEY `eb` (`entry_by`),
  KEY `mb` (`modify_by`),
  KEY `idx_eae_branch_method_expensedate` (`branch_id`,`payment_method`,`expense_date`),
  CONSTRAINT `account` FOREIGN KEY (`accountid`) REFERENCES `expense_account` (`id`),
  CONSTRAINT `eb` FOREIGN KEY (`entry_by`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `mb` FOREIGN KEY (`modify_by`) REFERENCES `sys_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2480 DEFAULT CHARSET=latin1;

/*Table structure for table `goods_return` */

CREATE TABLE `goods_return` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_date` date DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

/*Table structure for table `goods_return_item` */

CREATE TABLE `goods_return_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_variance_id` int(11) NOT NULL,
  `quantity` int(10) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_gri_branch_created` (`branch_id`,`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel` */

CREATE TABLE `hotel` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `basic` decimal(10,2) NOT NULL,
  `billing_name` varchar(128) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `attn_to` varchar(128) DEFAULT NULL,
  `accountid` int(10) unsigned DEFAULT NULL,
  `startdate` date DEFAULT NULL,
  `enddate` date DEFAULT NULL,
  `round_hourly_salary` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `commission` decimal(5,2) DEFAULT 0.00,
  `type` enum('meal','salary','both') NOT NULL DEFAULT 'both',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_capital` */

CREATE TABLE `hotel_capital` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `type` enum('Invest','Collect') NOT NULL DEFAULT 'Invest',
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` enum('Pending','Approved') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_card` */

CREATE TABLE `hotel_card` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `card1` varchar(128) DEFAULT NULL,
  `card2` varchar(128) DEFAULT NULL,
  `card3` varchar(128) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'Hotel',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_card_remarks` */

CREATE TABLE `hotel_card_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_card` int(10) unsigned NOT NULL,
  `remarks` text NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_expense` */

CREATE TABLE `hotel_expense` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `statement` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` enum('Pending','Approved') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  `account_entry_id` int(10) unsigned DEFAULT NULL,
  `payment_method` enum('Cash','Online') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Online',
  `bank_transaction` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `hotel_income` */

CREATE TABLE `hotel_income` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `statement` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text COLLATE utf8_unicode_ci NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` enum('Pending','Approved') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  `account_entry_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `hotel_invoice` */

CREATE TABLE `hotel_invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` int(10) unsigned NOT NULL,
  `hotel` int(10) unsigned NOT NULL,
  `hotel_name` varchar(128) DEFAULT NULL,
  `hotel_address` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_invoice_payment` */

CREATE TABLE `hotel_invoice_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `month` varchar(7) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `date` date NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `type` enum('Invoice','Payment') NOT NULL DEFAULT 'Payment',
  `remarks` text DEFAULT NULL,
  `status` enum('Pending','Checked','Approved') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_loan` */

CREATE TABLE `hotel_loan` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `direction` enum('Give','Collect') COLLATE utf8_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `account` int(10) unsigned NOT NULL,
  `particulars` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` enum('Pending','Approved') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `hotel_parttime` */

CREATE TABLE `hotel_parttime` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL DEFAULT 10,
  `date` date NOT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `worker_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`hotel`,`date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_parttime_worker` */

CREATE TABLE `hotel_parttime_worker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel_parttime_date` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `passport` varchar(12) DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `hotel_parttime` (`hotel_parttime_date`),
  CONSTRAINT `hotel_parttime` FOREIGN KEY (`hotel_parttime_date`) REFERENCES `hotel_parttime` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_payment` */

CREATE TABLE `hotel_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `month` varchar(7) NOT NULL,
  `description` text DEFAULT NULL,
  `billed_amount` decimal(10,2) unsigned DEFAULT NULL,
  `input_amount` varchar(64) DEFAULT NULL,
  `paid_amount` decimal(10,2) unsigned DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('Pending','Done','Carryforward','Checked') NOT NULL DEFAULT 'Pending',
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime DEFAULT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Pending','Checked','Approved') NOT NULL DEFAULT 'Pending',
  `approved` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_time` datetime DEFAULT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#ffffff',
  `invoice_no` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hotel_id` (`hotel`),
  CONSTRAINT `hotel_id` FOREIGN KEY (`hotel`) REFERENCES `hotel` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement` */

CREATE TABLE `hotel_statement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `month` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `hotel` int(10) unsigned NOT NULL,
  `hourly` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `type` enum('Fulltime','Parttime','Hourly') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Fulltime',
  `invoice` int(10) unsigned DEFAULT NULL,
  `file1` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `file2` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `file3` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `file4` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `accountid` int(10) unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `text_color` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `background_color` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enum` enum('meal','salary','hotel') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'salary' COMMENT 'Type of statement: meal, salary, hotel',
  PRIMARY KEY (`id`),
  UNIQUE KEY `m-h` (`month`,`hotel`,`type`,`enum`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `hotel_statement_meal` */

CREATE TABLE `hotel_statement_meal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `basic` decimal(10,2) unsigned NOT NULL,
  `pay` enum('Monthly','Daily') NOT NULL DEFAULT 'Monthly',
  `phone` varchar(20) DEFAULT NULL,
  `working_days` int(10) unsigned NOT NULL,
  `working_hours` int(10) unsigned NOT NULL DEFAULT 0,
  `mc` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `public_holiday` int(10) unsigned NOT NULL DEFAULT 0,
  `approved` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `billed_amount` decimal(10,2) DEFAULT NULL,
  `category` varchar(1) DEFAULT NULL,
  `verified` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `account` varchar(256) DEFAULT NULL,
  `lock` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_stmt_meal` (`statement`),
  CONSTRAINT `fk_hotel_statement_meal_statement` FOREIGN KEY (`statement`) REFERENCES `hotel_statement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_meal_income` */

CREATE TABLE `hotel_statement_meal_income` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_income_meal` (`worker`),
  CONSTRAINT `fk_hotel_statement_meal_income_worker` FOREIGN KEY (`worker`) REFERENCES `hotel_statement_meal` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_meal_payment` */

CREATE TABLE `hotel_statement_meal_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_time` datetime DEFAULT NULL,
  `done_by` int(10) unsigned DEFAULT NULL,
  `done_time` datetime DEFAULT NULL,
  `account_entry_id` int(10) unsigned DEFAULT NULL,
  `transfer_customer` int(10) unsigned DEFAULT NULL,
  `worker_id` int(10) unsigned DEFAULT NULL,
  `bank` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_meal` (`worker`),
  CONSTRAINT `fk_hotel_statement_meal_payment_worker` FOREIGN KEY (`worker`) REFERENCES `hotel_statement_meal` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_remarks` */

CREATE TABLE `hotel_statement_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` int(10) unsigned NOT NULL,
  `remarks` text DEFAULT NULL,
  `text_color` varchar(15) DEFAULT NULL,
  `background_color` varchar(15) DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_worker` */

CREATE TABLE `hotel_statement_worker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `basic` decimal(10,2) unsigned NOT NULL,
  `pay` enum('Monthly','Daily') NOT NULL DEFAULT 'Monthly',
  `phone` varchar(20) DEFAULT NULL,
  `working_days` int(10) unsigned NOT NULL,
  `working_hours` int(10) unsigned NOT NULL DEFAULT 0,
  `mc` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `public_holiday` int(10) unsigned NOT NULL DEFAULT 0,
  `approved` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `billed_amount` decimal(10,2) DEFAULT NULL,
  `category` varchar(1) DEFAULT NULL,
  `verified` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `account` varchar(256) DEFAULT NULL,
  `lock` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `daily_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `meal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `visa` decimal(10,2) NOT NULL DEFAULT 0.00,
  `monthly_working` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `stmt` (`statement`),
  CONSTRAINT `stmt` FOREIGN KEY (`statement`) REFERENCES `hotel_statement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_worker_income` */

CREATE TABLE `hotel_statement_worker_income` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `swi` (`worker`),
  CONSTRAINT `swi` FOREIGN KEY (`worker`) REFERENCES `hotel_statement_worker` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_statement_worker_payment` */

CREATE TABLE `hotel_statement_worker_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_time` datetime DEFAULT NULL,
  `done_by` int(10) unsigned DEFAULT NULL,
  `done_time` datetime DEFAULT NULL,
  `account_entry_id` int(10) unsigned DEFAULT NULL,
  `transfer_customer` int(10) unsigned DEFAULT NULL,
  `worker_id` int(10) unsigned DEFAULT NULL,
  `bank` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stw` (`worker`),
  CONSTRAINT `stw` FOREIGN KEY (`worker`) REFERENCES `hotel_statement_worker` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_trans` */

CREATE TABLE `hotel_trans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `type` enum('Cash','Expense') NOT NULL,
  `payment_method` enum('Bank','Cash') DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `hotel_withdraw` */

CREATE TABLE `hotel_withdraw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hotel` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `status` enum('Pending','Approved') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `hotel_worker_payment` */

CREATE TABLE `hotel_worker_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `worker` int(10) unsigned NOT NULL,
  `statement` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `particulars` text DEFAULT NULL,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `incentive` */

CREATE TABLE `incentive` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `salesman` varchar(128) NOT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `particulars` text NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

/*Table structure for table `investment` */

CREATE TABLE `investment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `particulars` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('Bank','Cash') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bank',
  `date` date NOT NULL,
  `trans_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Budget',
  `trash` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date_created` (`date`,`created_at`),
  KEY `idx_trash` (`trash`),
  KEY `fk_invest_created_by` (`created_by`),
  KEY `fk_invest_updated_by` (`updated_by`),
  KEY `fk_invest_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_invest_created_by` FOREIGN KEY (`created_by`) REFERENCES `sys_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invest_deleted_by` FOREIGN KEY (`deleted_by`) REFERENCES `sys_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invest_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `sys_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Table structure for table `invoice` */

CREATE TABLE `invoice` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_date` date DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `delivered_by` int(10) unsigned DEFAULT NULL,
  `delivery_staff` varchar(128) DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `salesman_id` int(10) unsigned DEFAULT NULL,
  `salesman` varchar(128) DEFAULT NULL,
  `incentive` decimal(4,2) unsigned DEFAULT 5.00,
  `customer_order_id` int(10) unsigned DEFAULT NULL,
  `confirm_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_invoice_date` (`invoice_date`),
  KEY `idx_invoice_customer` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12728 DEFAULT CHARSET=latin1;

/*Table structure for table `invoice_item` */

CREATE TABLE `invoice_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `product_variance_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` decimal(10,2) unsigned DEFAULT NULL,
  `cost` decimal(10,2) unsigned DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) unsigned DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivered` int(10) unsigned NOT NULL DEFAULT 0,
  `delivered_by` varchar(128) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `delivery_staff` varchar(128) DEFAULT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `price_updated_by` int(10) unsigned DEFAULT NULL,
  `price_updated_at` datetime DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  `collected_at` datetime DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assigned_by` int(10) unsigned DEFAULT NULL,
  `assigned_to` varchar(128) DEFAULT NULL,
  `assigned_to_name` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_invoice_item_invoice` (`invoice_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12837 DEFAULT CHARSET=latin1;

/*Table structure for table `invoice_item_delviery` */

CREATE TABLE `invoice_item_delviery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_item_id` int(10) unsigned NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `delivered_by` int(10) unsigned NOT NULL,
  `delivered_at` datetime NOT NULL DEFAULT current_timestamp(),
  `delivery_staff` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12997 DEFAULT CHARSET=latin1;

/*Table structure for table `lorry` */

CREATE TABLE `lorry` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `driver_name` varchar(100) NOT NULL,
  `lorry_no` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_lorry_no` (`lorry_no`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `order` */

CREATE TABLE `order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_date` date DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `confirm_date` date DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `status` varchar(32) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `delivered_by` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `lorry_id` int(10) unsigned DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_order_lorry` (`lorry_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2110 DEFAULT CHARSET=latin1;

/*Table structure for table `order_item` */

CREATE TABLE `order_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_variance_id` int(11) NOT NULL,
  `is_verify` tinyint(1) NOT NULL DEFAULT 0,
  `quantity` int(10) NOT NULL,
  `price` decimal(10,3) DEFAULT NULL,
  `cost` decimal(10,3) DEFAULT NULL,
  `sst` decimal(4,2) unsigned NOT NULL DEFAULT 0.00,
  `name` varchar(128) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `branch_id` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_order_item_branch_created` (`branch_id`,`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=3770 DEFAULT CHARSET=latin1;

/*Table structure for table `payment` */

CREATE TABLE `payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `is_verify` tinyint(1) NOT NULL DEFAULT 0,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `payment_method` enum('Bank','Cash','Credit') NOT NULL,
  `payment_remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL,
  `branch_id` int(11) DEFAULT 1,
  `opex_or_capex` varchar(20) DEFAULT 'Capex',
  `investment_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_branch_method_created` (`branch_id`,`payment_method`,`created_at`),
  KEY `idx_payment_branch_method_date` (`branch_id`,`payment_method`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=266 DEFAULT CHARSET=latin1;

/*Table structure for table `petty_cash_currency_data` */

CREATE TABLE `petty_cash_currency_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `label` varchar(50) NOT NULL,
  `denomination` decimal(10,2) NOT NULL DEFAULT 0.00,
  `count` decimal(10,2) NOT NULL DEFAULT 0.00,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `modify_by` int(10) unsigned DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `month_year_label` (`month`,`year`,`label`)
) ENGINE=InnoDB AUTO_INCREMENT=748 DEFAULT CHARSET=utf8;

/*Table structure for table `petty_cash_currency_notes` */

CREATE TABLE `petty_cash_currency_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `month` int(2) NOT NULL,
  `year` int(4) NOT NULL,
  `note_text` text NOT NULL,
  `note_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(3) NOT NULL DEFAULT 0,
  `entry_by` int(10) unsigned NOT NULL,
  `entry_time` datetime NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `month_year` (`month`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;

/*Table structure for table `product` */

CREATE TABLE `product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `image` varchar(128) DEFAULT NULL,
  `image2` varchar(128) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `image_orientation` enum('L','P') NOT NULL DEFAULT 'P',
  `product_category_id` int(10) unsigned NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT NULL,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=latin1;

/*Table structure for table `product_category` */

CREATE TABLE `product_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `sort_order` tinyint(4) DEFAULT NULL,
  `uom` varchar(128) DEFAULT NULL,
  `uom2` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1;

/*Table structure for table `product_supplier` */

CREATE TABLE `product_supplier` (
  `product_id` int(10) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`product_id`,`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `product_variance` */

CREATE TABLE `product_variance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL DEFAULT 0,
  `index` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `particulars` text DEFAULT NULL,
  `tax` decimal(4,2) DEFAULT NULL,
  `cost_before_tax` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `wholesale` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `wprice` decimal(10,2) DEFAULT NULL,
  `image` varchar(128) DEFAULT NULL,
  `image_single` varchar(128) DEFAULT NULL,
  `size` varchar(32) DEFAULT NULL,
  `unit` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `min_stock` int(11) NOT NULL,
  `image_orientation` enum('L','P') NOT NULL DEFAULT 'P',
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `frozen` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=117 DEFAULT CHARSET=latin1;

/*Table structure for table `push_client` */

CREATE TABLE `push_client` (
  `type` enum('a','c') NOT NULL,
  `id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  PRIMARY KEY (`type`,`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `push_notification` */

CREATE TABLE `push_notification` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `receiver` text NOT NULL,
  `message` text NOT NULL,
  `status` enum('Pending','Sent','Failed') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `refund` */

CREATE TABLE `refund` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) unsigned NOT NULL,
  `payment_method` enum('Bank','Supplier ID') NOT NULL,
  `payment_remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `status` enum('Pending','Approved') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;

/*Table structure for table `salesman` */

CREATE TABLE `salesman` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `basic` decimal(10,2) NOT NULL,
  `image` varchar(256) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Table structure for table `salesman_remarks` */

CREATE TABLE `salesman_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `salesman_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('High','Normal','Low') NOT NULL DEFAULT 'Normal',
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `entry_by` int(10) unsigned NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1692 DEFAULT CHARSET=latin1;

/*Table structure for table `staff` */

CREATE TABLE `staff` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `basic` decimal(10,2) NOT NULL,
  `image` varchar(256) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

/*Table structure for table `staff_income` */

CREATE TABLE `staff_income` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `swi` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=573 DEFAULT CHARSET=latin1;

/*Table structure for table `staff_payment` */

CREATE TABLE `staff_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `particulars` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `done_by` int(10) unsigned DEFAULT NULL,
  `done_at` datetime DEFAULT NULL,
  `account_entry_id` int(10) unsigned DEFAULT NULL,
  `transfer_customer` int(10) unsigned DEFAULT NULL,
  `worker_id` int(10) unsigned DEFAULT NULL,
  `bank` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stw` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

/*Table structure for table `staff_salary` */

CREATE TABLE `staff_salary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `name` varchar(128) DEFAULT NULL,
  `statement_id` int(11) DEFAULT NULL,
  `days` tinyint(4) NOT NULL DEFAULT 0,
  `basic` decimal(10,2) NOT NULL DEFAULT 0.00,
  `salary` decimal(10,0) NOT NULL DEFAULT 0,
  `extra` decimal(10,0) NOT NULL DEFAULT 0,
  `paid` decimal(10,0) NOT NULL DEFAULT 0,
  `account` varchar(32) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `lock` tinyint(1) NOT NULL DEFAULT 0,
  `category` enum('Delivery Staff','Salesman','Store Staff','Marketing') DEFAULT NULL,
  `incentive` decimal(4,2) NOT NULL DEFAULT 5.00,
  `user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=84 DEFAULT CHARSET=latin1;

/*Table structure for table `statement` */

CREATE TABLE `statement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `month` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `month` (`month`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;

/*Table structure for table `stock_collect` */

CREATE TABLE `stock_collect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `salesman_id` int(10) unsigned DEFAULT NULL,
  `lorry_id` int(10) unsigned DEFAULT NULL,
  `delivery_staff` varchar(128) NOT NULL,
  `address` text DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL,
  `last_modified_by` int(10) unsigned DEFAULT NULL,
  `last_modified_time` datetime DEFAULT NULL,
  `approve_by` int(10) unsigned DEFAULT NULL,
  `approve_time` datetime DEFAULT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `category` varchar(12) NOT NULL DEFAULT '0',
  `category_ref` int(10) unsigned DEFAULT NULL,
  `advance_payment` int(10) unsigned DEFAULT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `amount` decimal(10,0) unsigned DEFAULT NULL,
  `confirm_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stock_collect_lorry` (`lorry_id`),
  CONSTRAINT `fk_stock_collect_lorry` FOREIGN KEY (`lorry_id`) REFERENCES `lorry` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2521 DEFAULT CHARSET=latin1;

/*Table structure for table `stock_collect_item` */

CREATE TABLE `stock_collect_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_collect_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_variance_id` int(10) unsigned DEFAULT NULL,
  `invoice_item_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(128) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` decimal(8,2) unsigned NOT NULL,
  `cost` decimal(8,2) unsigned NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL,
  `returned_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `damaged_quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `damaged_cause` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6217 DEFAULT CHARSET=latin1;

/*Table structure for table `stock_return` */

CREATE TABLE `stock_return` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `salesman_id` int(10) unsigned NOT NULL,
  `address` text DEFAULT NULL,
  `company` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL,
  `last_modified_by` int(10) unsigned DEFAULT NULL,
  `last_modified_time` datetime DEFAULT NULL,
  `approve_by` int(10) unsigned DEFAULT NULL,
  `approve_time` datetime DEFAULT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `category` varchar(12) NOT NULL DEFAULT '0',
  `category_ref` int(10) unsigned DEFAULT NULL,
  `advance_payment` int(10) unsigned DEFAULT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `amount` decimal(10,0) unsigned DEFAULT NULL,
  `stock_collect_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

/*Table structure for table `stock_return_item` */

CREATE TABLE `stock_return_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_return_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_variance_id` int(10) unsigned DEFAULT NULL,
  `invoice_item_id` int(10) unsigned NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(128) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `price` decimal(8,2) unsigned NOT NULL,
  `cost` decimal(8,2) unsigned NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(10) unsigned NOT NULL,
  `stock_collect_item_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=latin1;

/*Table structure for table `supplier` */

CREATE TABLE `supplier` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(128) NOT NULL,
  `contact` varchar(128) NOT NULL,
  `mobile` varchar(32) NOT NULL,
  `city` varchar(64) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(64) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `version` tinyint(4) DEFAULT NULL,
  `active` bit(1) DEFAULT b'1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

/*Table structure for table `supplier_custom_sort` */

CREATE TABLE `supplier_custom_sort` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `sort_key` varchar(50) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`,`sort_key`)
) ENGINE=MyISAM AUTO_INCREMENT=18437 DEFAULT CHARSET=latin1;

/*Table structure for table `supplier_remarks` */

CREATE TABLE `supplier_remarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `notes` text DEFAULT NULL,
  `priority` enum('High','Normal','Low') NOT NULL DEFAULT 'Normal',
  `entry_time` datetime NOT NULL DEFAULT current_timestamp(),
  `entry_by` int(10) unsigned NOT NULL,
  `trash` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1719 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_acl` */

CREATE TABLE `sys_acl` (
  `appliesto` int(4) NOT NULL,
  `utype` enum('u','r') NOT NULL,
  `privilege` int(3) NOT NULL,
  `access` tinyint(1) NOT NULL DEFAULT 0,
  `entrytime` datetime NOT NULL DEFAULT current_timestamp(),
  `entryby` int(10) unsigned NOT NULL,
  PRIMARY KEY (`appliesto`,`utype`,`privilege`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `sys_change_log` */

CREATE TABLE `sys_change_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `l_module` varchar(64) NOT NULL,
  `l_id` int(10) unsigned NOT NULL,
  `l_time` datetime NOT NULL,
  `l_user` int(11) DEFAULT NULL,
  `l_old_value` text NOT NULL,
  `l_new_value` text NOT NULL,
  `l_remarks` text DEFAULT NULL,
  `l_status` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `sys_fraud_user` */

CREATE TABLE `sys_fraud_user` (
  `f_username` varchar(32) NOT NULL,
  `f_password` varchar(64) DEFAULT NULL,
  `f_ip` varchar(15) NOT NULL,
  `f_attempts` int(11) NOT NULL DEFAULT 1,
  `f_date` datetime NOT NULL,
  PRIMARY KEY (`f_username`,`f_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `sys_guest_log` */

CREATE TABLE `sys_guest_log` (
  `gl_time` datetime NOT NULL,
  `gl_ip` varchar(64) NOT NULL,
  `gl_url` varchar(256) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `sys_log` */

CREATE TABLE `sys_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `l_logger` varchar(128) NOT NULL,
  `l_type` varchar(128) NOT NULL,
  `l_msg` text NOT NULL,
  `l_ref1` varchar(128) DEFAULT NULL,
  `l_ref2` varchar(128) DEFAULT NULL,
  `l_ref3` varchar(128) DEFAULT NULL,
  `l_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `sys_privilege` */

CREATE TABLE `sys_privilege` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `app` varchar(64) NOT NULL DEFAULT 'sys',
  `module` varchar(64) DEFAULT NULL,
  `link` varchar(64) DEFAULT NULL,
  `option` varchar(24) DEFAULT NULL,
  `name` varchar(64) NOT NULL,
  `title` varchar(64) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `root` int(2) DEFAULT NULL,
  `position` int(2) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `glyphicon` varchar(64) NOT NULL DEFAULT 'fa fa-file',
  `icon` varchar(128) NOT NULL DEFAULT 'noicon.png',
  `show_in_frontpage` tinyint(1) NOT NULL DEFAULT 0,
  `hidden` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `password_required` tinyint(1) NOT NULL DEFAULT 0,
  `controller` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `target` enum('_blank','_self','_parent','_top') NOT NULL DEFAULT '_top',
  `sk1` enum('Ctrl','Alt','Shift') DEFAULT NULL,
  `sk2` enum('Ctrl','Alt','Shift') DEFAULT NULL,
  `sk3` enum('Ctrl','Alt','Shift') DEFAULT NULL,
  `sk4` varchar(12) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link` (`link`,`option`,`root`),
  UNIQUE KEY `shortcut` (`sk1`,`sk2`,`sk3`,`sk4`)
) ENGINE=InnoDB AUTO_INCREMENT=943 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_privilege_log` */

CREATE TABLE `sys_privilege_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` set('1') COLLATE utf8_unicode_ci DEFAULT NULL,
  `time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `controller` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `function` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2464 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `sys_register` */

CREATE TABLE `sys_register` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(128) NOT NULL,
  `value` text DEFAULT NULL,
  `group` varchar(32) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `register_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_registration` */

CREATE TABLE `sys_registration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `r_username` varchar(256) NOT NULL,
  `r_password` varchar(64) NOT NULL,
  `r_ip` varchar(64) NOT NULL,
  `r_geo` text DEFAULT NULL,
  `r_time` datetime NOT NULL,
  `r_verification` varchar(64) NOT NULL,
  `r_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `sys_role` */

CREATE TABLE `sys_role` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `r_name` varchar(32) NOT NULL,
  `code` varchar(12) DEFAULT NULL,
  `r_active` tinyint(1) NOT NULL DEFAULT 1,
  `r_date_created` date DEFAULT NULL,
  `r_owner` int(10) unsigned NOT NULL DEFAULT 0,
  `r_scope` enum('Global','Local') NOT NULL,
  `r_created_by` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_search_options` */

CREATE TABLE `sys_search_options` (
  `schema` varchar(128) DEFAULT NULL,
  `object` varchar(128) DEFAULT NULL,
  `display` varchar(128) DEFAULT NULL,
  `functions` varchar(128) DEFAULT NULL,
  `keys` varchar(128) DEFAULT NULL,
  `note` varchar(128) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `sys_sessions` */

CREATE TABLE `sys_sessions` (
  `id` varchar(32) NOT NULL,
  `access` int(10) unsigned DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `sys_site` */

CREATE TABLE `sys_site` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `s_name` varbinary(128) NOT NULL,
  `s_description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_site_privilege` */

CREATE TABLE `sys_site_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sp_site` int(10) unsigned NOT NULL,
  `sp_privilege` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_privilege` (`sp_site`,`sp_privilege`),
  KEY `sp_privilege` (`sp_privilege`),
  CONSTRAINT `sp_privilege` FOREIGN KEY (`sp_privilege`) REFERENCES `sys_privilege20180311` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sp_site` FOREIGN KEY (`sp_site`) REFERENCES `sys_site` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_sites` */

CREATE TABLE `sys_sites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `page` varchar(32) NOT NULL,
  `site` varchar(32) NOT NULL,
  `seq` tinyint(4) NOT NULL,
  `has_menu` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `active` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `simple` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `dashboard` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `dashboard_url` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_token` */

CREATE TABLE `sys_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  PRIMARY KEY (`id`,`user_id`,`token`)
) ENGINE=MyISAM AUTO_INCREMENT=269 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_user` */

CREATE TABLE `sys_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `u_fullname` varchar(128) NOT NULL,
  `u_username` varchar(64) NOT NULL,
  `u_password` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `pass` varchar(128) DEFAULT NULL,
  `u_recover` varchar(50) DEFAULT NULL,
  `u_pin` varchar(32) NOT NULL DEFAULT '1111',
  `u_date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `u_loggedin` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `u_last_login_time` datetime DEFAULT NULL,
  `u_last_ip` varchar(42) DEFAULT NULL,
  `u_remarks` text DEFAULT NULL,
  `u_email` varchar(128) DEFAULT NULL,
  `u_created_by` int(10) unsigned NOT NULL DEFAULT 1,
  `u_last_modified_by` int(10) unsigned DEFAULT NULL,
  `u_failed_attempt` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `u_status` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `u_avatar` varchar(128) DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `order_notification` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`u_username`),
  UNIQUE KEY `u_pin` (`u_pin`),
  KEY `user_account` (`account_id`),
  CONSTRAINT `user_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=latin1;

/*Table structure for table `sys_user_role` */

CREATE TABLE `sys_user_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ur_user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `ur_role_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`ur_user_id`,`ur_role_id`),
  KEY `role` (`ur_role_id`),
  KEY `id` (`id`),
  CONSTRAINT `ur_role_id` FOREIGN KEY (`ur_role_id`) REFERENCES `sys_role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `ur_user_id` FOREIGN KEY (`ur_user_id`) REFERENCES `sys_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=347 DEFAULT CHARSET=latin1;

/* Function  structure for function  `coItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `coItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
#SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (',IFNULL(ii.description,''), ' ', IFNULL(pv.size,''), ' x ', IFNULL(pv.unit,'') ,') <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
#SELECT GROUP_CONCAT('<div class=\"order-item\">',IFNULL(ii.description,''), ' ', IFNULL(pv.size,''), ' x ', IFNULL(pv.unit,'') ,' <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
SELECT GROUP_CONCAT('<div class=\"order-item\">',IFNULL(ii.description,''), ' <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
FROM `customer_order_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND customer_order_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `deliveredItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `deliveredItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\"></span><span class=\"',IFNULL(delivered_at, ' hidden-white'),'\"> <span class=\"item-price\">','<span class=\"item-qty\">', ii.quantity,'</span></span></span></div>' SEPARATOR ' ') INTO items
FROM `invoice_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND invoice_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `invoiceItems2` */

DELIMITER $$

/*!50003 CREATE FUNCTION `invoiceItems2`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (', p.name, ' ',ii.description, ' ', pv.size, ' x ', pv.unit ,') <span class=\"item-price\"><span class=\"item-qty\">', ii.quantity,'</span></span></div>' SEPARATOR ' ') INTO items
FROM `invoice_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND invoice_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `returnedItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `returnedItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SELECT GROUP_CONCAT('<div style=\"border-bottom: solid 1px #ccc;\">',description,', <b class=\"frht\">(', cost ,' X ', quantity,' = ',(quantity*cost),')</b></div>' SEPARATOR '') INTO items
FROM `goods_return_item` WHERE order_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stock` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stock`(_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
DECLARE total INT;
SELECT 
IFNULL((SELECT SUM(quantity) FROM order_item WHERE product_variance_id=_id),0) -
IFNULL((SELECT SUM(quantity) FROM invoice_item WHERE product_variance_id=_id),0) INTO total;
RETURN total;
END */$$
DELIMITER ;

/* Function  structure for function  `invoiceItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `invoiceItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
#SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (',IFNULL(ii.description,''), ' ', IFNULL(pv.size,''), ' x ', IFNULL(pv.unit,'') ,') <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
#SELECT GROUP_CONCAT('<div class=\"order-item\">',IFNULL(ii.description,''), ' ', IFNULL(pv.size,''), ' x ', IFNULL(pv.unit,'') ,' <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
SELECT GROUP_CONCAT('<div class=\"order-item\">',IFNULL(ii.description,''), ' <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
FROM `invoice_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND invoice_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `invoiceItemsDelivery` */

DELIMITER $$

/*!50003 CREATE FUNCTION `invoiceItemsDelivery`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (', p.name, ' ',ii.description, ' ', pv.size, ' x ', pv.unit ,') <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span> <span class=\"d-icon btn btn-sm btn-warning\"><i class=\"fas fa-shipping-fast\"></i></span></div>' SEPARATOR ' ') INTO items
FROM `invoice_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND invoice_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `orderItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `orderItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT(
  '<div class=\"order-item\"><span class=\"item-count\">', 
  @rank := @rank + 1, 
  '.</span> (', 
  #IFNULL(p.name, ''), ' ', 
  TRIM(IFNULL(ii.description, '')), ' ', 
  IFNULL(pv.size, ''), ' x ', 
  IFNULL(pv.unit, '') ,') <span class=\"item-price\">(', 
  IFNULL(ii.cost, 0) ,' X <span class=\"item-qty\">', 
  IFNULL(ii.quantity, 0), '</span> = ', 
  (IFNULL(ii.quantity, 0) * IFNULL(ii.cost, 0)), 
  ')</span></div>' 
  SEPARATOR ''
) INTO items
FROM `order_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND order_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stock2` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stock2`(_id INT, _branch_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
DECLARE total INT;
SELECT 
IFNULL((SELECT SUM(quantity) FROM order_item WHERE product_variance_id=_id AND branch_id=_branch_id),0) -
IFNULL((SELECT SUM(quantity) FROM invoice_item WHERE product_variance_id=_id AND branch_id=_branch_id),0) INTO total;
RETURN total;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (', p.name, ' ',ii.description, ' ', pv.size, ' x ', pv.unit ,') <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
FROM `stock_collect_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItemsMini` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItemsMini`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (', p.name, ' ',ii.description, ' ', pv.size, ' x ', pv.unit ,')</span></div>' SEPARATOR ' ') INTO items
FROM `stock_collect_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItemsQty` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItemsQty`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\">',ii.quantity,'</div>' SEPARATOR ' ') INTO items
FROM `stock_collect_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItemsQtyDelivered` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItemsQtyDelivered`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;

SELECT GROUP_CONCAT(CONCAT('<div class="order-item">',(SELECT IFNULL(SUM(IFNULL(quantity,0)),0) FROM `invoice_item` WHERE DATE(invoice_item.delivered_at)=CURDATE() 
AND invoice_item.product_variance_id=stock_collect_item.product_variance_id),'</div>') SEPARATOR '') INTO items
FROM `stock_collect_item` WHERE stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItemsQtyPending` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItemsQtyPending`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;

SELECT GROUP_CONCAT(CONCAT('<div class="order-item ',IF(returned_quantity=0,'pending','returned'),'">',quantity-returned_quantity-(SELECT IFNULL(SUM(IFNULL(quantity,0)),0) FROM `invoice_item` WHERE DATE(invoice_item.delivered_at)=CURDATE() 
AND invoice_item.product_variance_id=stock_collect_item.product_variance_id),'</div>') SEPARATOR '') INTO items
FROM `stock_collect_item` WHERE stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCollectItemsQtyToReturn` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCollectItemsQtyToReturn`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;

SELECT GROUP_CONCAT(CONCAT('<div class="order-item ',IF(returned_quantity=0,'pending','returned'),'">',quantity-(SELECT IFNULL(SUM(IFNULL(quantity,0)),0) FROM `invoice_item` WHERE DATE(invoice_item.delivered_at)=CURDATE() 
AND invoice_item.product_variance_id=stock_collect_item.product_variance_id),'<span class="pad-right-5"></span><span class="btn btn-return btn-sm btn-',IF(returned_quantity=0,'warning','success'),'" data-id="',id,'"><i class="fas fa-people-carry"></i></span></div>') SEPARATOR '') INTO items
FROM `stock_collect_item` WHERE stock_collect_id=_id;
RETURN items;
END */$$
DELIMITER ;

/* Function  structure for function  `stockCurrent` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockCurrent`(_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
DECLARE total INT;
SELECT 
IFNULL((SELECT SUM(quantity) FROM order_item WHERE product_variance_id=_id),0) -
IFNULL((SELECT SUM(quantity) FROM invoice_item ii, invoice i WHERE i.id=ii.invoice_id and product_variance_id=_id AND IFNULL(ii.delivery_date, i.invoice_date) = curdate()),0) INTO total;
RETURN total;
END */$$
DELIMITER ;

/* Function  structure for function  `stockPending` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockPending`(_id INT) RETURNS int(11)
    DETERMINISTIC
BEGIN
DECLARE total INT;
SELECT 
IFNULL((SELECT SUM(quantity) FROM order_item WHERE product_variance_id=_id),0) -
IFNULL((SELECT SUM(quantity) FROM invoice_item ii, invoice i WHERE i.id=ii.invoice_id AND product_variance_id=_id AND IFNULL(ii.delivery_date, i.invoice_date) < curdate()),0) INTO total;
RETURN total;
END */$$
DELIMITER ;

/* Function  structure for function  `stockReturnItems` */

DELIMITER $$

/*!50003 CREATE FUNCTION `stockReturnItems`(_id INT) RETURNS text CHARSET latin1
    DETERMINISTIC
BEGIN
DECLARE items TEXT;
SET @rank=0;
SELECT GROUP_CONCAT('<div class=\"order-item\"><span class=\"item-count\">', @rank:=@rank+1, '.</span> (', p.name, ' ',ii.description, ' ', pv.size, ' x ', pv.unit ,') <span class=\"item-price\">(', ii.price ,' X <span class=\"item-qty\">', ii.quantity,'</span> = ',(ii.quantity*ii.price),')</span></div>' SEPARATOR ' ') INTO items
FROM `stock_return_item` ii, `product_variance` pv, product p WHERE pv.id=ii.product_variance_id AND p.id=pv.product_id AND stock_return_id=_id;
RETURN items;
END */$$
DELIMITER ;

/*Table structure for table `sys_acls` */

DROP TABLE IF EXISTS `sys_acls`;

/*!50001 CREATE TABLE  `sys_acls`(
 `privilege` int(11) ,
 `link` varchar(64) ,
 `access` tinyint(4) ,
 `utype` varchar(1) ,
 `user` int(11) 
)*/;

/*Table structure for table `sys_permission` */

DROP TABLE IF EXISTS `sys_permission`;

/*!50001 CREATE TABLE  `sys_permission`(
 `id` int(11) ,
 `privilege` int(11) ,
 `link` varchar(64) ,
 `icon` varchar(128) ,
 `title` varchar(64) ,
 `position` int(2) ,
 `option` varchar(24) ,
 `root` int(2) ,
 `active` tinyint(1) ,
 `hidden` tinyint(1) unsigned ,
 `access` tinyint(4) ,
 `user` int(11) ,
 `show_in_frontpage` tinyint(1) ,
 `module` varchar(64) ,
 `glyphicon` varchar(64) ,
 `target` enum('_blank','_self','_parent','_top') 
)*/;

/*Table structure for table `sys_privileges` */

DROP TABLE IF EXISTS `sys_privileges`;

/*!50001 CREATE TABLE  `sys_privileges`(
 `gid` int(3) unsigned ,
 `gtitle` varchar(64) ,
 `pid` int(3) unsigned ,
 `position` int(2) ,
 `ptitle` varchar(64) ,
 `icon` varchar(128) ,
 `option` varchar(24) ,
 `link` varchar(64) 
)*/;

/*Table structure for table `sys_users_roles` */

DROP TABLE IF EXISTS `sys_users_roles`;

/*!50001 CREATE TABLE  `sys_users_roles`(
 `uid` int(10) unsigned ,
 `username` varchar(64) ,
 `rid` int(2) unsigned ,
 `name` varchar(32) 
)*/;

/*View structure for view sys_acls */

/*!50001 DROP TABLE IF EXISTS `sys_acls` */;
/*!50001 CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `sys_acls` AS select `a`.`privilege` AS `privilege`,`p`.`link` AS `link`,`a`.`access` AS `access`,`a`.`utype` AS `utype`,`a`.`appliesto` AS `user` from (`sys_acl` `a` join `sys_privilege` `p`) where `a`.`privilege` = `p`.`id` and `a`.`utype` = _latin1'u' union select `a`.`privilege` AS `privilege`,`p`.`link` AS `link`,`a`.`access` AS `access`,`a`.`utype` AS `utype`,`r`.`ur_user_id` AS `user` from ((`sys_acl` `a` join `sys_privilege` `p`) join `sys_user_role` `r`) where `a`.`privilege` = `p`.`id` and `r`.`ur_role_id` = `a`.`appliesto` and `a`.`utype` = _latin1'r' */;

/*View structure for view sys_permission */

/*!50001 DROP TABLE IF EXISTS `sys_permission` */;
/*!50001 CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `sys_permission` AS select `a`.`privilege` AS `id`,`a`.`privilege` AS `privilege`,`p`.`link` AS `link`,`p`.`icon` AS `icon`,`p`.`title` AS `title`,`p`.`position` AS `position`,`p`.`option` AS `option`,`p`.`root` AS `root`,`p`.`active` AS `active`,`p`.`hidden` AS `hidden`,`a`.`access` AS `access`,`a`.`user` AS `user`,`p`.`show_in_frontpage` AS `show_in_frontpage`,`p`.`module` AS `module`,`p`.`glyphicon` AS `glyphicon`,`p`.`target` AS `target` from (`sys_acls` `a` join `sys_privilege` `p`) where `a`.`privilege` = `p`.`id` */;

/*View structure for view sys_privileges */

/*!50001 DROP TABLE IF EXISTS `sys_privileges` */;
/*!50001 CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `sys_privileges` AS (select `g`.`id` AS `gid`,`g`.`title` AS `gtitle`,`p`.`id` AS `pid`,`p`.`position` AS `position`,`p`.`title` AS `ptitle`,`p`.`icon` AS `icon`,`p`.`option` AS `option`,`p`.`link` AS `link` from (`sys_privilege` `g` left join `sys_privilege` `p` on(`g`.`id` = `p`.`root`)) where `g`.`root` = 0 and `g`.`active` = 1 and `p`.`active` = 1 order by `g`.`position`,`g`.`title`,`p`.`link`,`p`.`position`) */;

/*View structure for view sys_users_roles */

/*!50001 DROP TABLE IF EXISTS `sys_users_roles` */;
/*!50001 CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `sys_users_roles` AS (select `u`.`id` AS `uid`,`u`.`u_username` AS `username`,`r`.`id` AS `rid`,`r`.`r_name` AS `name` from ((`sys_user` `u` join `sys_user_role` `ur`) join `sys_role` `r`) where `u`.`id` = `ur`.`ur_user_id` and `r`.`id` = `ur`.`ur_role_id`) */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
