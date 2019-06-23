<?php
require_once plugin_dir_path(dirname(__FILE__)) . 'config.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'utils.php';

class Activities_Route extends WP_REST_Controller {

    private $base = 'activities';

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {

        register_rest_route( CHD_MOBILE_API_REST_NAMESPACE, '/' . $this->base, array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(
                    'page' => array(
                        'default' => 1,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value > 0;
                        }
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value > 0;
                        },
                        'sanitize_callback' => function ($value, $request, $param) {
                            return min(50, $value);
                        }
                    ),
                    'with_avatar' => array(
                        'default' => false,
                        'validate_callback' => function ($value, $request, $param) {
                            return $value == '' || $value === 'true' || $value === 'false';
                        },
                        'sanitize_callback' => function ($value, $request, $param) {
                            return $value === 'true' ? true : false;
                        }
                    )
                ),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => array(
                    'content' => array(
                        'default' => '',
                        'validate_callback' => function ($value, $request, $param) {
                            return is_string($value) && strlen($value) > 0;
                        }
                    ),
                ),
            ),
        ) );
        register_rest_route( CHD_MOBILE_API_REST_NAMESPACE, '/' . $this->base . '/(?P<id>\d+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'                => array(
                    'id' => array(
                        'default' => null,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric($value) && $value > 0;
                        }
                    ),
                ),
            ),
        ));
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        $bp_data = bp_activity_get( array(
            'display_comments'  => false,
            'count_total'       => false,
            'per_page'          => $request['per_page'],
            'page'              => $request['page'],
            'sort'              => 'DESC',
        ) );

        if ($request['with_avatar']) {
            foreach ($bp_data['activities'] as $bp_activity) {
                $avatar_url = bp_core_fetch_avatar(array('item_id' => $bp_activity->user_id, 'html' => false));
                $bp_activity->avatar = chd_normalize_url($avatar_url);
            }
        }

        $response = array(
            'items' => $bp_data['activities'],
            'has_more_items' => (bool) $bp_data['has_more_items'],
        );

        return new WP_REST_Response($response, 200 );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check( $request ) {
        return is_user_logged_in();
    }

    /**
     * Get activity
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $result = bp_activity_get(array(
            'in' => array($request['id']),
            'spam' => 'all',
        ));

        $bp_activity = isset($result['activities'][0]) ? $result['activities'][0] : null;

        if ($bp_activity) {
            $avatar = bp_core_fetch_avatar(array('item_id' => $bp_activity->user_id, 'html' => false));
            $bp_activity->avatar = chd_normalize_url($avatar);

            return new WP_REST_Response($bp_activity, 200);
        } else {
            return new WP_Error(404, 'Activity not found');
        }
    }

    /**
     * Get item check
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request)
    {
        return is_user_logged_in();
    }

    /**
     * Create new activity
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        $activity_id = bp_activity_post_update(array(
            'content' => $request['content'],
            'user_id' => wp_get_current_user()->ID,
            'error_type' => 'bool',
        ));

        if (is_numeric($activity_id)) {
            $result = bp_activity_get(array(
                'in' => array($activity_id)
            ));

            $bp_activity = isset($result['activities'][0]) ? $result['activities'][0] : null;

            if ($bp_activity) {
                $avatar = bp_core_fetch_avatar(array('item_id' => $bp_activity->user_id, 'html' => false));
                $bp_activity->avatar = chd_normalize_url($avatar);

                return new WP_REST_Response($bp_activity, 200);
            } else {
                return new WP_Error(500, 'Internal server error');
            }

        } else {
            return new WP_Error(400, 'Failed to create new post');
        }
    }

    /**
     * Create new activity check
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request)
    {
        return is_user_logged_in();
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return parent::get_collection_params();
    }
}
