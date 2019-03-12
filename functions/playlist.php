<?php

function playlist_display_index()
{
	echo html_tag("h1",_("All your playlists"));
	echo html_tag("p",_("They are all right here!"));
	
	//First have the current playlist, expandable and stuff
	echo playlist_html_current();
	
	// Then have a list of all the users playlists saved in our db
	echo playlist_html_saved();
}

function playlist_html_current()
{
	// Get the playlist for the current logged in user
	$current_playlist=nightbot_get_playlist();
	
	$html=html_tag("h2", _("Current playlist at Nightbot"));
	
	// html_table_from_array($array, $headlines=NULL, $silent_columns=array(), $size_table=array(), $class=NULL)
	// preprint($current_playlist);
	$html.=html_tag("div", html_table_from_array($current_playlist, NULL, array("_id", "providerId", "url")), "scrollbox small");
	return $html;
}

function playlist_html_saved()
{
	$playlists=get_users_playlists();
	if(empty($playlists))
		return html_tag("p",_("No saved playlists"));
	
	$html=html_tag("h2", _("Saved playlists"));
	
	// TODO: list them here
	
	foreach($playlists as $playlist)
	{
		$html.=prestr($playlist);
	}
	
	return $html;
}

function get_users_playlists($user_id=NULL)
{
	if($user_id==NULL)
		$user_id=login_get_user();
	
	if($user_id==NULL)
		return FALSE;
	
	$db=new db_class();
	
	return $db->get_from_array(PREFIX."playlist", array("user" => $user_id));
}
?>