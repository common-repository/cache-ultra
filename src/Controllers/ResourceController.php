<?php 
namespace CacheUltra\Controllers;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Resource;
use CacheUltra\Models\Cache;
use CacheUltra\Services\Security;
use CacheUltra\Services\Resource AS ResourceService;
use CacheUltra\Services\Request AS RequestService;

class ResourceController {

    use Singleton;

    private $processing = [
        'javascript' => 'cu_processing_javascript',
        'css' => 'cu_processing_css',
    ];
    private $settings = [
        'lazyload',
    ];
    private $prefix = '_cu_';

    private $url;

    private function __construct() {
        $this->url = CACHE_ULTRA_API_MODE == 'dev' ? 'https://464ff434.ngrok.io/' : 'https://cache.limelightdept.com/';
    }

    public function ValidateToken($token) {
        return Security::getInstance()->validate($token);
    }
    public function saveResources($request) {
        $post = $request['post'];
        $archive = $request['archive'];
        $cache = $request['cache'];
        $resources = $request['resources'];
        $settings = $request['settings'];
        if($post == '0'){
            foreach($settings AS $key => $value){
                \update_option($this->prefix.$key.'_'.$archive,$value);
            }
        }else{
            foreach($settings AS $key => $value){
                \update_post_meta($request['post'],'_cu_'.$key,$value);
            }
        }
        foreach($resources AS $type => $items) {
            foreach($items AS $order => $resource) {
                $oldResource = Resource::find($resource['id']);
                if($oldResource){
                    $oldResource['exclude'] = $resource['exclude'];
                    $oldResource['remove'] = $resource['remove'];
                    $oldResource['location'] = $resource['location'];
                    $oldResource['order'] = $order;
                    $oldResource['cache_id'] = $cache;
                    Resource::save($oldResource);
                }
            }
        }
        return new \WP_REST_Response( [
            'success' => 0,
            'message' => 'Resources Saved Successfully',
        ], 200 );
    }
    public function flushResources($request) {
        $types = $request['types'];
        $post = $request['post'];
        $cache = $request['cache'];
        if($post == '0'){
            foreach($types AS $type) {
                Resource::flushByType($type,$cache,true);
            }
        }else{
            foreach($types AS $type) {
                Resource::flushByType($type,$post);
            }
        }
        return new \WP_REST_Response( [
            'success' => 1,
        ], 200 );


    }
    public function saveResource($request) {
        $resource = Resource::find($request['resource']['id']);
        if($resource){
            $resource['exclude'] = $request['resource']['exclude'];
            $resource['remove'] = $request['resource']['remove'];
            $resource['location'] = $request['resource']['location'];
            $resource['order'] = $request['resource']['order'];
            Resource::save($resource);
            return new \WP_REST_Response( [
                'success' => 1,
                'resource' => $resource,
            ], 200 );
        }
        return new \WP_REST_Response( [
            'success' => 0,
            'resource' => false,
            'message' => 'Resource does not exist',
        ], 200 );


    }
    public function cancel($request) {
        $post = $request['post'];
        if($post != '0') {
            foreach($request['types'] AS $type) {
                \update_post_meta($post,$this->processing[$type], false);
            }
        }
        return new \WP_REST_Response( [
            'success' => 0,
            'message' => 'Resources Canceled',
        ], 200 );
    }
    public function all($request) {
        $settings = [];
        if($request['post'] == '0') {
            $resources = Resource::AllByTypeAndCacheId($request['cache'],$request['types']);
            foreach($this->settings AS $setting){
                $settings[$setting] = \get_option($this->prefix.$setting.'_'.$request['archive'],false);
            }
        }else{
            $resources = Resource::AllByTypeAndPostId($request['post'],$request['types']);
            foreach($this->settings AS $setting){
                $settings[$setting] = \get_post_meta($request['post'],$this->prefix.$setting,true);
            }
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'resources' => $resources,
            'settings' => $settings,
        ], 200 );
        
    }
    public function check($request) {
        $loading = [];
        if($request['post'] != '0') {
            foreach($request['types'] AS $type) {
                $loading[$type] = \get_post_meta($request['post'],$this->processing[$type],true);
            }
        }else{
            foreach($request['types'] AS $type) {
                $loading[$type] = \get_option($this->processing[$type] . '_' . $request['cache'],false);
            }
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'loading' => $loading,
        ], 200 );
    }
    public function process($request) {
        $post = $request['post'];
        $archive = '0';
        $cache = $request['cache'];
        if($cache['type'] == 'custom'){
            $permalink = $cache['permalink'];
            foreach($request['types'] AS $type) {
                \update_option($this->processing[$type] . '_' . $cache['id'], true);
            }
        }elseif($post == '0'){
            $archive = $request['archive'];
            $permalink = get_post_type_archive_link($archive);
            foreach($request['types'] AS $type) {
                \update_option($this->processing[$type] . '_' . $cache['id'], true);
            }
        }else{
            $permalink = \get_permalink($post);
            foreach($request['types'] AS $type) {
                \update_post_meta($post,$this->processing[$type], true);
            }
            $cache = Cache::wherePostId($post);
        }
        $timestamp = time();
        $token = Security::getInstance()->createToken();
        $request_data = [
            'token' => $token,
            'types' => $request['types'],
            'time' => $timestamp,
            'archive' => $archive,
            'cache' => $cache,
            'post_id' => $post,
            'url' => $permalink,
        ];
        RequestService::getInstance()->request($request_data,$this->url.'api/resources/process/'.$cache['id'],'map_resources');
        return new \WP_REST_Response( [
            'success' => 1,
        ], 200 );
    }
    public function save($request) { 
        ResourceService::getInstance()->save();
    }

}