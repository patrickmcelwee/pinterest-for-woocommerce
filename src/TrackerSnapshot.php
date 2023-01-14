<?php
/**
 * Include Pinterest data in the WC Tracker snapshot.
 *
 * @package Automattic\WooCommerce\Pinterest
 */

namespace Automattic\WooCommerce\Pinterest;

use Pinterest_For_Woocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class handling Woo Tracker
 */
class TrackerSnapshot {

	/**
	 * Transient key name; the time when was the feed generation started.
	 *
	 * @var string
	 */
	public const TRANSIENT_WCTRACKER_FEED_GENERATION_WALL_START_TIME = PINTEREST_FOR_WOOCOMMERCE_PREFIX . '_wctracker_feed_generation_wall_start_time';

	/**
	 * Transient key name; the time it took to generate feed.
	 *
	 * @var string
	 */
	public const TRANSIENT_WCTRACKER_FEED_GENERATION_WALL_TIME = PINTEREST_FOR_WOOCOMMERCE_PREFIX . '_wctracker_feed_generation_wall_time';

	public const TRANSIENT_WCTRACKER_LIFE_TIME = 2 * WEEK_IN_SECONDS;

	/**
	 * Not needed if allow_tracking is disabled.
	 *
	 * @return bool Whether the object is needed.
	 */
	public static function is_needed(): bool {
		return 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	}

	/**
	 * Hook extension tracker data into the WC tracker data.
	 */
	public static function maybe_init(): void {

		if ( ! self::is_needed() ) {
			return;
		}

		add_filter(
			'woocommerce_tracker_data',
			function ( $data ) {
				return self::include_snapshot_data( $data );
			}
		);
	}

	/**
	 * Add extension data to the WC Tracker snapshot.
	 *
	 * @param array $data The existing array of tracker data.
	 *
	 * @return array The updated array of tracker data.
	 */
	protected static function include_snapshot_data( array $data = array() ): array {
		if ( ! isset( $data['extensions'] ) ) {
			$data['extensions'] = array();
		}

		$feed_generation_time = ProductFeedStatus::get()[ ProductFeedStatus::PROP_FEED_GENERATION_WALL_TIME ];

		$data['extensions'][ PINTEREST_FOR_WOOCOMMERCE_TRACKER_PREFIX ] = array(
			'settings' => self::parse_settings(),
			'store'    => array(
				'connected'        => wc_bool_to_string( Pinterest_For_Woocommerce::is_connected() ),
				'actively_syncing' => wc_bool_to_string( ProductSync::is_product_sync_enabled() ),
			),
			'feed'     => array(
				'generation_time' => $feed_generation_time,
				'products_count'  => (int) ProductFeedStatus::get()['product_count'] ?? 0,
			),
		);

		return $data;
	}

	/**
	 * Parse general extension and settings data in the required format.
	 *
	 * @return array
	 */
	protected static function parse_settings(): array {

		$settings = (array) Pinterest_For_Woocommerce()::get_settings( true );

		$tracked_settings = array(
			'track_conversions',
			'enhanced_match_support',
			'save_to_pinterest',
			'rich_pins_on_posts',
			'rich_pins_on_products',
			'product_sync_enabled',
			'enable_debug_logging',
			'erase_plugin_data',
		);

		$settings = array_intersect_key( $settings, array_flip( $tracked_settings ) );
		return array_map( 'wc_bool_to_string', $settings ) + array( 'version' => PINTEREST_FOR_WOOCOMMERCE_VERSION );
	}


}
