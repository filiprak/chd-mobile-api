<?php
require_once plugin_dir_path(dirname(__FILE__)) . 'config.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'utils.php';

class Messages_Route extends WP_REST_Controller {

    private $base = 'messages';

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
                    'offset' => array(
                        'default' => 0,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value >= 0;
                        }
                    ),
                    'limit' => array(
                        'default' => 20,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value >= 0;
                        },
                        'sanitize_callback' => function ($value, $request, $param) {
                            return min(50, $value);
                        }
                    ),
                    'thread_id' => array(
                        'default' => -1,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value > 0;
                        },
                    )
                ),
            ),
        ));
        register_rest_route( CHD_MOBILE_API_REST_NAMESPACE, '/' . $this->base, array(
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => array(
                    'subject' => array(
                        'default' => 'Wiadomość wysłana z aplikacji mobilnej',
                        'validate_callback' => function ($value, $request, $param) {
                            return is_string($value) && strlen($value) > 0;
                        }
                    ),
                    'content' => array(
                        'default' => '',
                        'validate_callback' => function ($value, $request, $param) {
                            return is_string($value) && strlen($value) > 0;
                        }
                    ),
                    'thread_id' => array(
                        'default' => 'new',
                        'validate_callback' => function ($value, $request, $param) {
                            return $value === 'new' || (is_numeric($value) && $value > 0);
                        }
                    ),
                    'recipients' => array(
                        'default' => array(),
                        'validate_callback' => function ($value, $request, $param) {
                            if (is_array($value) && count($value) > 0 && count($value) < 51) {
                                foreach ($value as $item) {
                                    if (!is_numeric($item)) {
                                        return false;
                                    } else if ($item < 1) {
                                        return false;
                                    }
                                }
                                return true;
                            } else {
                                error_log($request['thread_id']);
                                return $request['thread_id'] !== 'new';
                            }
                        }
                    ),
                ),
            ),
        ) );
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {

        global $wpdb;

        $user = wp_get_current_user();

        $messages = $wpdb->get_results("
            SELECT *
            FROM    {$wpdb->prefix}bp_messages_messages
            WHERE   thread_id = {$request['thread_id']}
            ORDER BY date_sent DESC
            LIMIT {$request['offset']}, {$request['limit']}
        ");

        $total = $wpdb->get_row("
            SELECT COUNT(*) as c
            FROM    {$wpdb->prefix}bp_messages_messages
            WHERE   thread_id = {$request['thread_id']}
        ");

        foreach ($messages as $item) {
            $item->id = (int) $item->id;
            $item->thread_id = (int) $item->thread_id;
            $item->sender_id = (int) $item->sender_id;
            $item->subject = (string) stripslashes(wp_specialchars_decode($item->subject));
            $item->message = (string) stripslashes(wp_specialchars_decode($item->message));

            if ($item->sender_id == $user->ID) {
                $item->self = true;
            }
        }


        return new WP_REST_Response(array(
            'offset' => (int) $request['offset'],
            'limit' => (int) $request['limit'],
            'count' => count($messages),
            'total' => (int) $total->c,
            'items' => $messages,
        ), 200 );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check( $request ) {

        global $wpdb;

        if (is_user_logged_in()) {
            $thread_id = (int) $request['thread_id'];
            $user = wp_get_current_user();

            $user_threads = $wpdb->get_results("
                SELECT  thread_id
                FROM    {$wpdb->prefix}bp_messages_recipients
                WHERE   thread_id = {$thread_id}
                AND     user_id = {$user->ID}
            ");

            return count($user_threads) > 0;
        }

        return false;
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
     * Create new message
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        $user = wp_get_current_user();

        $args = array(
            'sender_id' => $user->ID,
            'subject' => $request['subject'],
            'content' => $request['content'],
            'error_type' => 'wp_error',
        );

        if ($request['thread_id'] === 'new') {
            $args['recipients'] = $request['recipients'];
        } else {
            $args['thread_id'] = $request['thread_id'];
        }

        $result = messages_new_message($args);

        if ($result === false) {
            return new WP_Error(500, 'Failed to create message');
        } else if (is_wp_error($result)) {
            return $result;
        } else {
            return new WP_REST_Response(array(
                'thread_id' => $result,
            ), 200);
        }
    }

    /**
     * Create new message check
     * @param WP_REST_Request $request
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request)
    {
        global $wpdb;

        if (is_user_logged_in()) {
            if (is_numeric($request['thread_id'])) {
                $user = wp_get_current_user();
                $user_threads = $wpdb->get_results("
                    SELECT  thread_id
                    FROM    {$wpdb->prefix}bp_messages_recipients
                    WHERE   thread_id = {$request['thread_id']}
                    AND     user_id = {$user->ID}
                ");
                return count($user_threads) > 0;
            } else {
                return true;
            }
        }
        return false;
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
