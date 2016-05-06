<?php
/**
 * Caches calls to wp_nav_menu().
 */
class Pj_Cached_Nav_Menus {
	public static $ttl = 3600; // use 0 to cache forever (until nav menu update)
	public static $cache_menus = array();

	public static function load() {
		add_filter( 'pre_wp_nav_menu', array( __CLASS__, 'pre_wp_nav_menu' ), 10, 2 );
		add_filter( 'wp_nav_menu', array( __CLASS__, 'maybe_cache_nav_menu' ), 10, 2 );
		add_action( 'wp_update_nav_menu', array( __CLASS__, 'clear_caches' ) );
	}

	private static function _cache_key( $args ) {
		$_args = (array) $args;
		unset( $_args['menu'] );
		return 'pj-cached-nav-menu:' . md5( json_encode( $_args ) );
	}

	private static function _timestamp() {
		static $timestamp;
		if ( ! isset( $timestamp ) )
			$timestamp = get_option( 'pj-cached-nav-menus-timestamp', 0 );

		return $timestamp;
	}

	public static function pre_wp_nav_menu( $output, $args ) {
		if ( ! empty( $args->menu ) )
			return $output;

		$cache_key = self::_cache_key( $args );
		self::$cache_menus[] = $cache_key;

		$cache = get_transient( $cache_key );
		if ( is_array( $cache ) && $cache['timestamp'] >= self::_timestamp() ) {
			$output = $cache['html'] . '<!-- pj-cached-nav-menu -->';
		}

		return $output;
	}

	public static function maybe_cache_nav_menu( $html, $args ) {
		$cache_key = self::_cache_key( $args );

		if ( ! in_array( $cache_key, self::$cache_menus ) )
			return $html;

		$cache = array(
			'html' => $html,
			'timestamp' => time(),
		);

		set_transient( $cache_key, $cache, self::$ttl );
		return $html;
	}

	public static function clear_caches() {
		update_option( 'pj-cached-nav-menus-timestamp', time() );
	}
}

Pj_Cached_Nav_Menus::load();
