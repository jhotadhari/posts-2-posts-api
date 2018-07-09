<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class P2papi_REST_Connections_Controller extends P2papi_REST_Base_Controller {

    /**
     * Constructor.
     *
     * @since 0.0.1
     */
    public function __construct() {
    	parent::__construct();
        $this->rest_base = 'connections';
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
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema(  WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_item_schema' ),
		);

		$args_with_id = array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema(  WP_REST_Server::READABLE ),
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'            => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'     => array(
					'force'    => array(
						'default'      => false,
					),
				),
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
			$this->name_space, '/' . $this->rest_base . '/(?P<p2p_id>[\d]+)',
			$args_with_id
		);
		register_rest_route(
			$this->name_space, '/' . $this->rest_base . '/(?P<p2p_type>[a-zA-Z0-9-_]+)',
			$args
		);
		register_rest_route(
			$this->name_space, '/' . $this->rest_base . '/(?P<p2p_type>[a-zA-Z0-9-_]+)' . '/(?P<p2p_id>[\d]+)',
			$args_with_id
		);

		//get connected items (of an item and by connection_type)
		//... can be done with a REST posts request and some Query args

		//connection exists ???

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
		$item = $this->_get_prepared_item( $params['p2p_id'], $request );
		return rest_ensure_response( $item );
	}

	protected function _get_prepared_item( $p2p_id, $request = null ){
		return $this->prepare_item_for_response( p2p_get_connection( $p2p_id ), $request );
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
		$params = $request->get_params();
		$items = $this->_get_items( $params );

		if ( empty( $items ) )
			return rest_ensure_response( $data );

		foreach ( $items as $item ) {
			$response = $this->prepare_item_for_response( $item, $request );
			$data[] = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	protected function _get_items( $params ){
		$items = array();
		$args = array();
		if ( array_key_exists( 'p2p_direction', $params ) )
			$args['direction'] = sanitize_text_field( $params['p2p_direction'] );
		if ( array_key_exists( 'p2p_from', $params ) )
			$args['from'] = $params['p2p_from'];
		if ( array_key_exists( 'p2p_to', $params ) )
			$args['to'] = $params['p2p_to'];

		if ( array_key_exists( 'p2p_type', $params ) ) {
			$items = p2p_get_connections( $params['p2p_type'], $args );
		} else {
			$connection_types = P2P_Connection_Type_Factory::get_all_instances();
			foreach ( $connection_types as $connection_type ) {
				$items = array_merge( $items, p2p_get_connections( $connection_type->name, $args ));
			}
		}
		return $items;
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

		$schema = $this->get_item_schema();

		// p2p properties
		if ( isset( $schema['properties']['p2p_id'] ) )
			$data['p2p_id'] = (int) $item->p2p_id;
		if ( isset( $schema['properties']['p2p_from'] ) )
			$data['p2p_from'] = (int) $item->p2p_from;
		if ( isset( $schema['properties']['p2p_to'] ) )
			$data['p2p_to'] = (int) $item->p2p_to;
		if ( isset( $schema['properties']['p2p_type'] ) )
			$data['p2p_type'] = sanitize_text_field( $item->p2p_type );

		// custom properties
		if ( isset( $schema['properties']['p2p_type_obj'] ) )
			$data['p2p_type_obj'] = P2papi_REST_Connection_Types_Controller::_prepare_item_for_response( P2P_Connection_Type_Factory::get_instance( $item->p2p_type ) );
		if ( isset( $schema['properties']['meta'] ) ) {
			if ( array_key_exists( 'p2p_id', $data ) && array_key_exists( 'p2p_type_obj', $data ) ) {
				foreach ( $data['p2p_type_obj']['fields'] as $field_key => $field ) {
					$data['meta'][$field_key] = p2p_get_meta( $data['p2p_id'], $field_key, true );
				}
			}
		}

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


	/**
	 * Creates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$params = $request->get_params();

		if ( ! array_key_exists('p2p_from', $params ) || empty( $params['p2p_from'] ) )
			return new WP_Error( 'cant-create', __( 'can\'t create', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_from missing', 'posts-to-posts-backbone'), array( 'status' => 500 ) );
		if ( ! array_key_exists('p2p_to', $params ) || empty( $params['p2p_to'] ) )
			return new WP_Error( 'cant-create', __( 'can\'t create', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_to missing', 'posts-to-posts-backbone'), array( 'status' => 500 ) );
		if ( ! array_key_exists('p2p_type', $params ) || empty( $params['p2p_type'] ) )
			return new WP_Error( 'cant-create', __( 'can\'t create', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_type missing', 'posts-to-posts-backbone'), array( 'status' => 500 ) );

		$connection_type = p2p_type( $params['p2p_type'] );

		if ( ! $connection_type instanceof P2P_Connection_Type )
			return new WP_Error( 'cant-create', __( 'can\'t create', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_type not registered', 'posts-to-posts-backbone'), array( 'status' => 500 ) );

		// create connection
		$p2p_id = $connection_type->connect(
			$params['p2p_from'],
			$params['p2p_to'],
			array(
				'date' => current_time('mysql')
			)
		);

		// on fail
		if ( is_wp_error( $p2p_id ) )
			return new WP_Error( 'cant-create', $p2p_id->get_error_message(), array( 'status' => 500 ) );

		// update meta, fallback to default value
		$meta = array_key_exists('meta', $params ) && is_array( $params['meta'] ) ? $params['meta'] : array();
		$this->handle_item_meta( $this->_get_prepared_item( $p2p_id )->data, $meta, true );


		// ???
		// /**
		//  * Fires after a single post is created or updated via the REST API.
		//  *
		//  * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		//  *
		//  * @since 4.7.0
		//  *
		//  * @param WP_Post         $post     Inserted or updated post object.
		//  * @param WP_REST_Request $request  Request object.
		//  * @param bool            $creating True when creating a post, false when updating.
		//  */
		// do_action( "rest_insert_{$this->post_type}", $post, $request, true );

		return new WP_REST_Response( 'connection created. p2p_id: ' .  $p2p_id, 200 );

	}


	/**
	 * Updates a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$params = $request->get_params();

		if ( ! array_key_exists('p2p_id', $params ) || empty( $params['p2p_id'] ) )
			new WP_Error( 'cant-update', __( 'can\'t update', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_id missing', 'posts-to-posts-backbone'), array( 'status' => 500 ) );

		// update meta
		if ( array_key_exists('meta', $params ) && is_array( $params['meta'] ) )
			$this->handle_item_meta( $this->_get_prepared_item( $params['p2p_id'] )->data, $params['meta'] );

		// ???
		// /**
		//  * Fires after a single post is created or updated via the REST API.
		//  *
		//  * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		//  *
		//  * @since 4.7.0
		//  *
		//  * @param WP_Post         $post     Inserted or updated post object.
		//  * @param WP_REST_Request $request  Request object.
		//  * @param bool            $creating True when creating a post, false when updating.
		//  */
		// do_action( "rest_insert_{$this->post_type}", $post, $request, true );


		return new WP_REST_Response( 'connection updated', 200 );
	}


	/**
	 * Updates p2p connection meta from a REST request.
	 *
	 * @since 4.7.0
	 *
	 * @param int             $item 			???
	 * @param array           $meta 			???
	 * @param bool            $fill_defaults 	???
	 */
	protected function handle_item_meta( $item, $meta, $fill_defaults = false ) {
		if ( ! is_array( $meta ) || empty( $meta ) )
			return;

		if ( $fill_defaults ) {
			// loop throught fields in connection-type
			foreach ( $item['p2p_type_obj']['fields'] as $field_key => $field ) {
				if ( array_key_exists( $field_key, $meta ) ){
					// update meta, use POST value
					p2p_update_meta( $item['p2p_id'], $field_key, $meta[$field_key] );
				} elseif ( array_key_exists( 'default', $field ) ) {
					// update meta, use default value
					p2p_update_meta( $item['p2p_id'], $field_key, $field['default'] );
				}
			}
		} else {
			// loop throught POST meta
			foreach( $meta as $meta_key => $meta_val ) {
				p2p_update_meta( $item['p2p_id'], $meta_key, $meta_val );
			}
		}
	}


	/**
	 * Deletes a single post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$params = $request->get_params();

		if ( ! array_key_exists('p2p_id', $params ) || empty( $params['p2p_id'] ) )
			return new WP_Error( 'cant-delete', __( 'can\'t delete', 'posts-to-posts-backbone') . ' - ' . __( 'p2p_id missing', 'posts-to-posts-backbone'), array( 'status' => 500 ) );

		// delete connection
		$count = p2p_delete_connection( $params['p2p_id'] );

		// on fail
		if ( empty( $count ) )
			return new WP_Error( 'cant-delete', 'can\'t delete', array( 'status' => 500 ) );


		// ???
		// /**
		//  * Fires immediately after a single post is deleted or trashed via the REST API.
		//  *
		//  * They dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		//  *
		//  * @since 4.7.0
		//  *
		//  * @param object           $post     The deleted or trashed post.
		//  * @param WP_REST_Response $response The response data.
		//  * @param WP_REST_Request  $request  The request sent to the API.
		//  */
		// do_action( "rest_delete_{$this->post_type}", $post, $response, $request );


		return new WP_REST_Response( 'connection deleted', 200 );
	}





	// ??? permission checks
	// https://github.com/scribu/wp-posts-to-posts/wiki/Capabilities
	/**
	 * Checks if a given request has access to create an item.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ){
		//if ( ! empty( $request['id'] ) ) {
		//	return new WP_Error( 'rest_post_exists', __( 'Cannot create existing post.' ), array( 'status' => 400 ) );
		//}

		//$post_type = get_post_type_object( $this->post_type );

		//if ( ! empty( $request['author'] ) && get_current_user_id() !== $request['author'] && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
		//	return new WP_Error( 'rest_cannot_edit_others', __( 'Sorry, you are not allowed to create posts as this user.' ), array( 'status' => rest_authorization_required_code() ) );
		//}

		//if ( ! empty( $request['sticky'] ) && ! current_user_can( $post_type->cap->edit_others_posts ) ) {
		//	return new WP_Error( 'rest_cannot_assign_sticky', __( 'Sorry, you are not allowed to make posts sticky.' ), array( 'status' => rest_authorization_required_code() ) );
		//}

		//if ( ! current_user_can( $post_type->cap->create_posts ) ) {
		//	return new WP_Error( 'rest_cannot_create', __( 'Sorry, you are not allowed to create posts as this user.' ), array( 'status' => rest_authorization_required_code() ) );
		//}

		//if ( ! $this->check_assign_terms_permission( $request ) ) {
		//	return new WP_Error( 'rest_cannot_assign_term', __( 'Sorry, you are not allowed to assign the provided terms.' ), array( 'status' => rest_authorization_required_code() ) );
		//}

		//return true;


		$params = $request->get_params();
		// $user = ???;
		return true;
	}
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
	 * Checks if a given request has access to update an item.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ){
		$params = $request->get_params();
		// $user = ???;
		return true;
	}
	/**
	 * Checks if a given request has access to delete an item.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ){
		$params = $request->get_params();
		// $user = ???;
		return true;
	}


	/**
	 * Retrieves the post's schema, conforming to JSON Schema.
	 *
	 * @since 4.7.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema(){
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'p2p_connection',
			'type'                 => 'object',
			'properties'           => array(

				// p2p properties
				'p2p_id' => array(
					'description'  => esc_html__( 'Unique identifier for the connection.', 'my-textdomain' ),
					'type'         => 'integer',
					'readonly'     => true,
				),
				'p2p_from' => array(
					'description'  => esc_html__( 'Unique identifier for the connection from.', 'my-textdomain' ),
					'type'         => 'integer',
					'readonly'     => true,
				),
				'p2p_to' => array(
					'description'  => esc_html__( 'Unique identifier for the connection to.', 'my-textdomain' ),
					'type'         => 'integer',
					'readonly'     => true,
				),
				'p2p_type' => array(
					'description'  => esc_html__( 'p2p_type', 'my-textdomain' ),
					'type'         => 'string',
					'readonly'     => true,
				),

				// custom properties
				'p2p_type_obj' => array(
					'description'  => esc_html__( 'p2p_type_obj', 'my-textdomain' ),
					'type'         => 'object',
					'readonly'     => true,
				),
				'meta' => array(
					'description'  => esc_html__( 'connection meta', 'my-textdomain' ),
					'type'         => 'array',
				),
			),
		);

		foreach ( $schema['properties'] as $property ) {
			$property['validate_callback'] = array( $this, 'validate_arg' );
			$property['sanitize_callback'] = array( $this, 'sanitize_arg' );
		}

		return $schema;
	}


	// ???
	// /**
	//  * Our validation callback for `my-arg` parameter.
	//  *
	//  * @param mixed           $value   Value of the my-arg parameter.
	//  * @param WP_REST_Request $request Current request object.
	//  * @param string          $param   The name of the parameter in this case, 'my-arg'.
	//  */
	public function validate_arg( $value, $request, $param ) {
		$attributes = $request->get_attributes();

		if ( isset( $attributes['args'][ $param ] ) ) {
			$argument = $attributes['args'][ $param ];

			switch( $argument['type'] ) {
				case 'integer':
					if ( ! is_numeric( $value ) ) {
						return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'my-textdomain' ), $param, 'numeric' ), array( 'status' => 400 ) );
					}
					break;
				case 'string':
					if ( ! is_string( $value ) ) {
						return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'my-textdomain' ), $param, 'string' ), array( 'status' => 400 ) );
					}
					break;
				case 'array':
					if ( ! is_array( $value ) ) {
						return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'my-textdomain' ), $param, 'array' ), array( 'status' => 400 ) );
					}
					break;
				case 'object':
					if ( ! is_object( $value ) ) {
						return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not of type %2$s', 'my-textdomain' ), $param, 'object' ), array( 'status' => 400 ) );
					}
					break;
				default:
					// silence ...
			}

			switch( $param ) {
				case 'p2p_type':

					$connection_types = array_values(
						array_map( function( $connection_type ) {
							return $connection_type->name;
						}, P2P_Connection_Type_Factory::get_all_instances() )
					);

					if ( ! in_array( $value, $connection_types ) ) {
						return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%1$s is not a valid p2p_type', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
					}

					break;
				default:
					// silence ...
			}

		} else {
			// If we reused this validation callback and did not have required args then this would fire.
			return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
		}

		// If we got this far then the data is valid.
		return true;
	}

	// ???
	// /**
	//  * Our sanitize callback for `my-arg` parameter.
	//  *
	//  * @param mixed           $value   Value of the my-arg parameter.
	//  * @param WP_REST_Request $request Current request object.
	//  * @param string          $param   The name of the parameter in this case, 'my-arg'.
	//  */
	public function sanitize_arg( $value, $request, $param ) {
		$attributes = $request->get_attributes();

		if ( isset( $attributes['args'][ $param ] ) ) {
			$argument = $attributes['args'][ $param ];
			$params = $request->get_params();

			// get connection_type
			if ( array_key_exists( 'p2p_id', $params) ) {
				$item = $this->_get_prepared_item( $params['p2p_id'] )->data;
				$connection_type =  $item['p2p_type_obj'];

			} else {
				$connection_type = P2papi_REST_Connection_Types_Controller::_prepare_item_for_response( P2P_Connection_Type_Factory::get_instance( $params['p2p_type'] ) );
			}

			switch( $argument['type'] ) {
				case 'integer':
					$value = absint( $value );
					break;
				case 'string':
					$value = sanitize_text_field( $value );
					break;
				case 'array':
				case 'object':
					$value = $this->p2papi_sanitize_text_or_array_field( $value );
					break;
				default:
					$value = sanitize_text_field( $value );
			}

			switch( $param ) {
				case 'meta':
					// loop the meta array and unset invalid
					foreach ( $value as $meta_key => $meta_val ) {
						// check if meta_key is registered with the field
						if ( ! array_key_exists( $meta_key, $connection_type['fields'] ) ){
							unset( $value[$meta_key] );
							continue;
						}
						// does the field have values/options? If yes, only those are valid
						if ( array_key_exists( 'values', $connection_type['fields'][$meta_key] ) ) {
							if ( ! array_key_exists( $meta_val, $connection_type['fields'][$meta_key]['values'] ) ) {
								unset( $value[$meta_key] );
								continue;
							}
						}
					}
					break;
				default:
					// silence ...
			}

			return $value;

		} else {
			// If we reused this validation callback and did not have required args then this would fire.
			return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
		}

		// If we got this far then something went wrong don't use user input.
		return new WP_Error( 'rest_api_sad', esc_html__( 'Something went terribly wrong.', 'my-textdomain' ), array( 'status' => 500 ) );
	}

}

// Hooking it into code
// https://jacobmartella.com/2017/12/22/simple-guide-adding-wp-rest-api-controller/
function p2papi_rest_connections_controller_init(){
	$controller = new P2papi_REST_Connections_Controller();
	$controller->register_routes();
}
add_action('rest_api_init','p2papi_rest_connections_controller_init');



?>