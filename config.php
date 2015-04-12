<?php

// Biz parameters
// ----------------------------

// relative path from newsd.php to temp text file containing URLs of channels to be fetched
$tmpSrcTxtFile = "tmp/cchannels"; 

// relative path from newsd.php to the file that, if found, stops the deamon without fetching subsequent URLs
$stop_newsd_now_cmdfile = "cmd/stop_newsd_now";

// relative path from newsd.php to the file that, if found, stops the daemon when all channels have been fetched. Otherwise, the deamon restarts from the first channel in the list.
$stop_newsd_cmdfile = "cmd/stop_newsd";

// relative path from newsd.php to temp xml files containing currently fetched channel content
$tmpCurrUnzippedXmlFile = "tmp/uncompressed.xml";
$tmpCurrXmlFile = "tmp/channel.xml";
$tmpCurrOriginalXmlFile = "tmp/original_channel.xml";
$tmpPartFolder = "tmp";

// src db conn data

$dbhost = "";
$dbuser = "";
$dbpass = "";
$dbname = "";

//db tables
$dbtable__twitter_twitted = "";
$dbtable__admin_news = "";
$dbtable__twitter_shorturl = "";
$dbtable__admin_channels = "";
$dbtable__stats_visualizzazioni = "";

// db fields
$dbfield__newsid = "";
$dbfield__id = "";
$dbfield__admin_news_channel = "";
$dbfield__admin_channels_channel = "";
$dbfield__content = "";
$dbfield__news = "";
$dbfield__skip = "";
$dbfield__stats_visualizzazioni_newsid = "";
$dbfield__twitter_shorturl_id = "";
$dbfield__stats_visualizzazioni_newstitle = "";
$dbfield__admin_channels_referer = "";

// website url
$websiteUrl = "";

// log
$newsLogFolder = "log";

// Facebook

$fbposting_enabled = false;
$appid = "";
$appsecret = "";
$pageAccessToken = "";
$fb_api_post_path = "";

// Twitter

$tweets_enabled = false;
$consumer_key = "";
$consumer_secret = "";
$user_token = "";
$user_secret = "";

global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;
    
?>
