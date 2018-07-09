<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class P2papi_REST_Connection_Types_Controller extends P2papi_REST_Base_Controller {

	protected static $connected_items_count = null;

    /**
     * Constructor.
     *
     * @since 0.0.1
     */
    public function __construct() {
    	parent::__construct();
        $this->rest_base = 'connection-types';
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @since 0.0.1
     *
     * @see register_rest_route()
     */
	public function register_routes() {

		$args = array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema(  WP_REST_Server::READABLE ),
			),
			'schema' => array( $this, 'get_item_schema' ),
		);

		$args_with_name = array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema(  WP_REST_Server::READABLE ),
			),
			'schema' => array( $this, 'get_item_schema' ),
		);

		$args_schema = array(
			'methods'         => WP_REST_Server::READABLE,
			'callback'        => array( $this, 'get_public_item_schema' ),
		);

		// !!! don't confuse the order of register_rest_route functions
		register_rest_route(
			$this->name_space, '/' . $this->rest_base . '/schema',
			$args_schema
		);
		register_rest_route(
			$this->name_space, '/' . $this->rest_base,
			$args
		);
		register_rest_route(
			$this->name_space, '/' . $this->rest_base . '/(?P<name>[a-zA-Z0-9-_]+)',
			$args_with_name
		);

	}


	/**
	 * Retrieves a single post.	??? rename post to item
	 *
	 * @since 4.7.0	??? rename version
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$params = $request->get_params();
		$item = $this->prepare_item_for_response( P2P_Connection_Type_Factory::get_instance( $params['name']), $request );
		return rest_ensure_response( $item );
	}





	/**
	 * Retrieves a collection of posts.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ){
		$data = array();

		$items = P2P_Connection_Type_Factory::get_all_instances();

		if ( empty( $items ) )
			return rest_ensure_response( $data );

		foreach ( $items as $item ) {
			$response = $this->prepare_item_for_response( $item, $request );
			$data[] = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Prepares a single post output for response.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ){
		$data = array();

		if ( null === $item )
			return rest_ensure_response( $data );

		$data = self::_prepare_item_for_response( $item );

		// ???
		// /**
		//  * Filters the post data for a response.
		//  *
		//  * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		//  *
		//  * @since 4.7.0
		//  *
		//  * @param WP_REST_Response $response The response object.
		//  * @param WP_Post          $post     Post object.
		//  * @param WP_REST_Request  $request  Request object.
		//  */
		// return apply_filters( "rest_prepare_{$this->post_type}", $response, $post, $request );


		return rest_ensure_response( $data );

	}


	public static function _prepare_item_for_response( $item ){
		$data = array();

		if ( ! is_object( $item) )
			return $data;

		$schema = self::_get_item_schema();

		if ( isset( $schema['properties']['name'] ) ) {
			$data['name'] = (string) $item->name;
		}

		if ( isset( $schema['properties']['side'] ) ) {
			$data['side'] = (array) $item->side;
		}

		// if ( isset( $schema['properties']['cardinality'] ) ) {
		// 	$data['cardinality'] = (array) $item->cardinality;
		// }

		if ( isset( $schema['properties']['labels'] ) ) {
			$data['labels'] = (array) $item->labels;
		}

		if ( isset( $schema['properties']['fields'] ) ) {
			$data['fields'] = (array) $item->fields;
		}

		if ( isset( $schema['properties']['connections_count'] ) ) {
			$connected_items_count = self::get_connected_items_count();
			if ( ! isset( $connected_items_count[ $item->name ] ) ) {
				$data['connections_count'] = (int) 0;
			} else {
				$data['connections_count'] = (int) $connected_items_count[ $item->name ];
			}
		}

		return $data;
	}


	protected static function get_connected_items_count() {
		if ( null === self::$connected_items_count ){
			global $wpdb;

			$connected_items_count = $wpdb->get_results( "
				SELECT p2p_type, COUNT(*) as count
				FROM $wpdb->p2p
				GROUP BY p2p_type
			" );

			self::$connected_items_count = scb_list_fold( $connected_items_count, 'p2p_type', 'count' );

		}
		return self::$connected_items_count;
	}



	// ??? permission checks
	// https://github.com/scribu/wp-posts-to-posts/wiki/Capabilities
	/**
	 * Checks if a given request has access to read an item.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	*/
	public function get_item_permissions_check( $request ){
		$params = $request->get_params();
		// $user = ???;
		return true;
	}
	public function get_items_permissions_check( $request ){
		return $this->get_item_permissions_check( $request );
	}


	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(){
		return self::_get_item_schema( $this );
	}

	public static function _get_item_schema( $_this = null ){
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'p2p_connection_type',
			'type'                 => 'object',
			'properties'           => array(
				'name' => array(
					'type'         => 'string',
					'readonly'     => true,
					),
				'side' => array(
					'type'         => 'array',
					'readonly'     => true,
				),
				// 'cardinality' => array(
				// 	'type'         => 'array',
				// 	'readonly'     => true,
				// ),
				'labels' => array(
					'type'         => 'array',
					'readonly'     => true,
				),
				'fields' => array(
					'type'         => 'array',
					// 'readonly'     => true,
				),
				// 'data' => array(
				// 	'type'         => 'array',
				// 	'readonly'     => true,
				// ),
				'connections_count' => array(
					'type'         => 'integer',
					'readonly'     => true,
				),
			),
		);

		if ( ! isset( $_this ) ) {
			foreach ( $schema['properties'] as $property ) {
				$property['validate_callback'] = array( $_this, 'validate_arg' );
				$property['sanitize_callback'] = array( $_this, 'sanitize_arg' );
			}
		}

		return $schema;
	}




}

// Hooking it into code
// https://jacobmartella.com/2017/12/22/simple-guide-adding-wp-rest-api-controller/
function p2papi_rest_connection_types_controller_init(){
	$controller = new P2papi_REST_Connection_Types_Controller();
	$controller->register_routes();
}
add_action('rest_api_init','p2papi_rest_connection_types_controller_init');



?>