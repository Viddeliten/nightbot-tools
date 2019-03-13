<?php

/*	Put all your code that should be run before page is rendered here. */

// preprint($_POST);

if(isset($_POST['merge_current']))
{
	playlist_add_to_current((int) $_POST['merge_current']);
}
if(isset($_POST['replace_current']))
{
	nightbot_clear_playlist();
	playlist_add_to_current((int) $_POST['replace_current']);
}

if(isset($_POST['move_or_copy_songs']))
{
	if($_POST['playlist']=="NEW_PLAYLIST")
	{
		$playlist_id=playlist_create_new(login_get_user());
	}
	else
		$playlist_id=$_POST['playlist'];

	foreach($_POST['tracks'] as $track_id => $value)
	{
		playlist_move_to($_REQUEST['id'], $playlist_id, $track_id, ($_POST['action']=="move" ? TRUE : FALSE));
	}
}

if(isset($_POST['playlist_update_description']))
{
	if(!playlist_set($_REQUEST['id'], "name", $_POST['name']))
		add_error(_("Name could not be saved"));
	playlist_set($_REQUEST['id'], "description", $_POST['description']);
}

if(isset($_POST['extract_current_nightbot_playlist']))
{
	playlist_extract_current_to($_POST['move_to']);	
}