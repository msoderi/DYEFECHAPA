<?php

date_default_timezone_set("Europe/Rome");

require 'tmhOAuth.php';

function post_tweet($text)
{
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	$twitter = http_parse_message(http_head("https://api.twitter.com/1/help/test.json ",array(),$info));
	$twitterDatetime = strtotime($twitter->headers["Date"]);

	$tmhOAuth = new tmhOAuth(array(
	  'consumer_key'    => $consumer_key,
	  'consumer_secret' => $consumer_secret,
	  'user_token'      => $user_token,
	  'user_secret'     => $user_secret,
	  'force_timestamp' => true,
	  'timestamp'	    => $twitterDatetime
	));

	$code = $tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), array(
	  'status' => $text
	));

	return $code;

}

?>
