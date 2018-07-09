<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class P2papi_REST_Base_Controller extends WP_REST_Controller {

    /**
     * The namespace of plugins controller's routes.
     *
     * The property name is a workaround, because my PHPParser will throw an error, if we use 'namespace', so we use 'name_space'
     *
     * @since 0.0.1
     * @var string
     */
	protected $name_space;

    /**
     * Constructor.
     *
     * @since 0.0.1
     */
    public function __construct() {
        // $this->namespace = 'p2papi/v1';
        $this->name_space = 'p2papi/v1';
    }


	/**
	 * Retrieves the item's schema for display / public consumption purposes.
	 *
	 * @since 4.7.0
	 *
	 * @return array Public item schema data.
	 */
	public function get_public_item_schema() {
		$schema = $this->get_item_schema();
		foreach ( $schema['properties'] as &$property ) {
			unset( $property['arg_options'] );
			unset( $property['validate_callback'] );
			unset( $property['sanitize_callback'] );
		}
		return rest_ensure_response( $schema );
	}


	// ???
	// /**
	//  * Retrieves an array of endpoint arguments from the item schema for the controller.
	//  *
	//  * @since 4.7.0
	//  *
	//  * @param string $method Optional. HTTP method of the request. The arguments for `CREATABLE` requests are
	//  *                       checked for required values and may fall-back to a given default, this is not done
	//  *                       on `EDITABLE` requests. Default WP_REST_Server::CREATABLE.
	//  * @return array Endpoint arguments.
	//  */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {

		$schema            = $this->get_item_schema();
		$schema_properties = ! empty( $schema['properties'] ) ? $schema['properties'] : array();
		$endpoint_args     = array();

		foreach ( $schema_properties as $field_id => $params ) {

			$endpoint_args[ $field_id ]['validate_callback'] = array_key_exists( 'validate_callback', $params ) ? $params['validate_callback'] : 'rest_validate_request_arg';
			$endpoint_args[ $field_id ]['sanitize_callback'] = array_key_exists( 'sanitize_callback', $params ) ? $params['sanitize_callback'] : 'rest_sanitize_request_arg';

			if ( isset( $params['description'] ) ) {
				$endpoint_args[ $field_id ]['description'] = $params['description'];
			}

			if ( WP_REST_Server::CREATABLE === $method && isset( $params['default'] ) ) {
				$endpoint_args[ $field_id ]['default'] = $params['default'];
			}

			if ( WP_REST_Server::CREATABLE === $method && ! empty( $params['required'] ) ) {
				$endpoint_args[ $field_id ]['required'] = true;
			}

			foreach ( array( 'type', 'format', 'enum', 'items', 'properties', 'additionalProperties' ) as $schema_prop ) {
				if ( isset( $params[ $schema_prop ] ) ) {
					$endpoint_args[ $field_id ][ $schema_prop ] = $params[ $schema_prop ];
				}
			}

			// Merge in any options provided by the schema property.
			if ( isset( $params['arg_options'] ) ) {

				// Only use required / default from arg_options on CREATABLE endpoints.
				if ( WP_REST_Server::CREATABLE !== $method ) {
					$params['arg_options'] = array_diff_key( $params['arg_options'], array( 'required' => '', 'default' => '' ) );
				}

				$endpoint_args[ $field_id ] = array_merge( $endpoint_args[ $field_id ], $params['arg_options'] );
			}
		}

		return $endpoint_args;
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

			return $value;

		} else {
			// If we reused this validation callback and did not have required args then this would fire.
			return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s was not registered as a request argument.', 'my-textdomain' ), $param ), array( 'status' => 400 ) );
		}

		// If we got this far then something went wrong don't use user input.
		return new WP_Error( 'rest_api_sad', esc_html__( 'Something went terribly wrong.', 'my-textdomain' ), array( 'status' => 500 ) );
	}

}

?>