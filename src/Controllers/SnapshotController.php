<?php 
namespace CacheUltra\Controllers;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Cache;
use CacheUltra\Models\Resource;
use CacheUltra\Services\Security;
use CacheUltra\Services\Resource AS ResourceService;
use CacheUltra\Services\Cache AS CacheService;
use CacheUltra\Services\Request AS RequestService;

class SnapshotController {

    use Singleton;

    private $url;

    private function __construct() {
        $this->url = CACHE_ULTRA_API_MODE == 'dev' ? 'https://464ff434.ngrok.io/' : 'https://cache.limelightdept.com/';
    }

    public function ValidateToken($token) {
        return Security::getInstance()->validate($token);
    }
    public function updateOptions($request) {
        $post_id = $request['post'];
        $cache = $request['cache'];
        if($post_id == '0'){
            $archive = $request['archive'];
            $permalink = get_post_type_archive_link($archive);
            $currrentCache = Cache::find($cache['id']);
        }else{
            $permalink = \get_permalink($post_id);
            $currrentCache = Cache::wherePostId($post_id);
            $archive = FALSE;
        }
        if($currrentCache) {
            $currrentCache['enabled'] = $cache['enabled'];
            $currrentCache['permalink'] = $permalink;
        }else{
            $currrentCache = Cache::getNew();
            $currrentCache['permalink'] = $permalink;
            $currrentCache['post_id'] = $post_id;
            $currrentCache['timestamp'] = false;
            $currrentCache['enabled'] = $cache['enabled'];
            $currrentCache['status'] = 1;
        }
        $currrentCache['archive'] = $archive;
        Cache::save($currrentCache);
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $currrentCache,
        ], 200 );

    }
    public function getArchiveSnapshot($request) {
        if($request['archive'] == '0'){
            $archive = 0;
            $cache = Cache::find($request['cache']['id']);
        }else{
            $archive = $request['archive']['id'];
            $cache = Cache::find($request['archive']['cache']['id']);
        }
        $loading = false;
        if($cache && $cache['status'] == 2) {
            $loading = true;
        }
        return new \WP_REST_Response( [
            'archive' => $archive,
            'loading' => $loading,
            'cache' => $cache,
            'snapshot' => null,
        ], 200 );
    }
    public function checkSnapshot($request) {
        $cache = Cache::find($request->get_param('id'));
        $loading = false;
        if($cache && $cache['status'] != 1) {
            $loading = true;
        }
        if($cache['post_id'] != '0'){
            $cache['post_title'] = \get_the_title($cache['post_id']);
        }
        return new \WP_REST_Response( [
            'loading' => $loading,
            'processing' => $loading,
            'cache' => $cache,
            'snapshot' => null,
        ], 200 );
    }
    public function getSnapshot($request) {
        $cache = Cache::wherePostId($request->get_param('id'));
        $loading = false;
        if($cache && $cache['status'] != 1) {
            $loading = true;
        }
        return new \WP_REST_Response( [
            'loading' => $loading,
            'processing' => $loading,
            'cache' => $cache,
            'snapshot' => null,
        ], 200 );
    }
    public function clearArchive($request) {
        if($request['archive'] == '0'){
            $cache = Cache::find($request['cache']['id']);
            $permalink = $cache['permalink'];
        } else{
            $cache = Cache::find($request['archive']['cache']['id']);
            $permalink = get_post_type_archive_link($request['archive']['id']);
        }
        if($cache){
            foreach($cache['files'] AS $key => $file){
                if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                    unlink(CACHE_ULTRA_CACHES_DIR.$file);
                } 
                unset($cache[$key]);
            }
        }
        $cache['notes']['scores'] = false;
        $cache['status'] = 1;
        $cache['timestamp'] = false;
        $cache['permalink'] = $permalink;
        Cache::save($cache);
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function clear($request) {
        $cache = Cache::wherePostId($request->get_param('id'));
        $permalink = \get_permalink($request->get_param('id'));
        if($cache){
            foreach($cache['files'] AS $key => $file){
                if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                    unlink(CACHE_ULTRA_CACHES_DIR.$file);
                } 
                unset($cache[$key]);
            }
            $cache['status'] = 1;
            $cache['timestamp'] = false;
            $cache['permalink'] = $permalink;
            $cache['notes']['scores'] = false;
            Cache::save($cache);
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function cancel($request) {
        $cache = Cache::wherePostId($request->get_param('id'));
        $permalink = \get_permalink($request->get_param('id'));
        if($cache){
            foreach($cache['files'] AS $key => $file){
                if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                    unlink(CACHE_ULTRA_CACHES_DIR.$file);
                } 
                unset($cache[$key]);
            }
            $cache['status'] = 1;
            $cache['timestamp'] = false;
            $cache['permalink'] = $permalink;
            $cache['notes']['scores'] = false;
            Cache::save($cache);
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function snapshot($request) {
        $post = $request['post'];
        $archive = '0';
        $timestamp = time();
        $resources = [];
        $token = Security::getInstance()->createToken();
        $cache_type = 'auto';
        if($post == '0'){
            if($request['archive'] == '0'){
                $archive = '0';
                $cache = $request['cache'];
                $permalink = $cache['permalink'];
                $settings =  \get_option('_cu_cache_settings_'.$cache['id'],Cache::$defaultSettings);
                $lazyload = $settings['lazyload'];
                $cache_type = 'custom';
            }else{
                $archive = $request['archive']['id'];
                $cache = $request['archive']['cache'];
                $permalink = get_post_type_archive_link($archive);
                $lazyload = \get_option('_cu_lazyload_'.$archive,false);
            }
            $resources = Resource::AllByTypeAndId($cache['id'],['javascript','css'],true);
            $enabled = $cache['enabled'];
        }else{
            $cache = Cache::wherePostId($post);
            $permalink = \get_permalink($post);
            $lazyload = \get_post_meta($post,'_cu_lazyload',true);
            $enabled = $request['form']['enabled'];
            $resources = Resource::AllByTypeAndId($post,['javascript','css']);
        }
        if($cache && isset($cache['id'])){
            if($cache['status'] != '1'){
                return new \WP_REST_Response( [
                    'success' => 2,
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
            $cache = Cache::getNew();
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
        try{
            if(!$resources || empty($resources)){
                $cache = ResourceService::getInstance()->process($cache,['javascript','css'],$timestamp);
                return new \WP_REST_Response( [
                    'success' => 4,
                    'status' => 'resources',
                    'message' => 'Loading Resources.',
                    'cache' => $cache,
                ], 200 );
            }else{
                Cache::save($cache);
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
                return new \WP_REST_Response( [
                    'success' => 1,
                ], 200 );
            }
        }catch(\Exception $e){
            return new \WP_REST_Response( [
                'success' => 1,
            ], 200 );
        }
    }
    public function save($request) {

        $token = $_POST['token'];
        $hash = md5($_POST['cache_id'].\get_site_url().$_POST['time']);
        $file = file_get_contents($this->url.'/storage/tmp/'.$hash);
        $post_data = unserialize(base64_decode($file));

        $client = new \GuzzleHttp\Client();
        $remote_response = $client->post($this->url.'api/files/unlink',  [
            'form_params'=> [
                'token' => $token,
                'hash' => $hash,
            ],
            'timeout' => 10,
            'connect_timeout' => 10,
        ]);

        $post = (int)sanitize_text_field($post_data['post']);
        $time = (int)sanitize_text_field($post_data['time']);
        $archive = sanitize_text_field($post_data['archive']);
        $identity = 'nothing-provided';
        if($post != '0') {
            $identity = $post;
            $cache = Cache::wherePostId($post);
        }elseif($archive != '0' ){
            $identity = $archive;
            $cache = Cache::whereArchive($archive);
        }else{
            $identity = 'custom_' . $request['cache']['id'];
            $cache = Cache::find($request['cache']['id']);
        }
        if($cache && $cache['status'] != 1) {
            $cache['files'] = [
                'post-'.$identity.'-'.$time.'-head.js',
                'post-'.$identity.'-'.$time.'-foot.js',
                'post-'.$identity.'-'.$time.'-head.css',
                'post-'.$identity.'-'.$time.'-foot.css',
                'post-'.$identity.'.html',
            ];
            $html = base64_encode(urldecode($post_data['html_content']));
            $js_head = urldecode($post_data['javascript_head']);
            $js_foot = urldecode($post_data['javascript_foot']);
            $css_head = urldecode($post_data['css_head']);
            $css_foot = urldecode($post_data['css_foot']);

            file_put_contents(CACHE_ULTRA_CACHES_DIR.'post-'.$identity.'-'.$time.'-head.js',$js_head);
            file_put_contents(CACHE_ULTRA_CACHES_DIR.'post-'.$identity.'-'.$time.'-foot.js',$js_foot);
            file_put_contents(CACHE_ULTRA_CACHES_DIR.'post-'.$identity.'-'.$time.'-head.css',$css_head);
            file_put_contents(CACHE_ULTRA_CACHES_DIR.'post-'.$identity.'-'.$time.'-foot.css',$css_foot);
            file_put_contents(CACHE_ULTRA_CACHES_DIR.'post-'.$identity.'.html',$html);
            $cache['status'] = 1;
            $cache['enabled'] = 1;
            Cache::save($cache);
            CacheService::getInstance()->performance($cache);
        }
    }
}