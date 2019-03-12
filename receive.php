<?php

/*	Put all your code that should be run before page is rendered here. */

if(isset($_POST['extract_current_nightbot_playlist']))
{
	playlist_extract_current_to($_POST['move_to']);	
}