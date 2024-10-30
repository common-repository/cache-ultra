<?php 
namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;

class Metabox {

    use Singleton;

    public function __construct() {
        add_action('add_meta_boxes', [$this,'Create']);
    }

    public function Create() {
        $screens = ['post', 'page'];
        foreach ($screens as $screen) {
            add_meta_box(
                'limelight-cache-box-'.$screen,
                'Snapshot',
                [$this, 'Render'],
                $screen,
                'side'
            );
        }
    }

    public function Render() {
        include_once CACHE_ULTRA_DIR . '/templates/admin/snapshot.phtml';
    }

}