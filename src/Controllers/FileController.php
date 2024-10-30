<?php 
namespace CacheUltra\Controllers;

use CacheUltra\Traits\Singleton;
use CacheUltra\Services\Security;
use CacheUltra\Models\Cache AS CacheModel;

class FileController {

    use Singleton;
    private function __construct() {
    }
    public function ValidateToken($token) {
        return Security::getInstance()->validate($token);
    }
    public function unlinkFile($request) {
        $data = $_POST;
        $cache = CacheModel::find($data['cache_id']);
        if(isset($cache['notes']['tmp_file'][$data['time']])){
            unlink($cache['notes']['tmp_file'][$data['time']]);
            unset($cache['notes']['tmp_file'][$data['time']]);
        } 
        CacheModel::save($cache);
        return new \WP_REST_Response( [
            'success' => 1,
            'message' => 'File has been removed',
        ], 200 );
    }
}