<?php
/*
	register_block_type('rsvpmaker/formfield', ['render_callback' => 'rsvp_form_text']);	
	register_block_type('rsvpmaker/formtextarea', ['render_callback' => 'rsvp_form_textarea']);	
	register_block_type('rsvpmaker/formselect', ['render_callback' => 'rsvp_form_select']);	
	register_block_type('rsvpmaker/formradio', ['render_callback' => 'rsvp_form_radio']);	
	register_block_type('rsvpmaker/guests', ['render_callback' => 'rsvp_form_guests']);	
*/

function upgrade_rsvpform ($future = true) {
global $rsvp_options;

$form = '<!-- wp:rsvpmaker/formfield {"label":"'.__('First Name','rsvpmaker').'","slug":"first","sluglocked":true,"required":"required","guestform":true} -->
<div class="wp-block-rsvpmaker-formfield"><p><label>'.__('First Name','rsvpmaker').':</label> <span class="required"><input class="first" type="text" name="profile[first]" id="first" value=""/></span></p></div>
<!-- /wp:rsvpmaker/formfield -->

<!-- wp:rsvpmaker/formfield {"label":"'.__('Last Name','rsvpmaker').'","slug":"last","guestform":true,"sluglocked":true,"required":"required"} -->
<div class="wp-block-rsvpmaker-formfield"><p><label>'.__('Last Name','rsvpmaker').':</label> <span class="required"><input class="last" type="text" name="profile[last]" id="last" value=""/></span></p></div>
<!-- /wp:rsvpmaker/formfield -->

<!-- wp:rsvpmaker/formfield {"label":"'.__('Email','rsvpmaker').'","slug":"email","sluglocked":true,"required":"required"} -->
<div class="wp-block-rsvpmaker-formfield"><p><label>'.__('Email','rsvpmaker').':</label> <span class="required"><input class="email" type="email" name="profile[email]" id="email" value=""/></span></p></div>
<!-- /wp:rsvpmaker/formfield -->

<!-- wp:rsvpmaker/formfield {"label":"'.__('Phone','rsvpmaker').'","slug":"phone"} -->
<div class="wp-block-rsvpmaker-formfield"><p><label>'.__('Phone','rsvpmaker').':</label> <span class=""><input class="phone" type="text" name="profile[phone]" id="phone" value=""/></span></p></div>
<!-- /wp:rsvpmaker/formfield -->

<!-- wp:rsvpmaker/formselect {"label":"Phone Type","slug":"phone_type","choicearray":["Mobile Phone","Home Phone","Work Phone"]} -->
<div class="wp-block-rsvpmaker-formselect"><p><label>Phone Type:</label> <span><select class="phone_type" name="profile[phone_type]" id="phone_type"><option value="Mobile Phone">Mobile Phone</option><option value="Home Phone">Home Phone</option><option value="Work Phone">Work Phone</option></select></span></p></div>
<!-- /wp:rsvpmaker/formselect -->

<!-- wp:rsvpmaker/guests -->
<div class="wp-block-rsvpmaker-guests"><!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph --></div>
<!-- /wp:rsvpmaker/guests -->

<!-- wp:paragraph -->
<p>'.__('Note','rsvpmaker').':<br><textarea name="note"></textarea></p>
<!-- /wp:paragraph -->';

$rsvp_options['rsvp_form'] = wp_insert_post(array('post_title'=>'RSVP Form:Default','post_content'=>$form,'post_status'=>'publish','post_type'=>'rsvpmaker','post_parent' => 0));
update_option('RSVPMAKER_Options',$rsvp_options);
update_post_meta($rsvp_options['rsvp_form'],'_rsvpmaker_special','RSVP Form');

if($future)
{
	$results = get_future_events();
	if($results)
	foreach($results as $post)
	{
		update_post_meta($post->ID,'_rsvp_form',$rsvp_options['rsvp_form']);
	}
}
return $rsvp_options['rsvp_form'];
}


function customize_rsvp_form () {
	
if(current_user_can('manage_options') && isset($_GET['upgrade_rsvpform'])) {
	$id = upgrade_rsvpform();
}	
	
if(isset($_GET['customize_rsvpconfirm'])) {
	$source = (int) $_GET['customize_rsvpconfirm'];
	$parent = (int) $_GET['post_id'];
	$old = get_post($source);
	if($old)
	{
	$new["post_title"] = "Confirmation:".$parent;
	$new["post_parent"] = $parent;
	$new["post_status"] = 'publish';
	$new["post_type"] = 'rsvpemail';
	$new["post_content"] = $old->post_content;
	$id = wp_insert_post($new);
	if($id)
		update_post_meta($parent,'_rsvp_confirm',$id);		
	}
}
if(isset($_GET['customize_form'])) {
	$source = (int) $_GET['customize_form'];
	$old = get_post($source);
	$parent = (int) $_GET['post_id'];
	if($old)
	{
	$new["post_title"] = "RSVP Form:".$parent;
	$new["post_parent"] = $parent;
	$new["post_status"] = 'publish';
	$new["post_type"] = 'rsvpmaker';
	$new["post_content"] = $old->post_content;
	//print_r($new);
	$id = wp_insert_post($new);
	//printf('<p>Insert post returned %s',$id);
	if($id)
		{
			update_post_meta($parent,'_rsvp_form',$id);
			update_post_meta($id,'_rsvpmaker_special','RSVP Form');
		}
	}
}

if(!empty($id)) {
	header('Location: '.admin_url('post.php?action=edit&post=').$id);
	exit();
}
	
}

