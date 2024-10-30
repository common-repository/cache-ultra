<?php 

namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Cache;
use CacheUltra\Models\Resource AS ResourceModel;
use CacheUltra\Services\Security;
use CacheUltra\Services\Cache AS CacheService;
use CacheUltra\Services\Request AS RequestService;

class Resource {

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
    
    public function process($cache,$types,$timestamp) {
        $post = $cache['post_id'];
        $archive = $cache['archive'];
        if($cache['type'] == 'custom'){
            $permalink = $cache['permalink'];
            foreach($types AS $type) {
                \update_option($this->processing[$type] . '_' . $cache['id'], true);
            }
        }elseif($post == '0'){
            $permalink = get_post_type_archive_link($archive);
            foreach($types AS $type) {
                \update_option($this->processing[$type] . '_' . $cache['id'], true);
            }
        }else{
            $permalink = \get_permalink($post);
            foreach($types AS $type) {
                \update_post_meta($post,$this->processing[$type], true);
            }
            $cache = Cache::wherePostId($post);
        }
        $cache['timestamp'] = $timestamp;
        $cache['status'] = 4;
        Cache::save($cache);
        $token = Security::getInstance()->createToken();
        $request_data = [
            'token' => $token,
            'types' => $types,
            'time' => $timestamp,
            'archive' => $archive,
            'cache' => $cache,
            'post_id' => $post,
            'url' => $permalink,
        ];
        $headers = ['Referrer' => \get_site_url()];
        try{
            RequestService::getInstance()->request($request_data,$this->url.'api/resources/process/'.$cache['id'],'map_resources');
            return $cache;
        }catch(\Exception $e){
            return $cache;
        }
    }

    public function save() {
        $resource_keys = [
            'javascript' => 3,
            'css' => 1,
        ];
        $hash = md5($_POST['cache_id'].\get_site_url().$_POST['time']);
        $file = file_get_contents($this->url.'/storage/tmp/'.$hash);
        $data = unserialize(base64_decode($file));
        $token = $data['token'];

        $client = new \GuzzleHttp\Client();
        $remote_response = $client->post($this->url.'api/files/unlink',  [
            'form_params'=> [
                'token' => $token,
                'hash' => $hash,
            ],
            'timeout' => 10,
            'connect_timeout' => 10,
        ]);

        $archive = sanitize_text_field($data['archive']);
        $post = (int)sanitize_text_field($data['post']);
        $time = (int)sanitize_text_field($data['time']);
        $isArchive = ($archive == '0' || $archive == '') ? false : true ;
        $keepers = [];
        $cache_id = (int)sanitize_text_field($data['cache']['id']);
        $cache = Cache::find($cache_id);
        if($cache){
            foreach($resource_keys AS $key => $location) {
                if(isset($data[$key]) && $data[$key] != '0'){
                    $resources = $data[$key];
                    if(!empty($resources)){
                        foreach($resources AS $order => $item) {
                            if($item['type'] == 'inline') {
                                if($isArchive || $cache['type'] == 'custom') {
                                    $resource = ResourceModel::ByCode($item['code'],$cache['id'],true);
                                }else{
                                    $resource = ResourceModel::ByCode($item['code'], $post);
                                }
                                if(!$resource){
                                    $resource = ResourceModel::ByIndex((int)$item['index'],(int)$cache['id'],$key.'.inline');
                                    if(!$resource) {
                                        $resource = ResourceModel::getNew();
                                        $resource['location'] = $location;
                                    }
                                }
                                $resource['dom_index'] = $item['index'];
                                $resource['code'] = $item['code'];
                                $resource['timestamp'] = $time;
                                $resource['post_id'] = $post;
                                $resource['archive'] = $archive;
                                $resource['type'] = $key.'.inline';
                                $resource['order'] = $order;
                                $resource['cache_id'] = $cache['id'];
                                $keepers[] = ResourceModel::save($resource);
                            }
                            if($item['type'] == 'links') {
                                if($isArchive || $cache['type'] == 'custom') {
                                    $resource = ResourceModel::ByUrl($item['url'],$cache['id'],true);
                                }else{
                                    $resource = ResourceModel::ByUrl($item['url'],$post);
                                }
                                if(!$resource){
                                    $resource = ResourceModel::ByIndex((int)$item['index'],(int)$cache['id'],$key.'.links');
                                    if(!$resource) {
                                        $resource = ResourceModel::getNew();
                                        $resource['location'] = $location;
                                    }
                                }
                                $resource['dom_index'] = $item['index'];
                                $resource['url'] = $item['url'];
                                $resource['timestamp'] = $time;
                                $resource['post_id'] = $post;
                                $resource['archive'] = $archive;
                                $resource['type'] = $key.'.links';
                                $resource['order'] = $order;
                                $resource['cache_id'] = $cache['id'];
                                $keepers[] = ResourceModel::save($resource);
                            }
                        }
                        if($isArchive || $cache['type'] == 'custom') {
                            ResourceModel::flush($cache['id'],$keepers, $key,$isArchive);
                            \update_option($this->processing[$key] . '_' . $cache['id'], false);
                        }else {
                            ResourceModel::flush($cache['post_id'],$keepers,$key);
                            \update_post_meta($cache['post_id'],$this->processing[$key], false);
                        }
                    }
                }
            }
            if($cache['status'] == '4'){
                CacheService::getInstance()->snapshot($cache);
            }
        }
    }
}