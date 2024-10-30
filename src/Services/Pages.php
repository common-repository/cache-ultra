<?php  
namespace CacheUltra\Services;

use CacheUltra\Traits\Singleton;

class Pages {

    use Singleton;

    public function __construct()  {
        add_action('admin_menu', [$this, 'AdminMenu'],20);
        add_action( 'admin_menu', [ $this, 'ChangeAdminMenuName' ], 200 );
    }

	public function ChangeAdminMenuName() {
        global $submenu;
        
		if ( isset( $submenu['cache-ultra'] ) ) {
			$submenu['cache-ultra'][0][0] = __( 'Page Caches', 'cache-ultra' );
		}
	}
    public function AdminMenu() {
        add_menu_page(
            'Cache Ultra',
            'Cache Ultra', 
            'manage_options', 
            'cache-ultra', 
            [$this, 'PageCaches'],
            'dashicons-yes-alt'
        );
        add_submenu_page( 
            'cache-ultra', 
            'Post Type Caches',
            'Post Type Caches', 
            'manage_options', 
            'post-type-caches',
            [$this, 'PostTypeCaches']
        );
        add_submenu_page( 
            'cache-ultra', 
            'Custom Caches',
            'Custom Caches', 
            'manage_options', 
            'custom-caches',
            [$this, 'CustomCaches']
        );
    }
    public function PageCaches() {
        echo '<div id="cache-ultra-page_caches">Loading...</div>';
    }
    public function PostTypeCaches() {
        echo '<div id="cache-ultra-post_type_caches">Loading...</div>';
    }
    public function CustomCaches() {
        echo '<div id="cache-ultra-custom_caches">Loading...</div>';
    }
}