function rsvp_field_apply_default($content,$slug,$default) {
	if(strpos($content,'type="text"') || strpos($content,'type="email"'))
		$content = str_replace('value=""','value="'.$default.'"',$content);
	elseif(strpos($content,'</textarea>'))
		$content = str_replace('</textarea>',$default.'</textarea>',$content);
	$find = 'value="'.$default.'"';
	if(strpos($content,'</select>'))
		$content = str_replace($find,$find.' selected="selected"',$content);
	elseif(strpos($content,'type="radio"'))
		$content = str_replace($find,$find.' checked="checked"',$content);
	return $content;
}

function rsvp_form_field($atts, $content) {
	global $post;
	global $rsvp_required_field;
	if(empty($atts["slug"]) || empty($atts["label"]))
		return;
	$slug = $atts["slug"];
	$label = $atts["label"];
	
	$output = '';
if(isset($atts["required"]) || isset($atts["require"]))
	{
		$output = '<span class="required">'.$output.'</span>';
		$rsvp_required_field[$slug] = $slug;
	}	

	update_post_meta($post->ID,'rsvpform'.$slug,$label);
	global $profile;
	//$profile = array('first' => 'David','last' => 'Carr','meal'=>'Chicken','dessert'=>'pie','email'=>'david@carrcommunications.com');
	global $guestprofile;
	if(!empty($guestprofile))
		$profile = $guestprofile;
	if(!empty($atts['guestform'])) // if not set, default is true
		rsvp_add_guest_field($content,$slug);
	if(empty($profile[$slug]))
		return $content;//.$slug.': no default'.var_export($profile,true);
	$default = $profile[$slug];
	return rsvp_field_apply_default($content,$slug,$default);
}

function rsvp_guest_content($content) {
	$content = str_replace(']"','][]"',$content);
	$content = str_replace('"profile','"guest',$content);
	$content = preg_replace('/id="[^"]+"/','',$content);//no ids on guest fields
	$content = str_replace('class="required"','',$content);//no required fields
	return $content;
}

function rsvp_add_guest_field($content,$slug) {
	global $guestfields;
	$guestfields[$slug] = rsvp_guest_content($content);
}

function rsvp_form_guests($atts, $content) {
if(is_admin())
	return $content;
$content = '';//ignore content
global $guestfields;
global $guestprofile;
$shared = '';
$label = (isset($atts['label'])) ? $atts['label'] : __('Guest','rsvpmaker');

if(!empty($guestfields))
	foreach($guestfields as $slug => $field)
		$shared .= $field;
$template = '<div class="guest_blank" id="first_blank"><p><strong>Guest ###</strong></p>'.$shared . $content.'</div>';//fields shared from master form, plus added fields
	
$addmore = (isset($atts['addmore'])) ? $atts['addmore'] : __('Add more guests','rsvpmaker');
global $wpdb;
global $blanks_allowed;
global $master_rsvp;
//$master_rsvp = 4;//test data
$wpdb->show_errors();
$output = '';
$count = 1; // reserve 0 for host
$max_party = (isset($atts["max_party"])) ? (int) $atts["max_party"] : 0;

if(isset($master_rsvp) && $master_rsvp)
{
$guestsql = "SELECT * FROM ".$wpdb->prefix."rsvpmaker WHERE master_rsvp=".$master_rsvp.' ORDER BY id';
if($results = $wpdb->get_results($guestsql, ARRAY_A) )
	{
	foreach($results as $row)
		{
			$output .= sprintf('<div class="guest_blank"><p><strong>%s %d</strong></p>',$label,$count)."\n";
			$guestprofile = rsvp_row_to_profile($row);
			$shared = '';
			if(!empty($guestfields))
				foreach($guestfields as $slug => $field)
				{
					if(!empty($guestprofile[$slug]))
						$shared .= rsvp_field_apply_default($field,$slug,$guestprofile[$slug]);
					else
						$shared .= $field;
				}
		
			$output .= $shared.do_blocks($content);
			$output = str_replace('[]','['.$count.']',$output);
			$output .= sprintf('<div><input type="checkbox" name="guestdelete[%s]" value="%s" /> '.__('Delete Guest','rsvpmaker').' %d</div><input type="hidden" name="guest[id][%s]" value="%s">',$row["id"],$row["id"], $count,$count,$row["id"]);
			$count++;
		}
	}
}

$output .= $template;
//$output .= '<script type="text/javascript"> var guestcount ='.$count.'; </script>';

$max_guests = $blanks_allowed + $count;

if($max_party)
	$max_guests = ($max_party > $max_guests) ? $max_guests : $max_party; // use the lower limit

// now the blank field
if($blanks_allowed < 1)
	return $output.'<p><em>'.__('No room for additional guests','rsvpmaker').'</em><p>'; // if event is full, no additional guests
elseif($count > $max_guests)
	return $output.'<p><em>'.__('No room for additional guests','rsvpmaker').'</em><p>'; // limit by # of guests per person
elseif($max_guests && ($count >= $max_guests))
	return $output.'<p><em>'.__('No room for additional guests (max per party)','rsvpmaker').'</em><p>'; // limit by # of guests per person

$output = '<div id="guest_section" tabindex="-1">'."\n".$output.'</div>'."<!-- end of guest section-->";
if($max_guests > ($count + 1))
	$output .= "<p><a href=\"#guest_section\" id=\"add_guests\" name=\"add_guests\">(+) ".$addmore."</a><!-- end of guest section--></p>\n";

$output .= '<script type="text/javascript"> var guestcount ='.$count.'; </script>';

return $output;
}

?>