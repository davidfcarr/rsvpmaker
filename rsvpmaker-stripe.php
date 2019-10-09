<?php

function rsvpmaker_to_stripe ($rsvp) {
	global $post;
	$vars['description'] = $post->post_title;
	$vars['name'] = $rsvp['first'].' '.$rsvp['last'];
	if(isset($_GET['rsvp']))
		$vars['rsvp_id'] = (int) $_GET['rsvp'];
	else	
		$vars['rsvp_id'] = $rsvp['id'];
	$include = array('amount','rsvp_id','email','event');
	foreach($rsvp as $index => $value)
		if(in_array($index,$include))
			$vars[$index] = $value;
	// transform
	return rsvpmaker_stripe_form($vars);
}

function rsvpmaker_stripe_form($vars, $show = false) {
global $post, $rsvp_options, $current_user, $button;
$currency = (empty($rsvp_options['paypal_currency'])) ? 'usd' : strtolower($rsvp_options['paypal_currency']);
$vars['currency'] = $currency;

if(empty($button))
	$button = 1;
else
	$button++;
ob_start();
require_once('stripe-php/init.php');
$keys = get_rsvpmaker_stripe_keys ();
$public = $keys['pk'];
$secret = $keys['sk'];
$varkey = 'button'.$button;
$url = add_query_arg('varkey',$varkey,get_permalink($post->ID));

if(strpos($public,'test'))
	$vars = array('test' => 'TEST TRANSACTION')+$vars;
	
\Stripe\Stripe::setApiKey($secret);

\Stripe\Stripe::setAppInfo(
  "WordPress RSVPMaker events management plugin",
  get_rsvpversion(),
  "https://rsvpmaker.com"
);

update_post_meta($post->ID,$varkey,$vars);
$price = $vars['amount'] * 100;
?>
<p>
<form action="<?php echo $url; ?>" method="post">
  <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
          data-key="<?php echo $public; ?>"
		  data-email="<?php if(!empty($vars['email'])) echo $vars['email']; ?>"
          data-description="<?php echo htmlentities($vars['description']); ?>"
          data-amount="<?php echo $price; ?>"
          data-currency="<?php echo $currency; ?>"
          data-locale="auto"></script>
</form>
</p>
<?php
$prefix = ($rsvp_options["paypal_currency"] == 'USD') ? '$' : '';
if($show)
	printf('<p>%s%s %s<br />%s</p>',$prefix,$vars['amount'],$rsvp_options["paypal_currency"],$vars["description"]);
if(strpos($public,'test') && !isset($_GET['hidetest']))
	printf('<p>%s</p>',__('Stripe is in TEST mode. To simulate a transaction, use:<br />Credit card 4111 1111 1111 1111<br />Any future date<br />Any three digit code.','rsvpmaker'));
return ob_get_clean();
}

add_filter('the_content','rsvpmaker_stripe_confirm',999);

function rsvpmaker_stripe_confirm($content) {
	global $post;
	if(isset($_GET['rsvpstripeconfirm']))
	{
		$confirm = get_post_meta($post->ID,$_GET['rsvpstripeconfirm'],true);
		if(empty($confirm))
			$confirm = 'Error retrieving confirmation message';
		//delete_option($_GET['rsvpstripeconfirm']);
		return $confirm;
	}
	return $content;
}

add_action('wp','rsvpmaker_stripe_charge_now',999);

