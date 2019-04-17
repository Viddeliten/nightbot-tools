<?php

define("TRACK_TABLE", PREFIX."track");

function track_insert($array)
{
	//If track already exist, we just return id of that
	$id=track_get_id_from_url($array['url']);
	if($id)
		return $id;

	$db=new db_class();
	$columns=$db->get_columns(TRACK_TABLE);
	$values=array();
	foreach($columns as $key)
	{
		if(!strcmp($key, "url"))
			$values[$key]=strtolower($array[$key]);
		else if(!in_array($key, array("id", "updated")) && isset($array[$key]))
			$values[$key]=$array[$key];
	}
	if(!empty($values))
	{
		if($db->insert_from_array(TRACK_TABLE, $values))
			return $db->insert_id;
		add_error(sprintf(_("Could not insert track. %s"), $db->error));
	}
	return FALSE;
}

function track_add_to_current_playlist($url, $title=NULL)
{
	$api=new rest_api_integration("nightbot", TRUE);

	$result=$api->post(array("1","song_requests","playlist"), array("q" => $url));
	if(200!=$result->status)
	{
		add_error(sprintf(_("Track %s (%s) could not be added to current playlist. %s"), $title, $url, $result->message));
	}
}

function track_get($track_id, $column)
{
	$db=new db_class();
	return $db->get($column, PREFIX."track", $track_id);
}

function track_get_id_from_url($url)
{
	$db=new db_class();
	$track=$db->get_from_array(TRACK_TABLE, array("url" => strtolower($url)), TRUE);
	if(!empty($track))
		return $track['id'];
	return FALSE;
}
function track_get_all_from_playlist($playlist_id, $checkboxes=FALSE)
{
	$db=new db_class();
	$result = $db->select("SELECT track.* 
		FROM ".PREFIX."playlist_track_reff reff
		INNER JOIN ".TRACK_TABLE." ON ".TRACK_TABLE.".id=reff.track
		WHERE reff.playlist=".sql_safe($playlist_id).";");
	$return=array();
	foreach($result as $r)
	{
		$temp=array();
		if($checkboxes)
		{
			$temp["select"]=html_form_checkbox(NULL, "track_".$r['id']."_checkbox", "tracks[".$r['id']."]", NULL, FALSE, NULL, FALSE);
		}
		
		foreach($r as $key => $val)
		{
			if(!strcmp($key,"title"))
				$temp[$key]=html_link($r['url'], $val, NULL, "_blank");
			else
				$temp[$key]=$val;
		}

		$initial_parameters=array(	"play"		=>	0);
		$switch_parameters=array(	"play"		=>	1,
									"track_id"	=>	$r['id']);
		$temp['play']=html_ajax_div_switcher($playlist_id."_play_track_".$r['id'], "track_display_play_frame", $initial_parameters, $switch_parameters, "play_div", TRUE);

		$return[]=$temp;
	}
	return $return;
}

function track_display_play_frame($play, $track_id=NULL)
{
	if(!$play)
		return "[Play]";
	
	$r=track_get($track_id, NULL);
	
	if($r['provider']=="soundcloud")
	{
		return '<iframe width="120" height="50" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url='.$r['url'].'&color=%23ff5500&auto_play=true&hide_related=false&show_comments=false&show_user=false&show_reposts=false&show_teaser=false&visual=false"></iframe>';
	}
	else if($r['provider']=="youtube")
	{
		$url_parts=explode("/", $r['url']);
		$url="https://www.youtube-nocookie.com/embed/".str_replace("watch?v=","", $url_parts[count($url_parts)-1]);
		// https://www.youtube-nocookie.com/embed/
		return '<iframe width="90" height="50" src="'.$url.'?autoplay=1" frameborder="0" allow="accelerometer; encrypted-media; gyroscope"></iframe>';
	}
}

?>