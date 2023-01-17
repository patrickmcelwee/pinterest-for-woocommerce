<?php
/**
 * Pinterest for WooCommerce Feed File Operations.
 *
 * @package     Pinterest_For_WooCommerce/Classes/
 * @version     x.x.x
 */

namespace Automattic\WooCommerce\Pinterest;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class which handles all feed file filesystem operations.
 */
class FeedFileOperations {

	/**
	 * Local Feed Configurations class.
	 *
	 * @var LocalFeedConfigs of local feed configurations;
	 */
	private $configurations;

	/**
	 * @param LocalFeedConfigs $local_feeds_configurations local feed configuration.
	 * @since x.x.x
	 */
	public function __construct( LocalFeedConfigs $local_feeds_configurations ) {
		$this->configurations = $local_feeds_configurations;
	}

	/**
	 * Prepare a fresh temporary file for each local configuration.
	 * Files is populated with the XML headers.
	 *
	 * @since x.x.x
	 * @throws Exception Can't open or write to the file.
	 */
	public function prepare_temporary_files(): void {
		foreach ( $this->configurations->get_configurations() as $config ) {
			$bytes_written = file_put_contents(
				$config['tmp_file'],
				ProductsXmlFeed::get_xml_header()
			);

			$this->check_write_for_io_errors( $bytes_written, $config['tmp_file'] );
		}
	}

	/**
	 * Add XML footer to all temporary feed files.
	 *
	 * @since x.x.x
	 * @throws Exception Can't open or write to the file.
	 */
	public function add_footer_to_temporary_feed_files(): void {
		foreach ( $this->configurations->get_configurations() as $config ) {
			$bytes_written = file_put_contents(
				$config['tmp_file'],
				ProductsXmlFeed::get_xml_footer(),
				FILE_APPEND
			);

			$this->check_write_for_io_errors( $bytes_written, $config['tmp_file'] );
		}
	}

	/**
	 * Check if we have a feed file on the disk.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function check_if_feed_file_exists(): bool {
		$configs = $this->configurations->get_configurations();
		$config  = reset( $configs );
		if ( false === $config ) {
			return false;
		}
		return isset( $config['feed_file'] ) && file_exists( $config['feed_file'] );
	}

	/**
	 * Checks the status of the file write operation and throws if issues are found.
	 * Utility function for functions using file_put_contents.
	 *
	 * @since x.x.x
	 * @param integer $bytes_written How much data was written to the file.
	 * @param string  $file          File location.
	 *
	 * @throws Exception Can't open or write to the file.
	 */
	private function check_write_for_io_errors( $bytes_written, $file ): void {

		if ( false === $bytes_written ) {
			throw new Exception(
				sprintf(
					/* translators: error message with file path */
					__( 'Could not open temporary file %s for writing', 'pinterest-for-woocommerce' ),
					$file
				)
			);
		}

		if ( 0 === $bytes_written ) {
			throw new Exception(
				sprintf(
					/* translators: error message with file path */
					__( 'Temporary file: %s is not writeable.', 'pinterest-for-woocommerce' ),
					$file
				)
			);
		}
	}

	/**
	 * Rename temporary feed files to final name.
	 * This is the last step of the feed file generation process.
	 *
	 * @since x.x.x
	 * @throws \Exception Renaming not possible.
	 */
	public function rename_temporary_feed_files_to_final(): void {
		foreach ( $this->configurations->get_configurations() as $config ) {
			$status = rename( $config['tmp_file'], $config['feed_file'] );
			if ( false === $status ) {
				throw new Exception(
					sprintf(
						/* translators: 1: temporary file name 2: final file name */
						__( 'Could not rename %1$s to %2$s', 'pinterest-for-woocommerce' ),
						$config['tmp_file'],
						$config['feed_file']
					)
				);
			}
		}
	}

	/**
	 * Write pre-populated buffers to feed files.
	 *
	 * @param array $buffers an array of (locale => content) elements.
	 * @since x.x.x
	 * @throws Exception Can't open or write to the file.
	 */
	public function write_buffers_to_temp_files( array $buffers ): void {
		foreach ( $this->configurations->get_configurations() as $location => $config ) {
			if ( '' === $buffers[ $location ] ) {
				continue;
			}

			$bytes_written = file_put_contents(
				$config['tmp_file'],
				$buffers[ $location ],
				FILE_APPEND
			);

			$this->check_write_for_io_errors( $bytes_written, $config['tmp_file'] );
		}
	}
}
