<?php 
namespace CacheUltra\Services;

class Api {
    use \CacheUltra\Traits\Singleton;

    private $routes;

    private function __construct() {
        $this->routes = require_once __DIR__.'/../routes/api.php';
        add_action( 'rest_api_init', [$this, 'Init'] );
    }

    public function Init() {
        foreach($this->routes AS $route) {
            register_rest_route( 'cache-ultra/v1', $route['path'], 
                [
                    'methods' => $route['methods'],
                    'callback' => [$route['callback'][0]::getInstance(), $route['callback'][1]],
                    'permission_callback' => function () use ($route){
                        if($route['permission']){
                            if(is_array($route['permission']) && isset($route['permission']['method'])) {
                                $method = $route['permission']['method'];
                                return $route['callback'][0]::getInstance()->$method($_POST['token']);
                            }
                            return current_user_can( $route['permission'] );
                        }
                        return true;
                    },
                ]
            );
        }
    }


}