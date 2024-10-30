<?php
namespace CacheUltra\Models;

class Cache {
    protected static $table = 'cu_caches';
    //status 1: cache files exist
    //status 2: cache files processing
    //status 3: there was an error processing cache files. message in cache notes column
    //status 4: Automatically loading resources because non were in existance in database
    protected static $columns = [
        'id' => '%d',
        'status' => '%d',
        'enabled' => '%d',
        'timestamp' => '%d',
        'post_id' => '%d',
        'files' => '%s',
        'notes' => '%s',
        'permalink' => '%s',
        'archive' => '%s',
        'type'  => '%s',
        'contains' => '%s',
    ];    
    protected static $defaults = [
        'status' => 1,
        'enabled' => 0,
        'timestamp' => 0,
        'post_id' => 0,
        'files' => [],
        'notes' => [],
        'permalink' => '',
        'archive' => NULL,
        'type' => 'auto',
        'contains' => NULL,
    ];
    public static $defaultSettings = [
        'lazyload' => false,
    ];

    public function __construct() {
        
    }
    public static function getPages() {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE type = 'auto' AND post_id != 0 AND timestamp != '0';";
        $caches = $wpdb->get_results( $sql, ARRAY_A );
        foreach($caches AS &$cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $caches;
    }
    public static function getEnabled(){
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE enabled = 1;";
        $caches = $wpdb->get_results( $sql, ARRAY_A );
        foreach($caches AS &$cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $caches;
    }
    public static function delete($id) {
        global $wpdb;
        $table = self::getTable();
        $wpdb->delete($table,['id' => $id]);
        return true;
    }
    public static function getNotes($id) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT notes FROM $table WHERE id = $id;";
        $cache = $wpdb->get_row( $sql, ARRAY_A );
        if($cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $cache;
    }
    public static function getTable() {
        global $wpdb;
        return $wpdb->prefix . self::$table;
    }
    public static function whereArchive($archive) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE archive = '$archive';";
        $cache = $wpdb->get_row( $sql, ARRAY_A );
        if($cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $cache;
    }
    public static function whereType($type) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE `type` = '$type';";
        $caches = $wpdb->get_results( $sql,ARRAY_A  );
        foreach($caches AS &$cache) {
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $caches;

    }
    public static function whereUrl($url,$withProtocol = true,$enabled = true) {
        $url = rtrim($url,"/");
        global $wpdb;
        $table = self::getTable();
        if($enabled){
            if($withProtocol){
                $sql = "SELECT * FROM $table WHERE type = 'custom' AND enabled = 1 AND ( 'http://$url/' LIKE CONCAT('%',contains,'%') OR 'http://$url' LIKE CONCAT('%',contains,'%') OR 'https://$url' LIKE CONCAT('%',contains,'%') OR 'https://$url/' LIKE CONCAT('%',contains,'%') )";
            } else{
                $sql = "SELECT * FROM $table WHERE type = 'custom' AND enabled = 1 AND ( '$url' LIKE CONCAT('%',contains,'%') OR '$url/' LIKE CONCAT('%',contains,'%') )";
            }
            $cache = $wpdb->get_row( $sql, ARRAY_A );
            if(!$cache){
                if($withProtocol){
                    $sql = "SELECT * FROM $table WHERE enabled = 1 AND permalink IN ('https://$url/','https://$url/','https://$url','https://$url');";
                } else{
                    $sql = "SELECT * FROM $table WHERE enabled = 1 AND permalink IN ('$url/','$url');";
                }
                $cache = $wpdb->get_row( $sql, ARRAY_A );
            }
            
        }else{
            if($withProtocol){
                $sql = "SELECT * FROM $table WHERE type = 'custom' AND ( 'http://$url/' LIKE CONCAT('%',contains,'%') OR 'http://$url' LIKE CONCAT('%',contains,'%') OR 'https://$url' LIKE CONCAT('%',contains,'%') OR 'https://$url/' LIKE CONCAT('%',contains,'%') )";
            } else{
                $sql = "SELECT * FROM $table WHERE type = 'custom' AND ( '$url' LIKE CONCAT('%',contains,'%') OR '$url/' LIKE CONCAT('%',contains,'%') )";
            }
            $cache = $wpdb->get_row( $sql, ARRAY_A );
            if(!$cache){
                if($withProtocol){
                    $sql = "SELECT * FROM $table WHERE permalink IN ('https://$url/','https://$url/','https://$url','https://$url');";
                } else{
                    $sql = "SELECT * FROM $table WHERE permalink IN ('$url/','$url');";
                }
                $cache = $wpdb->get_row( $sql, ARRAY_A );
            }
        }
        if($cache) {
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $cache;
    }
    public static function wherePostId($value) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE post_id = $value;";
        $cache = $wpdb->get_row( $sql, ARRAY_A );
        if($cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $cache;
    }
    public static function find($id) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE id = $id;";
        $cache = $wpdb->get_row( $sql, ARRAY_A );
        if($cache){
            foreach($cache AS $column => &$value) {
                $value = self::clean($column,$value);
            }
        }
        return $cache;
    }
    public static function save($data){
        if(isset($data['id']) && $data['id'] != '0'){
            $cache = self::find($data['id']);
            if($cache){
                return self::update($data);
            }else{
                return self::create($data);
            }
        }else{
            return self::create($data);
        }
    }
    public static function update($data) {
        global $wpdb;
        $insert = [
            'data' => [],
            'format' => [],
        ];
        foreach($data AS $column => $value){
            if($column == 'id') continue;
            if(isset(self::$columns[$column])){
                $insert['data'][$column] = self::parse($column,$value);
                $insert['format'][] = self::$columns[$column]; 
            }
        }
        $wpdb->update(self::getTable(),$insert['data'],['id' => $data['id']],$insert['format']);
        return $data['id'];

    }
    public static function create($data) {
        global $wpdb;
        $insert = [
            'data' => [],
            'format' => [],
        ];
        foreach($data AS $column => $value){
            if(isset(self::$columns[$column]) && $column != 'id'){
                $insert['data'][$column] = self::parse($column,$value);
                $insert['format'][] = self::$columns[$column]; 
            }
        }
        $wpdb->insert(self::getTable(),$insert['data'],$insert['format']);
        return $wpdb->insert_id;
    }

    public static function clean($column, $value){
        $method = $column . 'Clean';
        if(method_exists(__CLASS__, $method)){
            $value = self::$method($value);
        }
        return $value;
    }
    public static function enabledClean($value) {
        $value = $value == '0' ? NULL : '1';
        return $value;
    }
    public static function notesClean($value){
        return unserialize($value);
    }
    public static function filesClean($value){
        return unserialize($value);
    }
    public static function parse($column, $value) {
        $method = $column . 'Parse';
        if(method_exists(__CLASS__,$method)){
            $value = self::$method($value);
        }
        return $value;
    }
    public static function notesParse($value) {
        return serialize($value);
    }
    public static function filesParse($value) {
        return serialize($value);
    }
    public static function getNew() {
        return self::$defaults;
    }
}