<?php  
namespace CacheUltra\Models;


class Resource {
    protected static $table = 'cu_resources';
    protected static $columns = [
        'id' => '%d',
        'timestamp' => '%d',
        'post_id' => '%d',
        'type' => '%s',
        'code' => '%s',
        'url' => '%s',
        'location' => '%d',
        'order' => '%d',
        'exclude' => '%d',
        'cache_id' => '%s',
        'remove' => '%d',
        'dom_index' => '%d',
    ];    
    protected static $defaults = [
        'timestamp' => 0,
        'post_id' => 0,
        'type' =>  '',
        'code' => '',
        'url' => '',
        'location' => 0,
        'order' => 0,
        'exclude' => 0,
        'cache_id' => 0,
        'remove' => 0,
        'dom_index' => NULL,
    ];

    public function __construct() {
        
    }
    public static function delete($id,$field = 'cache_id') {
        global $wpdb;
        $table = self::getTable();
        $wpdb->delete($table,[$field => $id]);
        return true;
    }
    public static function getTable() {
        global $wpdb;
        return $wpdb->prefix . self::$table;
    }
    public static function ByIndex($index,$cache_id,$type){
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE cache_id = $cache_id AND type = '$type' AND dom_index = $index;";
        $result = $wpdb->get_row( $sql, ARRAY_A );
        return $result;
    }
    public static function ByCode($code,$id,$cache = false){
        global $wpdb;
        if(!self::is_base64_encoded($code)) $code = base64_encode($code);
        $table = self::getTable();
        $sql = "SELECT * FROM $table AS r WHERE r.post_id = $id AND r.code = '$code';";
        if($cache){
            $sql = "SELECT * FROM $table AS r WHERE r.cache_id = $id AND r.code = '$code';";
        }
        $result = $wpdb->get_row( $sql, ARRAY_A );
        return $result;
    }

    public static function ByUrl($url,$id,$cache = false){
        global $wpdb;
        $table = self::getTable();
        if(!self::is_base64_encoded($url)) $url = base64_encode($url);
        $sql = "SELECT * FROM $table AS r WHERE r.post_id = $id AND r.url = '$url';";
        if($cache) {
            $sql = "SELECT * FROM $table AS r WHERE r.cache_id = $id AND r.url = '$url';";
        }
        $result = $wpdb->get_row( $sql, ARRAY_A );
        return $result;
    }

