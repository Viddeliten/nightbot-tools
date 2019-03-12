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

?>