function rsvpmaker_stripe_charge_now ()
{
global $rsvp_options;
$currency = (empty($rsvp_options['paypal_currency'])) ? 'usd' : strtolower($rsvp_options['paypal_currency']);
if(isset($_POST['add_name_to_charge']))
{
	rsvpmaker_stripe_add_name();
	return;
}
	
if(!isset($_POST['stripeToken']))
	return;

ob_start();
$varkey = $_REQUEST['varkey'];
global $post, $wpdb, $current_user;
$vars = get_post_meta($post->ID,$varkey,true);
$vars['email'] = $_POST['stripeEmail'];
if(!empty($vars['paymentType']) && strpos($vars['paymentType'],'scription:'))
	return rsvpmaker_stripe_subscription($vars);
require_once('stripe-php/init.php');
$keys = get_rsvpmaker_stripe_keys ();
$public = $keys['pk'];
$secret = $keys['sk'];

\Stripe\Stripe::setApiKey($secret);

\Stripe\Stripe::setAppInfo(
  "WordPress RSVPMaker events management plugin",
  get_rsvpversion(),
  "https://rsvpmaker.com"
);
	
$token  = $_POST['stripeToken'];
$email  = $_POST['stripeEmail'];
$success = false;
  $customer = \Stripe\Customer::create([
      'email' => $email,
      'source'  => $token,
  ]);
try {
  $charge = \Stripe\Charge::create([
      'customer' => $customer->id,
      'amount'   => $vars['amount'] * 100,
      'currency' => $currency,
  ]);
$success = true;//only set on success
}
 catch(\Stripe\Error\Card $e) {
  // Since it's a decline, \Stripe\Error\Card will be caught
  $body = $e->getJsonBody();
  $err  = $body['error'];

  print('Status is:' . $e->getHttpStatus() . "\n");
  print('Type is:' . $err['type'] . "\n");
  print('Code is:' . $err['code'] . "\n");
  // param is '' in this case
  print('Param is:' . $err['param'] . "\n");
  print('Message is:' . $err['message'] . "\n");
} 
catch (\Stripe\Error\RateLimit $e) {
echo "<h1>Too Many Requests to Stripe Payment Processing Service</h1>";
	   $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Too many requests made to the API too quickly
} catch (\Stripe\Error\InvalidRequest $e) {
echo "<h1>Invalid Request to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Invalid parameters were supplied to Stripe's API
} catch (\Stripe\Error\Authentication $e) {
echo "<h1>Error Authenticating to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
} catch (\Stripe\Error\ApiConnection $e) {	 
echo "<h1>Problem Connecting to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Network communication with Stripe failed
} catch (\Stripe\Error\Base $e) {
echo "<h1>Error: Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (Exception $e) {
echo "<h1>Error</h1>";
print_r($e);  // Something else happened, completely unrelated to Stripe
}
	
$key = 'conf:'.time();
if($success && !empty($charge->id)) // something is wrong
{
$vars['charge_id'] = $charge->id;
rsvpmaker_stripe_payment_log($vars,$key);
echo '<h1>Thank you!</h1>';
foreach($vars as $index => $value)
	printf('<div>%s: %s</div>',$index,$value);
if(!empty($vars['rsvp_id']))
	{
		$rsvp_id = $vars['rsvp_id'];
		$paid = $vars['amount'];
		$invoice_id = get_post_meta($post->ID,'_open_invoice_'.$rsvp_id, true);
		if($invoice_id)
		{
		$charge = get_post_meta($post->ID,'_invoice_'.$rsvp_id, true);
		$paid_amounts = get_post_meta($post->ID,'_paid_'.$rsvp_id);
		if(!empty($paid_amounts))
		foreach($paid_amounts as $payment)
			$paid += $payment;
		$wpdb->query("UPDATE ".$wpdb->prefix."rsvpmaker SET amountpaid='$paid' WHERE id=$rsvp_id ");
		add_post_meta($post->ID,'_paid_'.$rsvp_id,$vars['amount']);
		delete_post_meta($post->ID,'_open_invoice_'.$rsvp_id);
		delete_post_meta($post->ID,'_invoice_'.$rsvp_id);
		}	
	}	
if(isset($vars['tracking']))
	add_post_meta($post->ID,$vars['tracking'],$vars['amount']);
}
$confirmation = ob_get_clean();
add_post_meta($post->ID,'stripepay_'.strtolower($vars['email']),array('amount' => $vars['amount'],'date' => date('r')));
update_post_meta($post->ID,$key,$confirmation);
$url = add_query_arg('rsvpstripeconfirm',$key,get_permalink($post->ID));
header('Location: '.$url);
exit();
}

