<?php 

namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Cache AS CacheModel;
use CacheUltra\Models\Resource;
use CacheUltra\Services\Security;
use CacheUltra\Services\Resource AS ResourceService;
use CacheUltra\Services\Request AS RequestService;

class Cache {

    use Singleton;

    private $url;

    public function __construct() {
        $this->url = CACHE_ULTRA_API_MODE == 'dev' ? 'https://464ff434.ngrok.io/' : 'https://cache.limelightdept.com/';
        add_filter('cron_schedules',[$this, 'CronSchedule']);
        if ( ! wp_next_scheduled( 'cu_CheckCaches' ) ) {
            wp_schedule_event( time(), 'every_eight_hours', 'cu_CheckCaches' );
        }
        add_action( 'cu_CheckCaches', [$this,'CheckCaches'] );
    }
    public static function getSiteUrls() {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        $sql = "SELECT `guid`,`ID` FROM $table;";
        $urls = $wpdb->get_results( $sql, ARRAY_A );
        return $urls;
    }
    public function performance($cache) {
        if($cache['type'] == 'custom' || $cache['post_id'] == '0'){
            \update_option('cu_processing_performance_'.$cache['id'],true);
        }else{
            \update_post_meta($cache['post_id'],'cu_processing_performance',true);
        }
        $token = Security::getInstance()->createToken();
        $request_data = [
            'token' => $token,
            'cache' => $cache,
            'time' => $cache['timestamp'],
        ];
        try{ 
            RequestService::getInstance()->request($request_data,$this->url.'api/snapshot/performance/'.$cache['id'],'performance');
        }catch(\Exception $e) {
            return true;
        }
        return true;
    }
    public function CheckCaches() {
        $caches = CacheModel::getEnabled();
        $messages = [];
        foreach($caches AS $cache) {

            if($cache['status'] != '1') {
                $messages[$cache['id']] = 'this cache is processing.';
                continue;
            }
            $timestamp = time();
            $checkTime = $timestamp - $cache['timestamp'];
            if($checkTime < 28800){
                $messages[$cache['id']] = 'has not yet been eight hours.';
                continue;
            }
            ResourceService::getInstance()->process($cache,['javascript','css'],$cache['timestamp']);
            //$this->processCache($cache);
        }
    }
    public function CronSchedule($schedules){
        $schedules['every_eight_hours'] = [
            'interval' => 28800,
            'display' => __('Every 8 hours'),
        ];
        return $schedules;
    }
    public function processCache($cache){
        if($cache['status'] != '1') return 'this cache is processing.';
        $timestamp = time();
        $checkTime = $timestamp - $cache['timestamp'];
        if($checkTime < 28800) return 'has not yet been eight hours.';
        $archive = 0;
        $enabled = 0;
        $resources = [];
        $lazyload = false;
        $token = Security::getInstance()->createToken();
        $cache_type = 'auto';
        if($cache['post_id'] == '0'){
            if($cache['archive'] == '0'){
                $archive = 0;
                $permalink = $cache['permalink'];
                $settings =  \get_option('_cu_cache_settings_'.$cache['id'],CacheModel::$defaultSettings);
                $lazyload = $settings['lazyload'];
                $cache_type = 'custom';
            }else{
                $archive = $cache['archive'];
                $permalink = get_post_type_archive_link($archive);
                $lazyload = \get_option('_cu_lazyload_'.$archive,false);
            }
            $resources = Resource::AllByTypeAndId($cache['id'],['javascript','css'],true);
        }else{
            $permalink = \get_permalink($cache['post_id']);
            $lazyload = \get_post_meta($cache['post_id'],'_cu_lazyload',true);
            $resources = Resource::AllByTypeAndId($cache['post_id'],['javascript','css']);
        }
        if($cache && isset($cache['id'])){
            foreach($cache['files'] AS $key => $file){
                if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                    unlink(CACHE_ULTRA_CACHES_DIR.$file);
                } 
                unset($cache[$key]);
            }
            $cache['permalink'] = $permalink;
            $cache['timestamp'] = $timestamp;
            $cache['enabled'] = $enabled;
            $cache['status'] = 2;
            if(!is_array($cache['notes'])) {
                $cache['notes'] = [
                    'tokens' => [
                        'snapshot' => $token,
                    ],
                ];
            }else{
                $cache['notes']['tokens']['snapshot'] = $token;
            }
        }else{
            $cache = CacheModel::getNew();
            $cache['permalink'] = $permalink;
            $cache['post_id'] = $cache['post_id'];
            $cache['timestamp'] = $timestamp;
            $cache['enabled'] = $enabled;
            $cache['status'] = 2;
            $cache['type'] = $cache_type;
            $cache['notes'] = [
                'tokens' => [
                    'snapshot' => $token,
                ]
            ];
        }
        CacheModel::save($cache);
        $request_data = [
            'token' => $token,
            'lazyload' => $lazyload,
            'time' => $timestamp,
            'post_id' => $cache['post_id'],
            'archive' => $archive,
            'url' => $permalink,
            'resources' => $resources,
            'cache' => $cache,
        ];
        RequestService::getInstance()->request($request_data,$this->url.'api/snapshot/process/'.$cache['id'],'snapshot');
        return new \WP_REST_Response( [
            'success' => 1,
        ], 200 );
    }
    public static function create() {
        $cache = CacheModel::getNew();
        $cache['permalink'] = $permalink;
        $cache['post_id'] = $post_id;
        $cache['timestamp'] = false;
        $cache['enabled'] = $cache['enabled'];
        $cache['status'] = 1;
        $cache['archive'] = $archive;
        CacheModel::save($currrentCache);
    }
    public static function customCaches() {
        $caches = CacheModel::whereType('custom');
        foreach($caches AS &$cache) {
            $cache['processing_performance'] = \get_option('cu_processing_performance_'.$cache['id'],false);
        }
        return $caches;
    }
    public static function archives() {
        $archives = [];
        foreach(get_post_types(['public' => true,],'objects') AS $type) {
            $url = get_post_type_archive_link($type->name);
            if($url){
                $cache = CacheModel::whereArchive($type->name);
                if(!$cache){
                    $cache = CacheModel::getNew();
                    $cache['permalink'] = $url;
                    $cache['timestamp'] = 0;
                    $cache['notes'] = [];
                    $cache['archive'] = $type->name;
                }else{
                    $cache['permalink'] = $url;
                }
                $cache['id'] = CacheModel::save($cache);
                $archives[] = [
                    'id' => $type->name,
                    'url' => $url,
                    'label' => $type->label,
                    'cache' => $cache,
                    'processing_performance' => \get_option('cu_processing_performance_'.$cache['id'],false),
                ];
            }
        }
        return $archives;
    }

    public function snapshot($cache) {
        try{
            $post = $cache['post_id'];
            $archive = $cache['archive'];
            $timestamp = $cache['timestamp'];
            $resources = [];
            $token = Security::getInstance()->createToken();
            $cache_type = $cache['type'];
            $lazyload = false;
            if($post == '0'){
                if($archive == '0'){
                    $archive = 0;
                    $permalink = $cache['permalink'];
                    $settings =  \get_option('_cu_cache_settings_'.$cache['id'],CacheModel::$defaultSettings);
                    $lazyload = $settings['lazyload'];
                    $cache_type = 'custom';
                }else{
                    $permalink = get_post_type_archive_link($archive);
                    $lazyload = \get_option('_cu_lazyload_'.$archive,false);
                }
                $resources = Resource::AllByTypeAndId($cache['id'],['javascript','css'],true);
                $enabled = $cache['enabled'];
            }else{
                $cache = CacheModel::wherePostId($post);
                $permalink = \get_permalink($post);
                $lazyload = \get_post_meta($post,'_cu_lazyload',true);
                $enabled = 0;
                $resources = Resource::AllByTypeAndId($post,['javascript','css']);
            }
            if($cache && isset($cache['id'])){
                if($cache['status'] != '1' && $cache['status'] != '4'){
                    return new \WP_REST_Response( [
                        'success' => 1,
                        'message' => 'Cache is already processing.',
                    ], 200 );
                }
                foreach($cache['files'] AS $key => $file){
                    if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                        unlink(CACHE_ULTRA_CACHES_DIR.$file);
                    } 
                    unset($cache[$key]);
                }
                $cache['permalink'] = $permalink;
                $cache['timestamp'] = $timestamp;
                $cache['enabled'] = $enabled;
                $cache['status'] = 2;
                if(!is_array($cache['notes'])) {
                    $cache['notes'] = [
                        'tokens' => [
                            'snapshot' => $token,
                        ],
                    ];
                }else{
                    $cache['notes']['tokens']['snapshot'] = $token;
                }
            }else{
                $cache = CacheModel::getNew();
                $cache['permalink'] = $permalink;
                $cache['post_id'] = $post;
                $cache['timestamp'] = $timestamp;
                $cache['enabled'] = $enabled;
                $cache['status'] = 2;
                $cache['type'] = $cache_type;
                $cache['notes'] = [
                    'tokens' => [
                        'snapshot' => $token,
                    ]
                ];
            }
            CacheModel::save($cache);
            $request_data = [
                'token' => $token,
                'lazyload' => $lazyload,
                'time' => $timestamp,
                'post_id' => $post,
                'archive' => $archive,
                'url' => $permalink,
                'resources' => $resources,
                'cache' => $cache,
            ];
            RequestService::getInstance()->request($request_data,$this->url.'api/snapshot/process/'.$cache['id'],'snapshot');

        }catch(\Exception $e){
        }
    }
}