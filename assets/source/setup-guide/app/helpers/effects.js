/**
 * External dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useCallback } from '@wordpress/element';

import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { SETTINGS_STORE_NAME } from '../data';

export const useSettingsSelect = ( selector = 'getSettings' ) => {
	return useSelect( ( select ) =>
		select( SETTINGS_STORE_NAME )[ selector ]()
	);
};

export const useSettingsDispatch = ( saveToDb = false ) => {
	const { updateSettings } = useDispatch( SETTINGS_STORE_NAME );

	return ( data ) => updateSettings( data, saveToDb );
};

export const useCreateNotice = () => {
	const { createNotice } = useDispatch( 'core/notices' );

	return useCallback(
		( type, message ) => message && createNotice( type, message ),
		[ createNotice ]
	);
};

export const useBodyClasses = ( style ) => {
	useEffect( () => {
		document.body.classList.add( 'woocommerce-setup-guide__body' );

		if ( style === 'wizard' ) {
			document.body.parentNode.classList.remove( 'wp-toolbar' );
			document.body.classList.remove( 'woocommerce-admin-is-loading' );
			document.body.classList.add( 'woocommerce-onboarding' );
			document.body.classList.add( 'woocommerce-admin-full-screen' );
		}

		return () => {
			document.body.classList.remove( 'woocommerce-setup-guide__body' );

			if ( style === 'wizard' ) {
				document.body.classList.remove( 'woocommerce-onboarding' );
				document.body.classList.remove(
					'woocommerce-admin-full-screen'
				);
				document.body.parentNode.classList.add( 'wp-toolbar' );
			}
		};
	}, [ style ] );
};

export const useConnectAdvertiser = () => {
	const createNotice = useCreateNotice();

	return useCallback(
		async ( trackingAdvertiser, trackingTag ) => {
			try {
				const results = await apiFetch( {
					path: `${ wcSettings.pinterest_for_woocommerce.apiRoute }/tagowner/`,
					data: {
						advrtsr_id: trackingAdvertiser,
						tag_id: trackingTag,
					},
					method: 'POST',
				} );

				if ( trackingAdvertiser === results.connected ) {
					if ( results.reconnected ) {
						createNotice(
							'success',
							__(
								'Advertiser connected successfully.',
								'pinterest-for-woocommerce'
							)
						);
					}
				} else {
					createNotice(
						'error',
						__(
							'Couldn’t connect advertiser.',
							'pinterest-for-woocommerce'
						)
					);
				}
			} catch ( error ) {
				createNotice(
					'error',
					error.message ||
						__(
							'Couldn’t connect advertiser.',
							'pinterest-for-woocommerce'
						)
				);
			}
		},
		[ createNotice ]
	);
};