function rsvpmaker_stripe_add_name() {
	global $wpdb;	
	$post_id = $_POST['post_id'];
	$charge_id = $_POST['add_name_to_charge'];
	$confkey = $_POST['confkey'];
	$name = stripslashes($_POST['name']);
	$sql = "SELECT * FROM $wpdb->postmeta WHERE post_id=$post_id AND meta_key='rsvpmaker_stripe_payment' AND meta_value LIKE '%".$charge_id."%' ";
	$row = $wpdb->get_row($sql);
	$vars = $row->meta_value;
	if(!empty($vars))
		$vars = unserialize($vars);
	$vars = array('name'=>$name)+$vars;
	$sql = $wpdb->prepare("UPDATE $wpdb->postmeta SET meta_value=%s WHERE meta_id=%d",serialize($vars),$row->meta_id);
	$ok = $wpdb->query($sql);
	if($ok)
	{
		$confirmation = "<h1>Thank you!</h1>\n";
		foreach($vars as $name => $value)
			$confirmation .= '<div>'.$name.': '.$value.'</div>';
		update_post_meta($post_id,$confkey,$confirmation);
		rsvpmaker_stripe_notify($vars);
		$url = add_query_arg('rsvpstripeconfirm',$confkey,get_permalink($post_id));
		header('Location: '.$url);
		exit();
	}
	printf('<h1>Error</h1><p>key %s<br />confirmation </p>%s<br />vars:<pre>%s</pre>POST<pre>%s</pre>',$key, $confirmation, var_export($vars,true),var_export($_POST,true));
	exit();
}

