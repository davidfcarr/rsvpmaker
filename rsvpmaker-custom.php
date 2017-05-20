<?php
//Any of the functions in rsvpmaker-pluggable.php can be overriden from this file.
//To activate, you must place a copy of this file with your customization in your main plugins directory (wp-content/plugins/ instead of wp-content/plugins/rsvpmaker/)
//Here are some sample customizations

//I use this in conjunction with a Mailchimp integration plugin (not yet released publicly). You can insert your own function to log email addresses to a database table or the service of your choice
function capture_email($rsvp) {
if(!$_POST["onfile"] && function_exists('RSVPMaker_Chimp_Add'))
	{
		$mergevars["FNAME"] = stripslashes($rsvp["first"]);
		$mergevars["LNAME"] = stripslashes($rsvp["first"]);
		RSVPMaker_Chimp_Add($rsvp["email"],$mergevars);
	}
}

// changes the formatting of rsvp details in the rsvp report
function format_rsvp_details($results) {

	if($results)
	foreach($results as $index => $row)
		{
		$row["yesno"] = ($row["yesno"]) ? "YES" : "NO";
		
		echo '<h3>'.$row["yesno"]." ".$row["first"]." ".$row["last"]." ".$row["email"];
		if($row["guestof"])
			echo " (guest of ".$row["guestof"].")";
		echo "</h3>";
		echo "<p>";
		if($row["details"])
			{
			$details = unserialize($row["details"]);
			// custom formatting for each individual's details
			echo sprintf("<p><strong>%s %s:</strong> %s %s</p>\n",$details["first"],$details["last"],$details["email"], $details["phone"]);
			}
		if($row["note"])
			echo "note: " . nl2br($row["note"]);
		echo "</p>";
		
		echo sprintf('<p><a href="%s&delete=%d">Delete record for: %s %s</a></p>',admin_url().'edit.php?post_type=rsvpmaker&page=rsvp',$row["id"],$row["first"],$row["last"]);
		}

}

//alternate implementation, requires PEAR Mail and Mime modules to be installed on server
function rsvp_notifications ($rsvp,$rsvp_to,$subject,$message) {

include('Mail.php');
include('Mail/mime.php');
$mail =& Mail::factory('mail');

$text = $message;
$html = "<html><body>\n".wpautop($message).'</body></html>';
$crlf = "\n";

$hdrs = array(
              'From'    => '"'.$rsvp["first"]." ".$rsvp["last"].'" <'.$rsvp["email"].'>',
              'Subject' => $subject
              );

$mime = new Mail_mime($crlf);

$mime->setTXTBody($text);
$mime->setHTMLBody($html);

//do not ever try to call these lines in reverse order
$body = $mime->get();
$hdrs = $mime->headers($hdrs);

$mail->send($rsvp_to, $hdrs, $body);

// now send confirmation

$hdrs = array(
              'From'    => $rsvp_options["rsvp_to"],
              'Subject' => "Confirming RSVP $answer for ".$post->post_title." $date"
              );

$mime = new Mail_mime($crlf);

$mime->setTXTBody($text);
$mime->setHTMLBody($html);

//do not ever try to call these lines in reverse order
$body = $mime->get();
$hdrs = $mime->headers($hdrs);

$mail->send($rsvp["email"], $hdrs, $body);

}

// changes the default formatting for event links that appear in the widget
function widgetlink($evdates,$plink,$evtitle) {
	return sprintf('%s <a href="%s">%s</a> ',$evdates,$plink,$evtitle);
}

?>