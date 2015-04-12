<?php

require_once 'facebook.php';

function post_facebook_link($message, $link) 
{
	
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	$facebook = new Facebook(array(
	    'appId'  => $appid,
	    'secret' => $appsecret,
	    'cookie' => false,
	));

	try 
	{

		$attachment = array
		(
			'message' => $message,
			'link' => $link,
			'access_token' => $pageAccessToken
		);

		$result = $facebook->api($fb_api_post_path, 'post', $attachment);

	}
	catch (FacebookApiException $e) 
	{

		print_r($e);

	}
}

?>
