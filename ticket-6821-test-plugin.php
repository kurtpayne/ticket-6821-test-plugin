<?php
/*
Plugin Name: Ticket 6821 Test Plugin
Plugin URI: https://github.com/kurtpayne/ticket-6821-test-plugin
Description: Help test ticket 6821
Version: 0.1
Author: Kurt Payne
Author URI: http://kpayne.me/
License: GPL2
*/

$ticket_6821_test_plugin = new Ticket_6821_Test_Plugin();
register_activation_hook( __FILE__ , array( 'Ticket_6821_Test_Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Ticket_6821_Test_Plugin', 'deactivate' ) );

class Ticket_6821_Test_Plugin {

	/**
	 * Constructor.  Fire off hooks
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Plugin hooks
	 * @return void
	 */
	public function hooks() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'upgrade' ) );
			add_action( 'admin_menu', array( $this, 'settings_page' ) );
			add_action( 'plugin_action_links', array( $this, 'add_settings_link'), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
		add_filter( 'wp_editors', array( $this, 'set_image_engines' ) );
	}

	/**
	 * Set the image engines / order
	 */
	public function set_image_engines() {
		
		// Get the engines
		$engines = get_option( 'ticket-6821-engines-order' );
		$available_engines = get_option( 'ticket-6821-engines' );
		
		// Remove unavailable engines
		$engines = array_intersect( $available_engines, $engines );
		
		// Done
		return $engines;
	}
	
	/**
	 * Enqueue jQuery scripts
	 */
	public function enqueue_scripts() {
		global $pagenow;
		if ( 'options-general.php' === $pagenow && 'ticket-6821-plugin' === $_REQUEST['page'] ) {
			wp_enqueue_script('jquery-ui-sortable');			
		}
	}
	
	/**
	 * Hook into the admin menu
	 * @return void
	 */
	public function settings_page() {
		add_options_page(
		    __( 'Ticket 6821 options', 'ticket-6821' ),
		    __( 'Ticket 6821',         'ticket-6821' ),
		    'manage_options', 'ticket-6821-plugin', array( $this, 'plugin_options' )
		);
	}

	/**
	 * Set options
	 * @return void
	 */
	public function plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Standard settings page
		if ( !extension_loaded( 'imagick' ) ) {
			echo '<div class="error"><p>' . sprintf( __( "The <a href=\"%s\" target=\"_blank\">imagick extension</a> was not found.", 'ticket-6821' ), 'http://pecl.php.net/package/imagick' ) . '</p></div>';
		}
		if ( !extension_loaded('gd') ) {
			echo '<div class="error"><p>' . sprintf( __( "The <a href=\"%s\" target=\"_blank\">gd extension</a> was not found.", 'ticket-6821' ), 'http://php.net/manual/en/book.image.php' ) . '</p></div>';
		}

		// Save settings
		if ( isset( $_REQUEST['__action'] ) && 'save' == $_REQUEST['__action'] ) {
			if ( !check_admin_referer( 'ticket-6821-save-settings' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );			
			}
			update_option( 'ticket-6821-engines-order', @explode(',', @$_POST['ticket_6821_engines_order'] ) ) ;
			update_option( 'ticket-6821-engines', @$_POST['ticket_6821_engines'] ) ;
		}

		?>
		<style>
			#sortable {
				list-style-type: none;
				margin: 0;
				padding: 0;
				width: 300px;
			}
			#sortable li {
				margin: 10px 0 0 5px;
			}
			div.drag-handle {
				float: left;
				padding: 10px 5px 0 5px;
			}
			div.drag-handle div {
				background: transparent url(images/sort.gif) no-repeat 0 0;
				width: 15px;
			}
		</style>
		<script>
			jQuery(document).ready(function() {
				jQuery( "#sortable" ).sortable( {
					update: function(event, ui) {
						jQuery("#ticket-6821-engines-order").val( jQuery(this).sortable( "toArray" ).join(",").replace( /ticket\-6821\-engine\-/g, "" ) );
					}
				});
				jQuery( "#sortable" ).disableSelection();
			});
		</script>
		<div class="wrap">
			<div id="icon-tools" class="icon32"><br/></div>
			<h2><?php _e( 'Ticket 6821 Tester Options', 'ticket-6821' ); ?></h2>

			<form id="ticket-6821-settings-form" name="ticket_6821_settings_form" method="post" action="<?php echo add_query_arg( '__action', 'save' ); ?>">
				<?php wp_nonce_field( 'ticket-6821-save-settings' ); ?>	
				<input type="hidden" id="ticket-6821-engines-order" name="ticket_6821_engines_order" value="<?php echo esc_attr( implode( ',', get_option( 'ticket-6821-engines-order' ) ) ); ?>" />

				<h3><?php _e( 'Engine options', 'ticket-6821' ); ?></h3>
				
				<p><?php _e( 'Drag the engines to enable loading order.  Use the checkboxes to enable or disable each engine from loading.', 'ticket-6821' ); ?></p>
				
				<ul id="sortable">
					<?php foreach ( (array) get_option( 'ticket-6821-engines-order') as $engine ) : ?>
						<li class="ui-state-default" id="ticket-6821-engine-<?php echo $engine; ?>">
							<div class='widget'>
								<div class="widget-top">
									<div class="drag-handle"><div>&nbsp;</div></div>
									<div class="widget-title"><label><h4><input type="checkbox" value="<?php echo $engine; ?>" <?php checked( in_array( $engine, (array) get_option( 'ticket-6821-engines') ) ); ?> name="ticket_6821_engines[]" /> <?php echo $engine; ?></h4></div></label>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>

				<br />
				<input type="submit" class="button-primary" value="<?php _e( 'Save', 'ticket-6821' ); ?>" name="ticket_6821_submit" id="ticket-6821-submit1" />
			</form>
		</div>
		<?php
	}

	/**
	 * Upgrade between different versions
	 * @return void
	 */
	public function upgrade() {

		// Get the current version
		$version = get_option( 'ticket-6821-version' );

		// Set default options
		if ( empty( $version ) || version_compare( $version, '0.1' ) < 0 ) {
			$engines = array();
			if ( extension_loaded('gd') ) {
				$engines[] = 'gd';
			}
			if ( extension_loaded('imagick') ) {
				$engines[] = 'imagick';
			}
			
			update_option( 'ticket-6821-engines', $engines );
			update_option( 'ticket-6821-engines-order', array( 'imagick', 'gd' ) );
			update_option( 'ticket-6821_version', '0.1' );
		}
	}
	
	/**
	 * Show the "Profile now" option on the plugins table
	 * @param array $links
	 * @param string $file
	 * @return array New links
	 */
	public static function add_settings_link( $links, $file ) {
		$settings_link = '<a href="options-general.php?page=ticket-6821-plugin">' . __( 'Settings', 'ticket-6821' ) . '</a>';
		if ( plugin_basename( $file ) === basename( __FILE__ ) )
			array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Uninstall (delete options)
	 */
	public static function uninstall() {
		delete_option( 'ticket-6821-engines-order' );
		delete_option( 'ticket-6821-engines' );
		delete_option( 'ticket-6821_version' );
	}
	
	/**
	 * Activate && check for a minimum WordPress version
	 */
	public static function activate() {
		global $wp_version;
		
		// Version check, only 3.5+
		if ( ! version_compare( $wp_version, '3.5-dev', '>=') ) {
			if ( function_exists('deactivate_plugins') ) {
				deactivate_plugins( __FILE__ );
			}
			die( '<strong>Ticket 6821 Tester</strong> requires WordPress 3.5 or later' );
		}
	}
}
