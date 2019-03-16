<?php

define('BOOTSTRAP_VERSION', "4.1.0"); // Uncomment to switch to newer bootstrap (experimental)

$code="";
if(isset($_REQUEST['code']))
	$code=preg_replace("/[^a-z0-9]/", "", $_REQUEST['code']);

define('REST_APIS', serialize(array(	"nightbot"	=>	array(	"icon_url"	=>	SITE_URL."/".CUSTOM_CONTENT_PATH."/img/oauth/nightbot_icon.png",
													"base_uri"	=>	"https://api.nightbot.tv",
													"302_uri"	=>	"https://api.nightbot.tv/oauth2/authorize?response_type=code".
																	"&client_id=".NIGHTBOT_CLIENT_ID.
																	"&redirect_uri=".SITE_URL."/oauth/nightbot".
																	"&scope=song_requests_queue song_requests_playlist channel",
													"auth_uri"	=>	"https://api.nightbot.tv/oauth2/token",
													"auth_parameters"	=>	array(
																					"client_id"		=>	NIGHTBOT_CLIENT_ID,
																					"client_secret"	=>	NIGHTBOT_CLIENT_SECRET,
																					"code"			=>	$code,
																					"grant_type"	=>	"authorization_code",
																					"redirect_uri"	=>	SITE_URL."/oauth/nightbot"
																				)
												)
									)
							)
	);

/********************************/
/*		Available pages			*/
/********************************/
//Slugs need to be untranslated or coder will have hell in case of many languages in translation!
define('CUSTOM_PAGES_ARRAY',serialize(array(
	//name	=>	content
	_("My playlists")				=>	array(	"slug"				=>	"my_playlists",
													"req_user_level"	=>	1, //If 1, only logged in users can see this. If >=2 only users with this admin level can see this link.
																				//NOTE: This number only affects the visibility of the link. The contents has to be governed separately
													"meta_description"	=> "All playlists, saved and current!"
												),
	_("Users")							=>	array(	"slug"	=>	"users",
													"req_user_level"	=>	0,
													"subpages"		=>	array(
																				_("Active users")	=>	array(	"slug"	=>	"active",
																											"req_user_level"	=>	0	//This will be seen by all
																										),
																				_("Friends")	=>	array(	"slug"	=>	"friends",
																											"req_user_level"	=>	1	//This will be seen by logged in users
																										)
																			)
												)
)));

/********************************/
/*		Extra settings			*/
/********************************/
define('CUSTOM_SETTINGS',serialize(array(
	"flattr"	=>	array(	"playlists" => _("Playlists")),
	"Notifications on"	=>	array(	"comments" => _("Comments")) // If you keep this, make sure to add a message under Admin tools messages for new comments!
	// "My custom setting"	=>	array(	"some_setting" => _("Some setting you might want to have"),
									// "some_other_setting" => _("Some OTHER setting")
								// )
)));
define('NUMBER_OF_EMAIL_NOTIFY',100); //How many emails usermessage system is allowed to sent each hour

//uncomment to remove "This site is presented by" (but not the ViddeWebb logo)
// define('NotPresented',"yes");

/****************************************************/
/*		Optional properties to add to body tag		*/	
/****************************************************/
define('BODY_PROPERTIES', "");
?>