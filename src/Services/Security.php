<?php 
namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;
use ReallySimpleJWT\Token;


class Security {

    use Singleton;

    protected $secret = '8982Ajfj!@!#^&$';

    public function __construct() {
    }
    public function validate($token) {
        $result = Token::validate($token, $this->secret);
        return $result;
    }
    public function createToken(){
        $time = time();
        $payload = [
            'iat' => $time,
            'uid' => \get_current_user_id(),
            'exp' => $time + 360,
            'iss' => \site_url(),
        ];
        $token = Token::customPayload($payload, $this->secret);
        return $token;
    }


}