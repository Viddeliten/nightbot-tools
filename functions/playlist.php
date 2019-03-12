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
	
	// Print the playlist
	// html_table_from_array($array, $headlines=NULL, $silent_columns=array(), $size_table=array(), $class=NULL)
	$html.=html_tag("div", html_table_from_array($current_playlist, NULL, array("_id", "providerId", "url")), "scrollbox small");
	
	// Droplist for extracting to new or existing playlist
	$html.=html_tag("h3",_("Extract current playlist"));

	$inputs=array();
	$options["NEW_PLAYLIST"]=sprintf("-- %s --", _("New playlist"));
	$playlists=playlist_get_users_playlists();
	foreach($playlists as $pl)
	{
		if($pl['name']=="")
			$pl['name']=sprintf(_("Playlist %s"), $pl['id']);
		$options[$pl['id']]=$pl['name'];
	}
	$inputs[]=html_form_droplist("move_to_droplist", _("Move all songs to"), "move_to", $options);
	$inputs[]=html_form_button("extract_current_nightbot_playlist", _("Extract"), "submit", NULL, FALSe, TRUE);
	$html.=html_form("post", $inputs, FALSE, TRUE);
	return $html;
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
	
	// TODO: list them here
	
	foreach($playlists as $playlist)
	{
		$html.=prestr($playlist);
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

function playlist_get($playlist_id, $column)
{
	$db=new db_class();
	return $db->get($column, PREFIX."playlist", $playlist_id);
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
			//First, check if the traxk is already in playlist, if so just move on!
			if(empty($db->get_from_array(PREFIX."playlist_track_reff", array("playlist"	=>	$playlist_id, "track"		=>	$id))))
			{
				$order=$db->select_first("SELECT MAX(`order`)+1 as next_order FROM ".PREFIX."playlist_track_reff WHERE playlist=".sql_safe($playlist_id));
				if(!$db->insert_from_array(PREFIX."playlist_track_reff", array("playlist"	=>	$playlist_id,
																			"track"		=>	$id,
																			"order"		=>	($order['next_order']!=NULL ? $order['next_order'] : 1)
																			)
											)
				) {
					add_error(sprintf(_("Could not insert track into playlist. %s"), $db->error));
					return FALSE;
				}
			}
			
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
?>