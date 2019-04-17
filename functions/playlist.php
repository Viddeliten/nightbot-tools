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

function playlist_display_single($playlist_id, $editmode=FALSE)
{
	$playlist=playlist_get($playlist_id);
	echo playlist_html_single($playlist, ($editmode ? "edit" : "single"));
}

function playlist_html_current()
{
	// Get the playlist for the current logged in user
	$current_playlist=nightbot_get_playlist();
	
	$html=html_tag("h2", _("Current playlist at Nightbot"));
	
	// Print the playlist
	// html_table_from_array($array, $headlines=NULL, $silent_columns=array(), $size_table=array(), $class=NULL)
	$html.=html_tag("div", html_table_from_array($current_playlist, NULL, array("_id", "providerId", "url")), "scrollbox small");
	
	// Textarea to add songs to current playlist
	$html.=html_tag("h3",_("Add songs"));
	$html.=html_tag("p",_("Enter urls or song names, separated by line breaks."));

	$inputs=array();
	$inputs[]=html_form_textarea("songs_for_current_playlist_textarea", _("Songs"), "songs_for_current_playlist", "", NULL);
	$inputs[]=html_form_button("add_songs_to_current", _("Add"), "submit", NULL, FALSe, TRUE);
	$html.=html_form("post", $inputs, FALSE, TRUE);

	// Droplist for extracting to new or existing playlist
	$html.=html_tag("h3",_("Extract current playlist"));

	$inputs=array();
	$inputs[]=playlist_droplist("move_to", _("Move all songs to"));
	$inputs[]=html_form_button("extract_current_nightbot_playlist", _("Extract"), "submit", NULL, FALSe, TRUE);
	$html.=html_form("post", $inputs, FALSE, TRUE);
	return $html;
}

function playlist_droplist($input_name, $input_label)
{
	$inputs=array();
	$options["NEW_PLAYLIST"]=sprintf("-- %s --", _("New playlist"));
	$playlists=playlist_get_users_playlists();
	foreach($playlists as $pl)
	{
		if($pl['name']=="")
			$pl['name']=sprintf(_("Playlist %s"), $pl['id']);
		$options[$pl['id']]=$pl['name'];
	}
	return html_form_droplist($input_name."_droplist", $input_label, $input_name, $options);
}


function playlist_html_saved()
{
	$html=html_tag("h2", _("Saved playlists"));

	$playlists=playlist_get_users_playlists();
	if(empty($playlists))
	{
		$html.=html_tag("p",_("No saved playlists"));
		return $html;
	}
	
	// List all saved playlists
	foreach($playlists as $playlist)
	{
		$html.=playlist_html_single($playlist, "small");
	}
	
	return $html;
}

function playlist_html_single($playlist, $mode)
{
	$tracks=track_get_all_from_playlist($playlist['id'], ($mode=="edit" ? TRUE : FALSE));
	$playlist_name=($playlist['name']!=NULL ? html_safe($playlist['name']) : sprintf(_("Playlist %s"), $playlist['id']));
	$move_songs="";

	switch($mode)
	{
		case "small":
			$html=html_tag("h3",	$playlist_name);
			$buttons[0]=html_action_button(SITE_URL."/playlist/edit/".$playlist['id'], _("Edit"));
			$buttons[1]=html_action_button(SITE_URL."/my_playlists", _("Replace current with this"), array("replace_current"	=> $playlist['id']), "warning");
			$buttons[2]=html_action_button(SITE_URL."/my_playlists", _("Add this to current"), array("merge_current"	=> $playlist['id']), "success");
			$html.=html_row(1, 3, $buttons);
			$html.=html_tag("div", $move_songs.html_table_from_array($tracks, NULL, array("id", "providerId", "url")), "scrollbox small");
			break;
		case "edit":
			$html=html_tag("h3", sprintf(_("Editing %s"), $playlist_name));
			$html.=html_form("post", array(	html_form_input("name_text", _("Name"), "text", "name", $playlist_name),
											html_form_textarea("description_textarea", _("Description"), "description", $playlist['description'], _("Optional description")),
											html_form_button("playlist_update_description", _("Save"), "success", NULL, FALSE, TRUE)));

			$inputs=array();
			$inputs[]=html_form_radio(_("With selected"), "action_radio", "action", array("move" => _("Move to"), "copy" => _("Copy to")));
			$inputs[]=playlist_droplist("playlist", _("Playlist"));
			$inputs[]=html_form_button("move_or_copy_songs", _("Do"), "submit", NULL, FALSe, FALSe);
			$form_contents=html_row(1, count($inputs), $inputs).
						html_tag("div", html_table_from_array($tracks, NULL, array("id", "providerId", "url")), "small");
			$html.=html_form("post", array($form_contents), FALSE, TRUE);
			break;
		default:
			$html=html_tag("h1", $playlist_name);
			$html.=html_tag("div", $move_songs.html_table_from_array($tracks, NULL, array("id", "providerId", "url")), "small");
			break;
	}	
	
	return $html;
}


function playlist_get_users_playlists($user_id=NULL)
{
	if($user_id==NULL)
		$user_id=login_get_user();
	
	if($user_id==NULL)
		return FALSE;
	
	$db=new db_class();
	
	return $db->get_from_array(PREFIX."playlist", array("user" => $user_id));
}

