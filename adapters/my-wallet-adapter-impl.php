<?php

use DSWallets\Adapters\Wallet_Adapter;
use DSWallets\PostTypes\Address;
use DSWallets\PostTypes\Currency;
use DSWallets\PostTypes\Transaction;
use DSWallets\PostTypes\Wallet;

defined( 'ABSPATH' ) || die( -1 ); // don't load directly

if (
	class_exists( '\DSWallets\Adapters\Wallet_Adapter' ) &&
	! class_exists( 'My_Wallet_Adapter' )
) {

	class My_Wallet_Adapter extends Wallet_Adapter {
		public function __construct( Wallet $wallet ) {
		
			$this->settings_schema = [
					[
						'id'            => 'ip',
						'name'          => __( 'IP address for the wallet', 'my-wallet-adapter' ),
						'type'          => 'string',
						'description'   => __( 'The IP of the machine running your wallet daemon. Set to 127.0.0.1 if you are running the daemon on the same machine as WordPress. If you want to enter an IPv6 address, enclose it in square brackets e.g.: [0:0:0:0:0:0:0:1].', 'my-wallet-adapter' ),
						'default'       => '127.0.0.1',
						// note how you can optionally add a validator callback for your settings.
						// Any PHP callable will do, as long as it takes one argument and returns a boolean.
						'validation_cb' => [ __CLASS__, 'validate_tcp_ip_address' ], 
					],
					[
						'id'            => 'port',
						'name'          => __( 'TCP port for the wallet', 'my-wallet-adapter' ),
						'type'          => 'number',
						'description'   => __( 'The TCP port where the wallet listens for connections.', 'my-wallet-adapter' ),
						'min'           => 0,
						'max'           => 65535,
						'step'          => 1,
						'default'       => 1234,
					],
					[
						'id'            => 'username',
						'name'          => __( 'Username', 'my-wallet-adapter' ),
						'type'          => 'string',
						'description'   => __( 'A username to use when connecting to the wallet\'s API.', 'my-wallet-adapter' ),
						'default'       => '',
					],
					[
						'id'            => 'password',
						'name'          => __( 'Password', 'my-wallet-adapter' ),
						'type'          => 'secret',
						'description'   => __( 'A password to connect to the wallet\'s API.', 'my-wallet-adapter' ),
						'default'       => '',
					],
					[
						'id'            => 'some_choice',
						'name'          => __( 'Some choice', 'my-wallet-adapter' ),
						'type'          => 'select',
						'description'   => __(
							'You can also create settings that are dropdown choices',
							'my-wallet-adapter'
						),
						'default'       => 'second',
						'options'       => [
							'first'  => __( 'First choice', 'my-wallet-adapter' ),
							'second' => __( 'Second choice', 'my-wallet-adapter' ),
						],
					],
			]; // end settings_schema value

		} // end constructor

		public function do_withdrawals( array $withdrawals ): void {
			// Always call the parent implementation first.
			// This checks if all transactions are pending withdrawals and of the same currency.
			// If this was called by 
			parent::do_withdrawals( $withdrawals );

			if ( ! $withdrawals ) {
				return;

			} else {
				foreach ( $withdrawals as $withdrawal ) {
					// Send $withdrawal here.
					// We assume that you've implemented a private method "send_amount_to_address()" that does the work.
					// The amount to send is in integer form in $withdrawal->amount.
					// The address to send to is in the string: $withdrawal->address->address.
					// Any extra info (such as Payment ID) is in the string: $withdrawal->address->extra. This may be empty.

					$result = $this->send_amount_to_address( $withdrawal->amount, $withdrawal->address->address );

					if ( $result->success ) {
						$withdrawal->status = 'done';
						$withdrawal->txid = $result->txid;
					} else {
						$withdrawal->status = 'failed';
						$withdrawal->error = __( 'The transaction could not be executed. Bummer!', 'my-wallet-adapter' );
					}
				} // end foreach

				// Here all withdrawals have been attempted.
				// Not strictly necessary, but we can throw an Exception
				// for the first error encountered, if any:
				foreach ( $withdrawals as $withdrawal ) {
					if ( 'failed' == $withdrawal->status && $withdrawal->error ) {
						throw new \Exception( $withdrawal->error );
					}
				} // end foreach
			} // end else
		} // end method do_withdrawals()

		public function get_new_address( Currency $currency = null ): Address {
			// Let's assume that you've implemented a private method "get_new_address()",
			// that returns a new address string from the wallet.
			$result = $this->get_new_address();

			$address = new Address;
			$address->type = 'deposit';
			$address->currency = $currency;
			$address->address = $result;

			return $address;
		} // end method get_new_address()

		public function get_hot_balance( Currency $currency = null ): int {
			// Let's assume that you've implemented get_balance() which returns the wallet's total balance as an integer.
			
			return absint( $this->get_balance() );
		}

		public function get_hot_locked_balance( Currency $currency = null ): int {
			// Let's assume that you've implemented a private method get_unlocked_balance(),
			// which returns the wallet's unlocked balance as an integer.
			
			return absint( $this->get_balance() - $this->get_unlocked_balance() );
		}

		public function do_description_text(): void {
			?>
			<h3><?php esc_html_e( 'IP address and port', 'my-wallet-adapter' ); ?></h3>
			<p><?php esc_html_e( 'You have indicated that you wish to connect to the wallet at the following IP address and TCP port:', 'my-wallet-adapter' ); ?></p>
			<p>
				<?php 
					printf(
						'%s:%d',
						$this->ip, // Note how the IP address setting you provided at the constructor is available here automagically!
						$this->port // Same for the port number! Typically, you would use these values to establish a connection, not display them to the admin.
					);
				?>
			</p>
			<?php
		} // end function do_description_text()

		public function get_extra_field_name( $currency = null ): ?string {
			if ( 'XMR' == $currency->symbol ) {
				return __( 'Payment ID', 'my-wallet-adapter');
			}
			return null;
		}

		public function get_wallet_version(): string {
			// Here we attempt a connection to the wallet
			// You can return the wallet's version as a string, if you have it.
			// Or you can throw an exception if connection is not possible.
		
			throw new \Exception(
				__( 'This wallet adapter has not yet been implemented!', 'my-wallet-adapter' )
			);
		}

		public function get_block_height( Currency $currency = null ): int {
			// You can return the current block height for your wallet, or 0 if not available

			return 0;
		}

		public function is_locked(): bool {
			// You would normally return true here if connection is established with the wallet.
			// We return false for now.

			return false;

			// Once you implement do_withdrawals() correctly, you can change this to return true.
			// Remember, withdrawals are not attempted while this returns false.
			// For example, you could return true only if the wallet is unlocked using a passphrase, or if the wallet looks synced, etc.
		}

		public function do_cron(): void {
			// TODO this gets called periodically. Optionally do housekeeping tasks with your wallet here.
		}

	} // end class definition
} // end guard clause for class definition