function rsvpmaker_stripe_subscription($vars) {
global $post, $wpdb, $current_user,$rsvp_options;
$currency = (empty($rsvp_options['paypal_currency'])) ? 'usd' : strtolower($rsvp_options['paypal_currency']);

ob_start();
require_once('stripe-php/init.php');

$keys = get_rsvpmaker_stripe_keys ();
$public = $keys['pk'];
$secret = $keys['sk'];

\Stripe\Stripe::setApiKey($secret);
	
$token  = $_POST['stripeToken'];
$email  = $_POST['stripeEmail'];
$token  = $_POST['stripeToken'];
$customer = \Stripe\Customer::create([
      'email' => $email,
      'source'  => $token,
  ]);

$pt = explode(':',$vars['paymentType']);
$interval = $pt[1];
$slug = str_replace(' ','',$interval).'_'.$vars['amount'];
if($interval == '1 year')
	$parray = [
  "amount" => $vars['amount'] * 100,
  "interval" => "year",
  "product" => [
    "name" => "Recurring charge, ".get_bloginfo('name')
  ],
  "currency" => $currency,
  "id" => $slug
];
elseif($interval == '6 months')
	$parray = [
  "amount" => $vars['amount'] * 100,
  "interval" => "month",
  "interval_count" => 6,
  "product" => [
    "name" => "Recurring charge, ".get_bloginfo('name')
  ],
  "currency" => $currency,
  "id" => $slug
];
elseif($interval == 'monthly')
	$parray = [
  "amount" => $vars['amount'] * 100,
  "interval" => "month",
  "product" => [
    "name" => "Recurring charge, ".get_bloginfo('name')
  ],
  "currency" => $currency,
  "id" => $slug
];
	
$plans = get_option('stripe_dues_plans');
if(empty($plans))
	$plans = array();
if(!in_array($slug,$plans))
{
try {
\Stripe\Plan::create($parray);	
}
catch (\Stripe\Error\RateLimit $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Too many requests made to the API too quickly
} catch (\Stripe\Error\InvalidRequest $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Invalid parameters were supplied to Stripe's API
} catch (\Stripe\Error\Authentication $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
} catch (\Stripe\Error\ApiConnection $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Network communication with Stripe failed
} catch (\Stripe\Error\Base $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (\Stripe\Error\Base $e) {
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (Exception $e) {
print_r($e);
  // Something else happened, completely unrelated to Stripe
}	
//could fail if plan already exists
$plans[] = $slug;
update_option('stripe_dues_plans',$plans);
update_option('stripe_price_'.$slug,$vars['amount']);
}

$success = false;
try {
// Subscribe the customer to the plan
$subscription = \Stripe\Subscription::create(array(
    "customer" => $customer->id,
    "plan" => $slug
));
$success = true;//only set on success
}
 catch(\Stripe\Error\Card $e) {
  // Since it's a decline, \Stripe\Error\Card will be caught
  $body = $e->getJsonBody();
  $err  = $body['error'];

  print('Status is:' . $e->getHttpStatus() . "\n");
  print('Type is:' . $err['type'] . "\n");
  print('Code is:' . $err['code'] . "\n");
  // param is '' in this case
  print('Param is:' . $err['param'] . "\n");
  print('Message is:' . $err['message'] . "\n");
} catch (\Stripe\Error\RateLimit $e) {
echo "<h1>Too Many Requests to Stripe Payment Processing Service</h1>";
	   $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Too many requests made to the API too quickly
} catch (\Stripe\Error\InvalidRequest $e) {
echo "<h1>Invalid Request to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Invalid parameters were supplied to Stripe's API
} catch (\Stripe\Error\Authentication $e) {
echo "<h1>Error Authenticating to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Authentication with Stripe's API failed
  // (maybe you changed API keys recently)
} catch (\Stripe\Error\ApiConnection $e) {	 
echo "<h1>Problem Connecting to Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Network communication with Stripe failed
} catch (\Stripe\Error\Base $e) {
echo "<h1>Error: Stripe Payment Processing Service</h1>";
  $body = $e->getJsonBody();
  $err  = $body['error'];
print_r($err);
  // Display a very generic error to the user, and maybe send
  // yourself an email
} catch (Exception $e) {
echo "<h1>Error</h1>";
print_r($e);  // Something else happened, completely unrelated to Stripe
}
$key = 'conf'.time().rand();
if(!$success || empty($subscription->id)) // something is wrong
	$confirmation = ob_get_clean();
else
{
$vars['charge_id'] = $subscription->id;
rsvpmaker_stripe_payment_log($vars,$key);
echo '<h1>Thank you!</h1>';
foreach($vars as $index => $value)
	printf('<div>%s: %s</div>',$index,$value);
$confirmation = ob_get_clean();	
}
update_post_meta($post->ID,$key,$confirmation);
$url = add_query_arg('rsvpstripeconfirm',$key,get_permalink($post->ID));
header('Location: '.$url);
exit();
}

function rsvpmaker_donation_prompt($defaultamount) {
	return sprintf('<form method="post" action="%s"><input type="text" name="donation" value="%s" /><button>%s</button></form>',get_permalink(),$defaultamount,__('Set Amount','rsvpmaker'));
}

function rsvpmaker_stripecharge ($atts) {
		if(is_admin())
		return;
	
	global $current_user;
	
	$vars['description'] =(!empty($atts['description'])) ? $atts['description'] : 'charge from '.get_bloginfo('name');
	$vars['paymentType'] = $paymentType = (empty($atts['paymentType'])) ? 'once' : $atts['paymentType'];
	$show =(!empty($atts['showdescription']) && ($atts['showdescription'] == 'yes')) ? true : false;

	if($paymentType == 'donation')
	{
		if(isset($_REQUEST['donation']))
			$atts['amount'] = $_REQUEST['donation'];
		else {
			return rsvpmaker_donation_prompt($atts['amount']);
		}
	}
	
	if($paymentType == 'schedule')
	{
	$months = array('january','february','march','april','may','june','july','august','september','october','november','december');
	$index = date('n') - 1;
	if(isset($_GET['next']))
	{
		if($index == 11)
			$index = 0;
		else
			$index++;		
	}
	$month = $months[$index];
	$vars['amount'] = $atts[$month];
	$vars['description'] = $vars['description'].': '.ucfirst($month);
	if(!empty($current_user->user_email))
	$vars['email'] = $current_user->user_email;
	return rsvpmaker_stripe_form($vars, $show);
	}
	
	$vars['amount'] = (!empty($atts['amount'])) ? $atts['amount'] : '';
	if($paymentType != 'once')
		$vars['description'] .= ' '.$paymentType;
	return rsvpmaker_stripe_form($vars,$show);
}

function rsvpmaker_stripe_payment_log($vars,$confkey) {
global $post, $current_user, $wpdb;
fix_timezone();
$vars['timestamp'] = date('r');
if(is_user_logged_in())
{
if(isset($current_user->display_name))
	$name = $current_user->display_name;
elseif($current_user->user_login)
	$name = $current_user->user_login;
if(!empty($name))
	$vars = array('name'=>$name)+$vars;
if(isset($current_user->ID))
	$vars['user_id'] = $current_user->ID;
update_user_meta($current_user->ID,'rsvpmaker_stripe_payment',$vars);
rsvpmaker_stripe_notify($vars);
}
else {
	$user = get_user_by( 'email', $vars['email'] );
	if($user)
	{
		if(isset($user->display_name))
			$name = $user->display_name;
		elseif($user->user_login)
			$name = $user->user_login;
		if(!empty($name))
		$vars = array('name'=>$name)+$vars;
		//add_user_meta($user->ID,'rsvpmaker_stripe_payment',$vars);
		rsvpmaker_stripe_notify($vars);
	}
	else {
		$sql = 'SELECT first, last FROM '.$wpdb->prefix."rsvpmaker WHERE email LIKE '".$vars['email']."' ORDER BY id DESC";
		$row = $wpdb->get_row($sql);
		if(!empty($row->first) && !empty($row->last))
		{
		$vars = array('name' => $row->first.' '.$row->last)+$vars;
		rsvpmaker_stripe_notify($vars);
		}
		else
			stripe_prompt_for_name($vars,$confkey); // try to get name for log and notification		
	}
}
add_post_meta($post->ID,'rsvpmaker_stripe_payment',$vars);
do_action('rsvpmaker_stripe_payment',$vars);
}

function rsvpmaker_stripe_notify($vars) {
	$keys = get_rsvpmaker_stripe_keys ();
	$public = $keys['pk'];
	$secret = $keys['sk'];
	$to = $keys['notify'];
	if(empty($to))
		return;
	$mail['to'] = $to;
	$mail['from'] = get_option('admin_email');
	$mail['fromname'] = get_option('blogname');
	$mail['html'] = '';
	foreach ($vars as $index => $value)
	{
		$mail['html'] .= sprintf('<div>%s: %s</div>',$index, $value);
	}
	$mail['subject'] = 'Stripe payment from '.$vars['name'];
	rsvpmailer($mail);
}

function stripe_prompt_for_name($vars,$confkey) {
global $post;
?>
<form action="<?php echo get_permalink(); ?>" method="post">
<input type="hidden" name="add_name_to_charge" value="<?php echo $vars['charge_id'];?>" />
<input type="hidden" name="confkey" value="<?php echo $confkey;?>" />
<input type="hidden" name="post_id" value="<?php echo $post->ID;?>" />
Your Name: <input type="text" name="name" />
	<br /><em>Please add your name for our records</em><br />
	<button>Submit</button>
</form>
<?php
}

function rsvpmaker_stripe_report () {
	echo '<h1>Stripe Charges</h1>';
	
	if(isset($_GET['history'])) {
		stripe_balance_history();
	}

	global $wpdb;
	$sql = "SELECT * FROM $wpdb->postmeta WHERE meta_key='rsvpmaker_stripe_payment' ORDER BY meta_id DESC";
	$results = $wpdb->get_results($sql);
	foreach($results as $row) {
		echo '<p>';
		$payment = unserialize($row->meta_value);
		foreach($payment as $index => $value)
			printf('<div>%s: %s</div>',$index,$value);
		echo '</p>';
	}
}

function stripe_balance_history () {
	require_once('stripe-php/init.php');

	$keys = get_rsvpmaker_stripe_keys ();
	$public = $keys['pk'];
	$secret = $keys['sk'];
		\Stripe\Stripe::setApiKey($secret);
	
	\Stripe\Stripe::setAppInfo(
		"WordPress RSVPMaker events management plugin",
		get_rsvpversion(),
		"https://rsvpmaker.com"
	);
//use https://stripe.com/docs/api/balance/balance_history

$history = \Stripe\BalanceTransaction::history (array('limit' => 50));
print_r($history);
}

add_action('admin_menu','rsvpmaker_stripe_report_menu',99);
function rsvpmaker_stripe_report_menu () {
if(empty(get_option('rsvpmaker_stripe_sk')))
	return;
add_submenu_page('edit.php?post_type=rsvpmaker', __("RSVPMaker Stripe Report",'rsvpmaker'), __("RSVPMaker Stripe Report",'rsvpmaker'), 'edit_rsvpmakers', "rsvpmaker_stripe_report", "rsvpmaker_stripe_report" );

//add_menu_page(__('RSVPMaker Stripe Transactions','rsvpmaker'), __('RSVPMaker Stripe Transactions','rsvpmaker'), 'manage_options','rsvpmaker_stripe_report', 'rsvpmaker_stripe_report','dashicons-products','9');
}
?>