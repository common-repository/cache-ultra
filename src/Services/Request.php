<?php
namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;
use CacheUltra\Models\Cache;

class Request {

    use Singleton;

    public function __construct() {

    }
    public function request($data,$api,$type){
        $tmp_file = CACHE_ULTRA_TMP_DIR.'/'.md5($data['cache']['id'] . \get_site_url() . $data['time'] . $type);
        file_put_contents($tmp_file,base64_encode(serialize($data)));
        $client = new \GuzzleHttp\Client();
        $data['cache']['notes']['tmp_file'][$data['time']] = $tmp_file;
        Cache::save($data['cache']);
        $form_params = [
            'time' => $data['time'],
            'token' => $data['token'],
        ];
        $headers = [
            'Referrer' => \get_site_url(),
        ];
        $response = $client->post($api,[
            'headers'=>$headers,
            'form_params'=> $form_params
        ]);
    }

}