    public static function find($id) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE id = $id;";
        $result = $wpdb->get_row( $sql, ARRAY_A );
        return $result;
    }
    public static function AllByTypeAndCacheId($cache_id,$types) {
        $in = '';
        $last = end($types);
        foreach($types AS $type) {
            $in .= "'".$type.".inline','".$type.".links'";
            if($type != $last) $in .= ',';
        }
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE cache_id = $cache_id AND type IN ($in);";
        $results = $wpdb->get_results( $sql,ARRAY_A  );
        $return = [];
        foreach($results AS $key => &$value) {
            $return[$key] = self::clean($value);
        }
        return $return;
    }
    public static function AllByTypeAndId($id,$types, $isArchive = false) {
        $in = '';
        $last = end($types);
        foreach($types AS $type) {
            $in .= "'".$type.".inline','".$type.".links'";
            if($type != $last) $in .= ',';
        }
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE post_id = $id AND type IN ($in);";
        if($isArchive) {
            $sql = "SELECT * FROM $table WHERE cache_id = $id AND type IN ($in);";
        }
        $results = $wpdb->get_results( $sql,ARRAY_A  );
        $return = [];
        foreach($results AS $key => &$value) {
            $return[$key] = self::clean($value);
        }
        return $return;

    }
    public static function AllByTypeAndPostId($id, $types){
        $in = '';
        $last = end($types);
        foreach($types AS $type) {
            $in .= "'".$type.".inline','".$type.".links'";
            if($type != $last) $in .= ',';
        }
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE post_id = $id AND type IN ($in);";
        $results = $wpdb->get_results( $sql,ARRAY_A  );
        $return = [];
        foreach($results AS $key => &$value) {
            $return[$key] = self::clean($value);
        }
        return $return;

    }
    public static function AllByPostId($id) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT DISTINCT r.id,r.timestamp,r.post_id,r.type,r.code,r.url,r.location,r.order FROM $table AS r WHERE r.post_id = $id;";
        $results = $wpdb->get_results( $sql,ARRAY_A  );
        return $results;

    }
    public static function flushTypeWherePostId($id,$type) {
        global $wpdb;
        $table = self::getTable();
        $wpdb->query( 
            $wpdb->prepare( 
                "
                DELETE FROM $table
                 WHERE post_id = %d
                 AND ( type = %s OR type = %s )
                ",
                    $id,$type.'.links',$type.'.inline'
                )
        );
        return true;
    }
    public static function flushByType($type,$id,$isArchive = false) {
        global $wpdb;
        $table = self::getTable();
        $sql = 
            "
            DELETE FROM $table
            WHERE `post_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' );
            ";
        if($isArchive){
            $sql = 
                "
                DELETE FROM $table
                WHERE `cache_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' );
                ";
        }
        return $wpdb->query($sql);
    }
    public static function flush($id, $keepers,$type,$cache = false) {
        $keep = implode(',',$keepers);
        global $wpdb;
        $table = self::getTable();
        $sql = 
            "
            DELETE FROM $table
             WHERE `post_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' );
            ";
        if(!empty($keep)){
            $sql = 
            "
            DELETE FROM $table
                WHERE `post_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' ) AND `id` NOT IN ($keep);
            ";
        }
        if($cache) {
            $sql = 
                "
                DELETE FROM $table
                 WHERE `cache_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' );
                ";
            
            if(!empty($keep)){
                $sql = 
                    "
                    DELETE FROM $table
                     WHERE `cache_id` = $id AND ( `type` = '$type.inline' OR `type` = '$type.links' ) AND `id` NOT IN ($keep);
                    ";
            }
        }
        return $wpdb->query($sql);
    }
    public static function flushWherePostId($id) {
        global $wpdb;
        $table = self::getTable();
        $wpdb->query( 
            $wpdb->prepare( 
                "
                DELETE FROM $table
                 WHERE post_id = %d
                ",
                    $id 
                )
        );
        return true;
    }
    public static function wherePostId($value) {
        global $wpdb;
        $table = self::getTable();
        $sql = "SELECT * FROM $table WHERE post_id = $value;";
        $array = $wpdb->get_row( $sql, ARRAY_A );
        return $array;
    }
    public static function save($data){
        if(isset($data['id'])){
            $resource = self::find($data['id']);
            if($resource){
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
            if(isset(self::$columns[$column])){
                $insert['data'][$column] = self::parse($column,$value);
                $insert['format'][] = self::$columns[$column]; 
            }
        }
        $wpdb->insert(self::getTable(),$insert['data'],$insert['format']);
        return $wpdb->insert_id;
    }

    public static function clean($value){
        $clean = [
            'code' => 'codeClean',
            'url' => 'urlClean',
        ];
        foreach($clean AS $column => $method) {
            if(method_exists(__CLASS__, $method)){
                $value[$column] = self::$method($value[$column]);
            }
        }
        return $value;
    }
    public static function urlClean($value) {
        if(self::is_base64_encoded($value)){
            return base64_decode($value);
        }
        return $value;
    }
    public static function codeClean($value) {
        if(self::is_base64_encoded($value)){
            return base64_decode($value);
        }
        return $value;
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
    public static function is_base64_encoded($data)
    {
        if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $data)) {
            return TRUE;
        } 
        else {
            return FALSE;
        }
    }
    public static function urlParse($value) {
        if(!self::is_base64_encoded($value)){
            return base64_encode($value);
        }
        return $value;
    }
    public static function codeParse($value) {
        if(!self::is_base64_encoded($value)){
            return base64_encode($value);
        }
        return $value;
    }
    public static function filesParse($value) {
        return serialize($value);
    }
    public static function getNew() {
        return self::$defaults;
    }
}