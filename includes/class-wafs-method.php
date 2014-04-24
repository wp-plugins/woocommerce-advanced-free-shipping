<?php

if ( ! class_exists( 'Wafs_Free_Shipping_Method' ) ) {


	class Wafs_Free_Shipping_Method extends WC_Shipping_Method {
	
	
		/**
		 * Constructor for your shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {

			$this->id                	= 'advanced_free_shipping';
			$this->method_title  		= __( 'Advanced Free Shipping' );
			$this->method_description 	= __( 'Configure WooCommerce Advanced Free Shipping' ); // 

			$this->matched_methods	 	= $this->wafs_match_methods();
			
			$this->init();
		
		}


		/**
		 * Init your settings
		 *
		 * @access public
		 * @return void
		 */
		function init() {
		
			$this->init_form_fields();
			$this->init_settings();

			$this->enabled 			= $this->get_option( 'enabled' );
			$this->hide_shipping 	= $this->get_option( 'hide_other_shipping_when_available' );
						
			// Save settings in admin if you have any defined
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			
			// Hide shipping methods
			add_filter( 'woocommerce_available_shipping_methods', array( $this, 'hide_all_shipping_when_free_is_available' ) );
			
		}
		
	
		/**
		 * Match methods
		 *
		 * Checks if methods matches conditions
		 *
		 * @access public
		 * @return void
		 */
		public function wafs_match_methods() {

			$methods = get_posts( array( 'posts_per_page' => '-1', 'post_type' => 'wafs' ) );
			
			foreach ( $methods as $method ) :

				$condition_groups = get_post_meta( $method->ID, '_wafs_shipping_method_conditions', true );

				// Check if method conditions match
				$match = $this->wafs_match_conditions( $condition_groups );
				
				// Add (single) match to parameter
				if ( true == $match ) :
					$matched_methods = $method->ID;
				endif;
				
			endforeach;
			
			return $matched_methods;
			
		}
		
		
		/**
		 * Match conditions
		 *
		 * @access public
		 * @return void
		 */
		public function wafs_match_conditions( $condition_groups = array() ) {

			if ( empty( $condition_groups ) ) return false;

			foreach ( $condition_groups as $condition_group => $conditions ) :

				$match_condition_group = true;
				
				foreach ( $conditions as $condition ) :

					$match = apply_filters( 'wafs_match_condition_' . $condition['condition'], false, $condition['operator'], $condition['value'] );

					if ( false == $match ) :
						$match_condition_group = false;
					endif;
					
				endforeach;

				// return true if one condition group matches
				if ( true == $match_condition_group ) :
					return true;
				endif;
				
			endforeach;
			
			return false;
			
		}
		
		
		/**
		 * Init form fields
		 *
		 * @access public
		 * @return void
		 */
		public function init_form_fields() {
		
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable Advanced Free Shipping', 'wafs' ),
					'default' 		=> 'yes'
				),
				'hide_other_shipping_when_available' => array(
					'title' 		=> __( 'Hide other shipping', 'wafs' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Hide other shipping methods when free shipping is available', 'wafs' ),
					'default' 		=> 'no'
				),
				'conditions' => array(
					'type' 			=> 'conditions_table',
				)
			);
			
			
		}
		
		/* Settings tab table.
		 *
		 * Load and render the table on the Advanced Free Shipping settings tab.
		 *
		 * @return string
		 */
		public function generate_conditions_table_html() {
			
			ob_start();
			
				/**
				 * Load conditions table file
				 */
				require_once plugin_dir_path( __FILE__ ) . 'admin/views/conditions-table.php';
			
			return ob_get_clean();
			
		}
		
		
		/**
		 * validate_additional_conditions_table_field function.
		 *
		 * @access public
		 * @param mixed $key
		 * @return bool
		 */
		public function validate_additional_conditions_table_field( $key ) {
			return false;
		}



		/**
		 * calculate_shipping function.
		 *
		 * @access public
		 * @param mixed $package
		 * @return void
		 */
		public function calculate_shipping( $package ) {

			if ( false == $this->matched_methods || 'no' == $this->enabled ) return;
			
			$match_details 	= get_post_meta( $this->matched_methods, '_wafs_shipping_method', true );
			$label 			= $match_details['shipping_title'];
			$calc_tax 		= $match_details['calc_tax'];
			
			$rate = array(
				'id'       => $this->id,
				'label'    => ( null == $label ) ? __( 'Free Shipping', 'wafs' ) : $label,
				'cost'     => '0',
				'calc_tax' => ( null == $calc_tax ) ? 'per_order' : $calc_tax
			);
			
			// Register the rate
			$this->add_rate( $rate );

			
		}
		
		
		/**
		 * Hide shipping.
		 *
		 * Hide Shipping methods when regular or advanced free shipping is available
		 *
		 * @param array $available_methods
		 * @return array
		 */
		public function hide_all_shipping_when_free_is_available( $available_methods ) {

			if ( 'no' == $this->hide_shipping ) return $available_methods;
			
		 	if ( isset( $available_methods['advanced_free_shipping'] ) ) :
		 	
				return array( $available_methods['advanced_free_shipping'] );
		 	
		 	elseif ( isset( $available_methods['free_shipping'] ) ) :

		 		return array( $available_methods['free_shipping'] );
		 		
		 	else :
		 	
		 		return $available_methods;
		 		
		 	endif;
		 	
		  	
		}
		
		
	}
	$wafs_free_shipping_method = new Wafs_Free_Shipping_Method();
	
}



?>