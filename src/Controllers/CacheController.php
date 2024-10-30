<?php 
namespace CacheUltra\Controllers;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Resource;
use CacheUltra\Models\Cache;
use CacheUltra\Services\Security;

class CacheController {

    use Singleton;

    private $settings = '_cu_cache_settings_';
    private $defaultSettings = [
        'lazyload' => false,
    ];
    private function __construct() {
    }
    public function ValidateToken($token) {
        return Security::getInstance()->validate($token);
    }
    public function cancelPerformance($request) {
        $cache = Cache::find($request->get_param('id'));
        Cache::save($cache);
        if($cache['type'] == 'custom' || $cache['post_id'] == '0'){
            $processing = \update_option('cu_processing_performance_'.$cache['id'],false);
        }else{
            $processing = \update_post_meta($cache['post_id'], 'cu_processing_performance',false);
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function cancelSnapshot($request) {
        $cache = Cache::find($request->get_param('id'));
        if($cache){
            foreach($cache['files'] AS $key => $file){
                if(file_exists(CACHE_ULTRA_CACHES_DIR.$file)){
                    unlink(CACHE_ULTRA_CACHES_DIR.$file);
                } 
                unset($cache[$key]);
            }
            $cache['status'] = 1;
            $cache['timestamp'] = false;
            $cache['notes']['scores'] = false;
            Cache::save($cache);
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function savePerformance($request) {
        $cache = Cache::find($_POST['cache']['id']);
        if(isset($_POST['scores'])) $cache['notes']['scores'] = $_POST['scores'];
        Cache::save($cache);
        if($cache['type'] == 'custom' || $cache['post_id'] == '0'){
            $processing = \update_option('cu_processing_performance_'.$cache['id'],false);
        }else{
            $processing = \update_post_meta($cache['post_id'], 'cu_processing_performance',false);
        }
    }
    public function checkPerformance($request) {
        $cache = Cache::find($request->get_param('id'));
        $processing = false;
        if($cache['type'] == 'custom' || $cache['post_id'] == '0'){
            $processing = \get_option('cu_processing_performance_'.$cache['id'],false);
        }else{
            $cache['post_title'] = \get_the_title($cache['post_id']);
            $processing = \get_post_meta($cache['post_id'], 'cu_processing_performance',true);
        }
        return new \WP_REST_Response( [
            'processing' => $processing,
            'loading' => $processing,
            'success' => 1,
            'cache' => $cache,
        ], 200 );
    }
    public function pages($request) {
        $caches = Cache::getPages();
        foreach($caches AS &$cache) {
            $cache['post_title'] = \get_the_title($cache['post_id']);
            $cache['processing_performance'] = \get_post_meta($cache['post_id'],'cu_processing_performance',true);
        }
        return new \WP_REST_Response( [
            'success' => 1,
            'caches' => $caches,
        ], 200 );
    }
    public function load($request){
        $files = [
            'javascript' => [
                'head' => 0,
                'foot' => 1,
            ],
            'css' => [
                'head' => 2,
                'foot' => 3,
            ],
            'html' => 4,
        ];
        $cache = Cache::find($request['id']);
        $html_file = CACHE_ULTRA_CACHES_DIR.$cache['files'][$files['html']];
        $file = CACHE_ULTRA_CACHES_DIR.$cache['files'][$files[$request['type']][$request['location']]];
        
        $supportsGzip = strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false;
        $data = base64_decode(file_get_contents($file));
        if ( $supportsGzip ) {
            $content = gzencode( $data, 9);
            header('Content-Encoding: gzip');
        } else {
            $content = $data;
        }
        $file_last_mod_time = filemtime($file);
        $content_last_mod_time = filemtime($html_file);
        $etag = '"' . $file_last_mod_time . '.' . $content_last_mod_time . '"';
        header('ETag: ' . $etag);
        $offset = 60 * 60;
        $expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";
        header('Content-type: text/'.$request['type'].'; charset: UTF-8');
        header('Cache-Control: public, max-age=86400');
        header( $expire );
        header('Pragma: cache');
        header( 'Content-Length: ' . strlen( $content ) );
        header('Vary: Accept-Encoding');
        echo $content;
        exit();
    }
    public function remove($request) {
        $success = Cache::delete($request['cache_id']);
        $success = Resource::delete($request['cache_id']);
        return new \WP_REST_Response( [
            'success' => $success,
            'message' => 'Cache deleted Successfully',
        ], 200 );
    }
    public function resources($request) {
        $cache = Cache::find($request['cache_id']);
        $types = $request['types'];
        if($cache){
            $resources = Resource::AllByTypeAndCacheId($cache['id'],$types);
            $settings = \get_option($this->settings.$cache['id'],$this->defaultSettings);
            return new \WP_REST_Response( [
                'success' => 1,
                'settings' => $settings,
                'resources' => $resources,
                'message' => 'Cache resources fetched Successfully',
            ], 200 );
        }
        return new \WP_REST_Response( [
            'success' => 0,
            'message' => 'Whoops Something went wrong!',
        ], 200 );

    }
    public function update($request) {
        $resources = $request['resources'];
        $settings = $request['settings'];
        $cache = $request['cache'];
        $oldCache = Cache::find($cache['id']);
        if($oldCache) {
            $id = Cache::save($cache);
            if($settings){
                $currentSettings = \get_option($this->settings.$cache['id'],$this->defaultSettings);
                foreach($settings AS $key => $value){
                    $currentSettings[$key] = $value;
                    \update_option($this->settings.$cache['id'],$currentSettings);
                }
            }
            if($resources){
                foreach($resources AS $type => $items) {
                    foreach($items AS $order => $resource) {
                        $oldResource = Resource::find($resource['id']);
                        if($oldResource){
                            $oldResource['exclude'] = $resource['exclude'];
                            $oldResource['remove'] = $resource['remove'];
                            $oldResource['location'] = $resource['location'];
                            $oldResource['order'] = $order;
                            $oldResource['cache_id'] = $cache['id'];
                            Resource::save($oldResource);
                        }
                    }
                }
            }
            return new \WP_REST_Response( [
                'success' => 1,
                'id' => $cache['id'],
                'message' => 'Cache Updated Successfully',
            ], 200 );
        }

        return new \WP_REST_Response( [
            'success' => 0,
            'message' => 'Whoops Something went wrong!',
        ], 200 );

    }
    public function create($request) {
        $cache = $request['cache'];
        $cache['id'] = 0;
        $id = Cache::save($cache);
        if($id) {
            return new \WP_REST_Response( [
                'success' => 1,
                'newid' => $id,
                'message' => 'Cache Created Successfully',
            ], 200 );
        }
        return new \WP_REST_Response( [
            'success' => 0,
            'message' => 'Whoops Something went wrong!',
        ], 200 );
    }
}