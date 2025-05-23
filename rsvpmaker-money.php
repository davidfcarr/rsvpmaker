<?php
function rsvpmaker_money_table() {
    global $wpdb;
	$money_table = $wpdb->prefix . 'rsvpmaker_money';
    $current_version = 2;
	$version       = (int) get_option( 'rsvpmaker_money_table' );
	if ( $version < $current_version ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE `$money_table` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`email` varchar(255) NOT NULL,
		`description` varchar(255) NOT NULL,
		`date` datetime NOT NULL,
		`status` varchar(255) NOT NULL,
		`transaction_id` varchar(255) NOT NULL,
		`user_id` int(11) NOT NULL DEFAULT '0',
		`metadata` text NOT NULL,
		`amount` float NOT NULL,
		`fee` float NOT NULL,
		`tracking_key` varchar(255) NOT NULL,
		`tracking_value` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	"
		);
		update_option( 'rsvpmaker_money_table', $current_version );
	}
    return $money_table;
}

function rsvpmaker_money_tx($atts) {
    global $wpdb;
	$money_table = rsvpmaker_money_table();
    $name = (isset($atts['name'])) ? $atts['name'] : '';
    $email = (isset($atts['email'])) ? $atts['email'] : '';
    $description = (isset($atts['description'])) ? $atts['description'] : '';
    $date = (isset($atts['date'])) ? $atts['date'] : date('Y-m-d H:i:s');
    $status = (isset($atts['status'])) ? $atts['status'] : 'Stripe';
    $transaction_id = (isset($atts['transaction_id'])) ? $atts['transaction_id'] : '';
    $user_id = (isset($atts['user_id'])) ? $atts['user_id'] : 0;
    $metadata = (isset($atts['metadata'])) ? $atts['metadata'] : '';
    $amount = (isset($atts['amount'])) ? $atts['amount'] : '';
    $fee = (isset($atts['fee'])) ? $atts['fee'] : '';
    $tracking_key = (isset($atts['tracking_key'])) ? $atts['tracking_key'] : '';
    $tracking_value = (isset($atts['tracking_value'])) ? $atts['tracking_value'] : '';
    $existing = $wpdb->get_row("SELECT * FROM $money_table WHERE transaction_id='$transaction_id' ");
    if($existing) {
        $wpdb->update($money_table,array('name'=>$name,
    'email'  => $email,
    'description'  => $description,
    'date'  => $date,
    'status'  => $status,
    'user_id'  => $user_id,
    'metadata'  => $metadata,
    'amount'  => $amount,
    'tracking_key' => $tracking_key,
    'tracking_value' => $tracking_value),array('transaction_id'  => $transaction_id));
        return $existing;
    }
    else
        $wpdb->insert($money_table,array('name'=>$name,
    'email'  => $email,
    'description'  => $description,
    'date'  => $date,
    'status'  => $status,
    'transaction_id'  => $transaction_id,
    'user_id'  => $user_id,
    'metadata'  => $metadata,
    'amount'  => $amount,
    'fee'  => $fee,
    'tracking_key' => $tracking_key,
    'tracking_value' => $tracking_value));
}
