<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Uninstall RSVPMaker</title>
</head>

<body>
<?php
$wp_config = '../wp-config.php';
	
for($i = 0; $i < 5; $i++)
	{
	if(file_exists($wp_config) )
		{
		require($wp_config);
		break;
		}
	$wp_config = '../'.$wp_config;
	}


if(!$wpdb)
	die('Unable to load WordPress database. You may have to manually delete the database tables wp_rsvp_dates, wp_rsvp_volunteer_time, and wp_rsvpmaker (prefix may be different depending on your configuration)');

$plugins = get_option('active_plugins');
if( in_array('rsvpmaker/rsvpmaker.php',$plugins) )
	echo '<h1 style="color:red;">WARNING: You should first deactivate RSVPMaker</h1>'; 
elseif(!current_user_can("manage_options") )
	die("You must be an administrator to access this function.");
else
	{
	echo "<h1>Bidding you a fond farewell</h2>";

if($_POST)
	{
	if($_POST["tables"])
	foreach($_POST["tables"] as $slug)
		{
		echo $sql = "DROP TABLE ".$wpdb->prefix.$slug;
		$wpdb->query($sql);
		echo "<br />";
		}
	if($_POST["events"])
		{
		echo $sql = "DELETE FROM  ".$wpdb->posts." WHERE post_type='rsvpmaker' ";
		$wpdb->query($sql);
		echo "<br />";
		}
	if($_POST["options"])
		{
		delete_option('RSVPMAKER_Options');
		echo "deleting options<br />";
		}
	}
;?>
<p>This will help you clean up database tables and entries created with RSVPMaker. Note that these operations cannot be undone.</p>
<form id="form1" name="form1" method="post" action="uninstall.php">
  <input type="checkbox" name="tables[rsvpmaker]" id="rsvpmaker" value="rsvpmaker" checked="checked" />
  remove rsvpmaker table - data on people responded to events<br />
  <input type="checkbox" name="tables[rsvp_dates]" id="rsvp_dates" value="rsvp_dates" checked="checked" />
  remove event dates table<br />
  <input type="checkbox" name="tables[rsvp_volunteer_time]" id="rsvp_volunteer_time" value="rsvp_volunteer_time" checked="checked" />
  remove volunteer times table - used when people sign up for a specific timeslot<br />
  <input type="checkbox" name="events" id="events" value="1" checked="checked" />
  remove events from posts table (posts with post_type rsvpmaker)<br />
  <input type="checkbox" name="options" id="options" value="1" checked="checked" />
  remove rsvpmaker settings from the options table<br />
<input type="submit" value="Submit" />  
</form>
<?php	
	}

$results = $wpdb->get_results("SHOW TABLES",ARRAY_N);
foreach($results as $row)
	{
	if(strpos($row[0],'_rsvp') )
		$tables .= $row[0] . " ";
	}
$options = (get_option('RSVPMAKER_Options')) ? ' options set ':' NO options set';
$events = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE post_type='rsvpmaker' ");
?>
<p>Tables: <?php echo $tables;?></p>
<p>Events: <?php echo $events;?></p>
<p>Settings: <?php echo $options;?></p>
<p>See the <a href="<?php echo get_admin_url();?>plugins.php">Plugins Control Panel</a> | <a href="<?php echo get_admin_url();?>">Administratior Dashboard</a>.</p>
</body>
</html>