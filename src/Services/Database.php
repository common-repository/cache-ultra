<?php

namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;

class Database {

    use Singleton;
    
    private $version;

    public function __construct() {
        $this->version = get_option('_cu_plugin_version',false);        
        if(!$this->version) {
            $this->version = '0.0.0';
        }
        if(is_admin()){
            $this->checkTables();
        }
    }       
    private function checkTables() {
        global $wpdb;
        $caches_table_name = $wpdb->prefix . "cu_caches";
        $resources_table_name = $wpdb->prefix . "cu_resources";
        $charset_collate = $wpdb->get_charset_collate();
        $schema  = DB_NAME;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        if(version_compare($this->version, "1.1.8") <= 0){
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'dom_index';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $resources_table_name ADD dom_index int;
                " );
            }
        }
        if(version_compare($this->version, "1.1.3") <= 0){
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'contains';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $caches_table_name ADD contains TEXT;
                " );
            }
        }
        if(version_compare($this->version, "1.0.8") <= 0){
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'remove';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $resources_table_name ADD remove tinyint DEFAULT 0 NOT NULL;
                " );
            }
        }
        if(version_compare($this->version, "1.0.0") <= 0){
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'type';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $caches_table_name ADD type VARCHAR(255) DEFAULT 'auto' NOT NULL;
                " );
            }
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$resources_table_name' AND COLUMN_NAME = 'cache_id';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $resources_table_name ADD cache_id bigint;
                " );
            }
            $exists = $wpdb->get_results( "SELECT * FROM  information_schema.COLUMNS WHERE TABLE_SCHEMA = '$schema' AND TABLE_NAME = '$caches_table_name' AND COLUMN_NAME = 'archive';" );
            if(empty($exists)) {
                $wpdb->query( "
                    ALTER TABLE $caches_table_name ADD archive text;
                " );
            }
        }
    }
}
