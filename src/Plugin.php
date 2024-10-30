<?php

namespace CacheUltra;

use CacheUltra\Traits\Singleton;
use CacheUltra\Services\Metabox;
use CacheUltra\Services\Api;
use CacheUltra\Services\Database;
use CacheUltra\Services\Pages;
use CacheUltra\Models\Cache;
use CacheUltra\Services\Cache AS CacheService;
use Spatie\Url\Url;

class Plugin {

    use Singleton;
    
    public $version = CACHE_ULTRA_VERSION;

    private $env = CACHE_ULTRA_MODE;

    private $metabox;

    private $api;

    private $database;

    private $cache;

    private $handles = [
        'toplevel_page_cache-ultra',
        'cache-ultra_page_post-type-caches',
        'cache-ultra_page_custom-caches',
    ];
    public function __construct() {
        $this->database = Database::getInstance();
        $this->metabox = Metabox::getInstance();
        $this->init();
        Pages::getInstance();
        update_option('_cu_plugin_version',CACHE_ULTRA_VERSION);
    }
    public function init(){
        \wp_mkdir_p(CACHE_ULTRA_CACHES_DIR);
        \wp_mkdir_p(CACHE_ULTRA_TMP_DIR);
        $this->env = $this->env == 'dev' ? '' : '.min';
        $this->api = Api::getInstance();
        add_action( 'admin_enqueue_scripts', [$this, 'admin_enqueue'] );
        add_filter( 'init', [$this, 'CheckCache'] );
        CacheService::getInstance();
    }
    public function CheckCache($query) {
        if (defined('DOING_AJAX') && DOING_AJAX) return;
        if(isset($_GET['cu-cache']) && $_GET['cu-cache'] == 'prevent') {
            return;
        }
        if( current_user_can('administrator') ) {
            return;
        }
        $enabled = true;
        //$url = $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"],'?');
        $url = $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
        if( isset($_GET['cu-performance']) &&  $_GET['cu-performance'] == 'force' ) {
            $enabled = false;
            $url = $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
            $url = Url::fromString($url);
            $url = $url->withoutQueryParameter('cu-performance')->__toString();
        }
        $this->cache = Cache::whereUrl($url,true,$enabled);
        if($this->cache && count($this->cache['files']) > 1) {
            $file = CACHE_ULTRA_CACHES_DIR .$this->cache['files'][4];
            if(file_exists($file)) {
                echo base64_decode(file_get_contents($file));
                die;
            }
        }
    }
    public function admin_enqueue($handle) {
        global $post;
        $meta = [
            'lazyload' => false,
        ];
        if($post){
            foreach($meta AS $key => &$value) {
                $value = \get_post_meta($post->ID, '_cu_'.$key, true);
            }
            $loading_snapshot = false;
            $cache = Cache::wherePostId($post->ID);
            $processing_performance = \get_post_meta($post->ID, 'cu_processing_performance',true);
            if($cache) {
                if($cache['status'] != 1) {
                    $loading_snapshot = true;
                }
            }else{
                $cache = Cache::getNew();
                $cache['permalink'] = \get_permalink($post->ID);
                $cache['post_id'] = $post->ID;
                $cache['timestamp'] = false;
                $cache['enabled'] = 0;
                $cache['status'] = 1;
                Cache::save($cache);
            }
            $resource_loading = [
                'javascript' => \get_post_meta($post->ID,'cu_processing_javascript',true),
                'css' => \get_post_meta($post->ID,'cu_processing_css',true),
            ];
            wp_enqueue_style('cache-ultra-admin-font', '//fonts.googleapis.com/css?family=Roboto:400,500,700,400italic|Material+Icons');
            wp_enqueue_style('cache-ultra-admin-styles', plugins_url( 'cache-ultra' ) . '/dist/snapshot'.$this->env.'.css');
            $url = plugins_url( 'cache-ultra' ) . '/dist/snapshot'.$this->env.'.js';
            wp_register_script( 'cache-ultra-admin-js', $url,['jquery'], $this->version,true );
            wp_localize_script( 'cache-ultra-admin-js', 'CacheUltra', [
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'root' => esc_url_raw( rest_url() ),
                'assets' =>  plugins_url( 'cache-ultra' ) . '/assets/',
                'post_id' => $post->ID,
                'snapshot_loading' => $loading_snapshot,
                'cache' => $cache,
                'resource_loading' => $resource_loading,
                'meta' => $meta,
                'processing_performance' => $processing_performance,
            ] );
            wp_enqueue_script('cache-ultra-admin-font-awesome','https://kit.fontawesome.com/88badb7223.js',['jquery'],$this->version,true);
            wp_enqueue_script( 'cache-ultra-admin-js' );
        }
        if(in_array($handle,$this->handles)) {
            wp_enqueue_style('cache-ultra-admin-font', '//fonts.googleapis.com/css?family=Roboto:400,500,700,400italic|Material+Icons');
            wp_enqueue_style('cache-ultra-settings-styles', plugins_url( 'cache-ultra' ) . '/dist/settings'.$this->env.'.css');
            wp_register_script( 'cache-ultra-settings-js', plugins_url( 'cache-ultra' ) . '/dist/settings'.$this->env.'.js',['jquery'], $this->version,true );
            wp_localize_script( 'cache-ultra-settings-js', 'CacheUltraSettings', [
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'root' => esc_url_raw( rest_url() ),
                'assets' =>  plugins_url( 'cache-ultra' ) . '/assets/',
                'archives' => CacheService::archives(),
                'customCaches' => CacheService::customCaches(),
                'site_url' => \site_url(),
                'site_urls' => CacheService::getSiteUrls(),
            ] );
            wp_enqueue_script('cache-ultra-admin-font-awesome','https://kit.fontawesome.com/88badb7223.js',['jquery'],$this->version,true);
            wp_enqueue_script( 'cache-ultra-settings-js' );
        }
    }

}