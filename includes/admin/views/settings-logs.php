<?php
/**
 * 
 */

$args = array(
    'paged'     => 1,
    'per_page'  => VOYNOTIF_logs::POSTS_PER_PAGE
);

if( isset( $_GET['orderby'] ) && !empty( $_GET['orderby'] ) ) {
    $args['orderby'] = $_GET['orderby'];
}

if( isset( $_GET['order'] ) && !empty( $_GET['order'] ) ) {
    $args['order'] = $_GET['order'];
}

if( isset( $_GET['paged'] ) && !empty( $_GET['paged'] ) ) {
    $args['paged'] = $_GET['paged'];
}

if( isset( $_GET['s'] ) && !empty( $_GET['s'] ) ) {
    $args['s'] = $_GET['s'];
}

?>
<div class="voy-row">
    <div class="voy-col-6">
        <h3><?php printf( __('%d Results', 'notifications-center'), VOYNOTIF_logs::get_logs_count($args) ); ?></h3>
    </div>
    <div class="voy-col-6 voy-text-right">
        <form style="margin-top: 10px;" action="<?php echo remove_query_arg('s'); ?>" method="get">
            <?php
            if( !empty( $_GET ) ) {
                foreach ($_GET as $key => $value) {
                    if( $key == 's' ) continue;
                    echo("<input type='hidden' name='$key' value='$value'/>");
                }  
            }
            ?>
            <input type="search" id="post-search-input" name="s" value="<?php if( isset( $_GET['s'] ) ) echo esc_html($_GET['s']); ?>">
            <input type="submit" id="search-submit" class="button" value="<?php _e('Search user', 'notifications-center'); ?>">             
        </form>       
    </div>
</div>

<table class="voynotif_list_logs">
    <thead>
        <tr>
            <th>ID</th>            
            <th>            
                <a href="<?php echo VOYNOTIF_logs::get_order_url('date'); ?>"><?php _e('Date', 'notifications-center'); ?></a>
            </th>
            <th><?php _e('Notification', 'notifications-center'); ?></th>
            <th>
                <a href="<?php echo VOYNOTIF_logs::get_order_url('type'); ?>"><?php _e('Type', 'notifications-center'); ?></a>
            </th>
            <th>
                <a href="<?php echo VOYNOTIF_logs::get_order_url('recipient'); ?>"><?php _e('Recipient', 'notifications-center'); ?></a>
            </th>
            <th>
                <a href="<?php echo VOYNOTIF_logs::get_order_url('subject'); ?>"><?php _e('Subject', 'notifications-center'); ?></a>
            </th>
            <th>
                <?php _e('Context', 'notifications-center'); ?>
            </th>
            <th>
                <a href="<?php echo VOYNOTIF_logs::get_order_url('status'); ?>"><?php _e('Status', 'notifications-center'); ?></a>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if (VOYNOTIF_logs::get_logs()) { ?>
            <?php foreach (VOYNOTIF_logs::get_logs( $args ) as $log) { ?>
                <?php
                $log_date = new DateTime($log->date);
                $log->context = unserialize($log->context);
                ?>
                <tr>
                    <td><?php echo $log->id; ?></td>
                    <td>
                        <?php echo VOYNOTIF_helpers::getDateTimeFormat($log_date); ?></td>
                    <td>
                        <?php if( get_post_type( $log->notification_id ) == 'voy_notification' ) { ?> 
                        <a href="<?php echo get_edit_post_link( $log->notification_id ); ?>"><?php echo get_the_title($log->notification_id); ?></a>
                        <?php } else {                           
                            echo apply_filters('voynotif/logs/notification_title/type='.$log->type, false, $log);
                        }
                        ?>
                    </td>
                    <td><?php echo voynotif_get_notification_type_title($log->type); ?></td>
                    <td><?php echo $log->recipient; ?></td>
                    <td><?php echo $log->subject; ?></td>
                    <td>
                        <?php echo VOYNOTIF_logs::get_context_html($log->context, $log->type); ?>
                    </td>
                    <td>
                        <?php echo VOYNOTIF_logs::get_status_title( $log->status, $log ); ?>
                    </td>
                </tr>
            <?php } ?>  
        <?php } ?>
    </tbody>
</table>
<div class="voynotif_pagination">
    <?php 
    $total_pages = ceil(VOYNOTIF_logs::get_logs_count($args) / $args['per_page']);
    echo paginate_links( array(
        'base'               => '%_%',
        'format'             => '?paged=%#%',
        'total'              => $total_pages,
        'current'            => $args['paged'],
        'prev_text'          => '‹',
        'next_text'          => '›',
        'end_size'           => 3,
        'mid_size'           => 3,
        'prev_next'          => true,
    ) );
    ?>
</div>