function playlist_get($playlist_id, $column="*")
{
	$db=new db_class();
	if($column=="*")
		return $db->get_from_array(PREFIX."playlist", array("id" => $playlist_id), TRUE);
	return $db->get($column, PREFIX."playlist", $playlist_id);
}
function playlist_set($playlist_id, $column, $new_value)
{
	$db=new db_class();
	if(login_get_user()==playlist_get($playlist_id, "user"))
		return $db->set(PREFIX."playlist", $column, $new_value, $playlist_id);
}

function playlist_create_new($user_id)
{
	$db=new db_class();
	$values=array(	"created"	=> "NOW()",
					"user"		=>	$user_id);
	if($db->insert_from_array(PREFIX."playlist", $values))
		return $db->insert_id;

	add_error(sprintf(_("Could not create playlist. Error: %s"), $db->error));
	return FALSE;
}

function playlist_move_to($from_playlist_id, $to_playlist_id, $track_id, $remove_from_old=TRUE)
{
	$user_id=login_get_user();
	if($user_id==playlist_get($from_playlist_id,"user") && $user_id==playlist_get($to_playlist_id,"user"))
	{
		// Add track to new playlist
		playlist_insert_track($to_playlist_id, $track_id);
		
		// Remove track from old playlist
		if($remove_from_old)
		{
			playlist_remove_track($from_playlist_id, $track_id);
		}
	}
}

function playlist_add_to_current($playlist_id)
{
	$user_id=login_get_user();
	// $db=new db_class(); //Does not seem to be needed here
	$api=new rest_api_integration("nightbot", TRUE);
	
	if(!$playlist_id)
	{
		add_error(_("No playlist"));
		return FALSE;	
	}
	
	//Check that current logged in user ownss this playlist
	if($user_id!=playlist_get($playlist_id, "user"))
	{
		add_error(_("User mismatch"));
		return FALSE;	
	}
	
	//Get all tracks from playlist
	$tracks=track_get_all_from_playlist($playlist_id);
	if(!empty($tracks))
	{
		foreach($tracks as $track)
		{
			track_add_to_current_playlist($track['url'], $track['title']);
		}
	}
}

function playlist_add_songs_to_current_from_text($text)
{
	$text=str_replace("\r","\n",$text);
	$text=str_replace("\n\n","\n",$text);
	
	$songs=explode("\n", $text);
	
	foreach($songs as $song)
	{
		if($song!="")
			track_add_to_current_playlist($song);
	}
}

function playlist_extract_current_to($receiving_playlist_id)
{
	$user_id=login_get_user();
	$db=new db_class();
	$api=new rest_api_integration("nightbot", TRUE);
	
	if($receiving_playlist_id=="NEW_PLAYLIST")
	{
		$playlist_id=playlist_create_new($user_id);
	}
	else
		$playlist_id=$receiving_playlist_id;
	if(!$playlist_id)
	{
		add_error(_("No playlist"));
		return FALSE;	
	}
	
	//Check that current logged in user ownss this playlist
	if($user_id!=playlist_get($playlist_id, "user"))
	{
		add_error(_("User mismatch"));
		return FALSE;	
	}
	
	//Get all items from current playlist and for each, add to the playlist (unless the track is already in there) and then remove the item from the current playlist
	$current_playlist=nightbot_get_playlist($user_id);
	while(!empty($current_playlist))
	{
		foreach($current_playlist as $track)
		{
			//Check if url exists in track db already. If not, insert it.
			$id=track_insert($track);
			if(!$id)
				return FALSE;
			
			//make a connection to the playlist
			if(!playlist_insert_track($playlist_id, $id))
				return FALSE;
			
			//Remove the item from current playlist
			$result=$api->_delete(array("1","song_requests","playlist",$track['_id']));
			if(200!=$result->status)
			{
				add_error(sprintf(_("Track %s could not be removed from current playlist"), $track['title']));
			}
		}
		$current_playlist=nightbot_get_playlist($user_id);
	}
}

function playlist_remove_track($playlist_id, $track_id)
{
	$db=new db_class();
	$db->delete_from_array(PREFIX."playlist_track_reff", array("playlist"	=>	$playlist_id,
																	"track"		=>	$track_id)
							);
}

function playlist_insert_track($playlist_id, $track_id)
{
	$db=new db_class();
	//First, check if the traxk is already in playlist, if so just move on!
	if(empty($db->get_from_array(PREFIX."playlist_track_reff", array("playlist"	=>	$playlist_id, "track"		=>	$track_id))))
	{
		$order=$db->select_first("SELECT MAX(`order`)+1 as next_order FROM ".PREFIX."playlist_track_reff WHERE playlist=".sql_safe($playlist_id));
		if(!$db->insert_from_array(PREFIX."playlist_track_reff", array("playlist"	=>	$playlist_id,
																	"track"		=>	$track_id,
																	"order"		=>	($order['next_order']!=NULL ? $order['next_order'] : 1)
																	)
									)
		) {
			add_error(sprintf(_("Could not insert track into playlist. %s"), $db->error));
			return FALSE;
		}
	}
	return TRUE;
}
?>