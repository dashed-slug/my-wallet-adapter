<?php
/*
 * Plugin Name: My Wallet Adapter
 * Description: Connects Bitcoin and Altcoin Wallets 6.0.0 or later with some type of wallet. This is sample code for wallet adapter developers. See the plugin's documentation for details.
 * Version: 1.0
 * Plugin URI: http://example.com
 * Author: author@example.com
 * Author URI: http://example.com/author
 * Text Domain: my-wallet-adapter
 * Domain Path: /languages/
 * License: GPLv2 or later
 *
 * @license GNU General Public License, version 2
 */

// don't load directly
defined( 'ABSPATH' ) || die( '-1' );

add_action(
	'wallets_declare_wallet_adapters',
	function() {
		require_once __DIR__ . '/adapters/my-wallet-adapter-impl.php';
	}
);
