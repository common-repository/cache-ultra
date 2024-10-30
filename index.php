<?php
/**
 * Plugin Name:       Cache Ultra
 * Plugin URI:        https://limelighttheme.com/cache-ultra/
 * Description:       Cache Ultra Plugin for Wordpress
 * Version:           1.1.11
 * Author:            Limelight Department
 * Author URI:        https://limelighttheme.com/
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CACHE_ULTRA_DIR', __DIR__);

define('CACHE_ULTRA_VERSION', '1.1.11');

define('CACHE_ULTRA_MODE', 'prod');

define('CACHE_ULTRA_API_MODE', 'prod');

define('CACHE_ULTRA_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

define('CACHE_ULTRA_FILE_PATH', __FILE__);

define('CACHE_ULTRA_NAMESPACE', __NAMESPACE__);

define ('CACHE_ULTRA_CACHES_DIR', wp_upload_dir()['basedir'] . '/cache-ultra/caches/');

define ('CACHE_ULTRA_TMP_DIR', wp_upload_dir()['basedir'] . '/cache-ultra/tmp/');

require CACHE_ULTRA_DIR . '/vendor/autoload.php';

$LIMECACHE =  \CacheUltra\Plugin::getInstance();


register_activation_hook(__FILE__, 'cache_ultra_plugin__activated');

function cache_ultra_plugin__activated() {
    global $wpdb;
    $caches_table_name = $wpdb->prefix . "cu_caches";
    $resources_table_name = $wpdb->prefix . "cu_resources";
    $charset_collate = $wpdb->get_charset_collate();

    $sql_caches = "CREATE TABLE IF NOT EXISTS $caches_table_name (
      `id` bigint NOT NULL AUTO_INCREMENT,
      `status` tinyint default 0 NOT NULL,
      `enabled` tinyint DEFAULT 0 NOT NULL,
      `timestamp` bigint DEFAULT 0 NOT NULL,
      `post_id` bigint NOT NULL,
      `files` TEXT,
      `notes` TEXT,
      `permalink` MEDIUMTEXT,
      `archive` TEXT,
      `type` VARCHAR(255) DEFAULT 'auto' NOT NULL,
      `contains` TEXT,
      PRIMARY KEY  (id)
    ) $charset_collate;";
    
    $sql_resources = "CREATE TABLE IF NOT EXISTS $resources_table_name (
        `id` bigint NOT NULL AUTO_INCREMENT,
        `timestamp` bigint DEFAULT 0 NOT NULL,
        `post_id` bigint NOT NULL,
        `cache_id` bigint NULL,
        `type` VARCHAR(255),
        `code` TEXT,
        `url` MEDIUMTEXT,
        `location` tinyint,
        `exclude` tinyint,
        `order` int,
        `remove` tinyint DEFAULT 0 NOT NULL,
        `dom_index` int,
        PRIMARY KEY  (id)
      ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_caches );
    dbDelta( $sql_resources );

    $schema  = DB_NAME;

    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'contains';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $caches_table_name ADD contains TEXT;
        " );
    }
    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'type';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $caches_table_name ADD type VARCHAR(255) DEFAULT 'auto' NOT NULL;
        " );
    }
    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'archive';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $caches_table_name ADD archive text;
        " );
    }
    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'remove';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $resources_table_name ADD remove tinyint DEFAULT 0 NOT NULL;
        " );
    }
    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'cache_id';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $resources_table_name ADD cache_id bigint;
        " );
    }
    $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'dom_index';" );
    if(empty($exists)) {
        $wpdb->query( "
            ALTER TABLE $resources_table_name ADD dom_index int;
        " );
    }
}
