<?php
require_once plugin_dir_path(dirname(__FILE__)) . 'config.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'utils.php';

class Threads_Route extends WP_REST_Controller {

    private $base = 'threads';

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
                        'default' => 10,
                        'validate_callback' => function ($value, $request, $param) {
                            return is_numeric( $value ) && $value >= 0;
                        },
                        'sanitize_callback' => function ($value, $request, $param) {
                            return min(50, $value);
                        }
                    ),
                    'exclude_deleted' => array(
                        'default' => 'false',
                        'validate_callback' => function ($value, $request, $param) {
                            return $value == 'true' || $value == 'false';
                        },
                        'sanitize_callback' => function ($value, $request, $param) {
                            return $value == 'true';
                        }
                    )
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

        $threads = $wpdb->get_results("
            SELECT *, (
                SELECT    m.id
                FROM      {$wpdb->prefix}bp_messages_messages as m
                WHERE     m.thread_id = r.thread_id
                ORDER BY  date_sent DESC LIMIT 1
            ) as last_message_id
            FROM    {$wpdb->prefix}bp_messages_recipients as r
            WHERE   user_id = {$user->ID}
            " . ($request['exclude_deleted'] ? " AND is_deleted = 0 " : " ") . "
            ORDER BY last_message_id DESC
            LIMIT {$request['offset']}, {$request['limit']}
        ");

        $last_messages_dict = array();
        $recipients_dict = array();

        if (count($threads) > 0) {
            $message_ids = array_map(function ($thread) {
                return (int) $thread->last_message_id;
            }, $threads);
            $message_ids_str = implode(',', $message_ids);

            $last_messages = $wpdb->get_results("
                SELECT    id, sender_id, subject, message, date_sent
                FROM      {$wpdb->prefix}bp_messages_messages
                WHERE     id IN ({$message_ids_str})
            ");

            foreach ($last_messages as $msg) {
                $msg->id = (int) $msg->id;
                $msg->sender_id = (int) $msg->sender_id;
                $msg->self = $user->ID == $msg->sender_id;
                $msg->subject = (string) stripslashes(wp_specialchars_decode($msg->subject));
                $msg->message = (string) stripslashes(wp_specialchars_decode($msg->message));
                $last_messages_dict[$msg->id] = $msg;
            }

            $thread_ids = array_map(function ($thread) {
                return (int) $thread->thread_id;
            }, $threads);
            $thread_ids_str = implode(',', $thread_ids);

            $recipients = $wpdb->get_results("
                SELECT    r.*, user_login, user_nicename, user_email, display_name
                FROM      {$wpdb->prefix}bp_messages_recipients as r
                LEFT JOIN {$wpdb->prefix}users as u ON u.ID = r.user_id
                WHERE     r.thread_id IN ({$thread_ids_str})
                AND       r.user_id != {$user->ID}
            ");

            foreach ($recipients as $recipient) {
                $recipient->id = (int) $recipient->id;
                $recipient->user_id = (int) $recipient->user_id;
                $recipient->unread_count = (int) $recipient->unread_count;
                $recipient->sender_only = (int) $recipient->sender_only == '1';
                $recipient->is_deleted = (int) $recipient->is_deleted == '1';

                $avatar_url = bp_core_fetch_avatar(array('item_id' => $recipient->user_id, 'html' => false));
                $recipient->avatar = chd_normalize_url($avatar_url);

                if (!$recipient->user_login) {
                    $recipient->user_name = '[Usunięty użytkownik]';
                    $recipient->user_nicename = 'deleted-' . $recipient->user_id;
                    $recipient->user_login = 'deleted-' . $recipient->user_id;
                    $recipient->display_name = '[Usunięty użytkownik]';
                }

                $recipients_dict[$recipient->thread_id][] = $recipient;
                unset($recipient->thread_id);
            }
        }

        foreach ($threads as $thread) {
            $thread->last_message = $last_messages_dict[$thread->last_message_id];
            $thread->recipients = $recipients_dict[$thread->thread_id];

            $thread->self_recipient_id = (int) $thread->id;
            $thread->user_id = (int) $thread->user_id;
            $thread->id = (int) $thread->thread_id;
            $thread->unread_count = (int) $thread->unread_count;
            $thread->sender_only = $thread->sender_only == '1';
            $thread->is_deleted = $thread->is_deleted == '1';

            unset($thread->last_message_id);
            unset($thread->user_id);
            unset($thread->thread_id);
        }

        $total = $wpdb->get_row("
            SELECT COUNT(*) as c
            FROM    {$wpdb->prefix}bp_messages_recipients
            WHERE   user_id = {$user->ID}
            " . ($request['exclude_deleted'] ? " AND is_deleted = 0 " : " ") . "
        ");

        $response = array(
            'offset' => (int) $request['offset'],
            'limit' => (int) $request['limit'],
            'count' => count($threads),
            'total' => (int) $total->c,
            'items' => $threads,
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
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return parent::get_collection_params();
    }
}
