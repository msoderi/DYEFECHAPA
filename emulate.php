<?php

date_default_timezone_set("Europe/Rome");


function emulateFirstInsertAndListlast($newsid, $newsdoc)
{

global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	try
	{
	
	$listlastNodesArray = array( $newsid => $newsdoc );
	$listlastNodesArrayKeys = array_keys($listlastNodesArray); 
	
	$listlast = array();
	for($i = 0; $i < count($listlastNodesArrayKeys); $i++)
	{
		
		$node = null;         	
		
		$node = $listlastNodesArray[$listlastNodesArrayKeys[$i]];
		if($node == null) return false;	
		$node->registerXPathNamespace("default","http://www.w3.org/2005/Atom");
		
		$id = "";
		$datetime = "";
		$newstype = "";
		$title = "";
		$description = "";
		$link = "";
		$attachments = array();

		if(null != $node->rss[0])
		{

	                $id = $node->id[0]->__toString();
	
        	        $datetime = $node->datetime[0]->__toString();

                	$newstype = $node->type[0]->__toString();
			if(null != $node->rss[0]->channel[0]->item[0]->title[0] && trim($node->rss[0]->channel[0]->item[0]->title[0]->__toString()) != "") $title = $node->rss[0]->channel[0]->item[0]->title[0]->__toString(); 

			if(null != $node->rss[0]->channel[0]->item[0]->description[0]  && trim($node->rss[0]->channel[0]->item[0]->description[0]->__toString()) != "") $description = $node->rss[0]->channel[0]->item[0]->description[0]->__toString();
 			if(strlen(mb_strcut($description,0,100000)) < strlen(mb_strcut($description,0,150000))) return false;
			if(trim(str_replace(chr(194).chr(160)," ",$description)) == "")
			{
				if(null != $node->rss[0]->channel[0]->item[0]->contentencoded[0]  && trim($node->rss[0]->channel[0]->item[0]->contentencoded[0]->__toString()) != "") $description = trim($node->rss[0]->channel[0]->item[0]->contentencoded[0]->__toString());
				if(trim(str_replace(chr(194).chr(160)," ",$description)) == "") $description = "";
			}

			if(null != $node->rss[0]->channel[0]->item[0]->link[0]  && trim($node->rss[0]->channel[0]->item[0]->link[0]->__toString()) != "") $link = $node->rss[0]->channel[0]->item[0]->link[0]->__toString();

			for($j = 0; $j < count($node->rss[0]->channel[0]->item[0]->mediacontent); $j++)
			{
				
				$attachment_title = "";
				if(null != $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediatitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediatitle[0]->__toString()))
				{
					$attachment_title = $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediatitle[0]->__toString();
				}
				else if(null != $node->rss[0]->channel[0]->item[0]->mediatitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediatitle[0]->__toString()))
				{
					$attachment_title = $node->rss[0]->channel[0]->item[0]->mediatitle[0]->__toString();
				}
				else if( null != $node->rss[0]->channel[0]->mediatitle[0] && trim($node->rss[0]->channel[0]->mediatitle[0]->__toString()))
				{
					 $attachment_title = $node->rss[0]->channel[0]->mediatitle[0]->__toString();
				}
				else if(null != $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediasubTitle[0]->__toString();
                                } 
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediasubTitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->mediasubTitle[0]->__toString();
                                }
				else if(null != $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediadescription[0] && trim($node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediacontent[$j]->mediadescription[0]->__toString();
                                } 
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediadescription[0] && trim($node->rss[0]->channel[0]->item[0]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediadescription[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->mediadescription[0] && trim($node->rss[0]->channel[0]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->mediadescription[0]->__toString();
                                }

				$attachment_url = "";
				if(null != $node->rss[0]->channel[0]->item[0]->mediacontent[$j]["url"]  && trim($node->rss[0]->channel[0]->item[0]->mediacontent[$j]["url"]) != "") $attachment_url = trim($node->rss[0]->channel[0]->item[0]->mediacontent[$j]["url"]);
				if(trim($attachment_url) == "") continue;

				if(trim($attachment_title) == "") $attachment_title = $attachment_url;

				$alreadyThere = false;
				for($a = 0; $a < count($attachments); $a++) if(trim($attachments[$a][1]) == trim($attachment_url)) $alreadyThere = true;

				if($alreadyThere) continue;
 
				$attachments[] = array
				(
					$attachment_title,
					$attachment_url
				);

			}

			for($g = 0; $g < count($node->rss[0]->channel[0]->item[0]->mediagroup); $g++)
                        for($j = 0; $j < count($node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent); $j++)
                        {

                                $attachment_title = "";
                                if(null != $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediatitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediatitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediatitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediatitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediatitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediatitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->mediatitle[0] && trim($node->rss[0]->channel[0]->mediatitle[0]->__toString()))
                                {
                                         $attachment_title = $node->rss[0]->channel[0]->mediatitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediasubTitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->item[0]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediasubTitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->mediasubTitle[0] && trim($node->rss[0]->channel[0]->mediasubTitle[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->mediasubTitle[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediadescription[0] && trim($node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]->mediadescription[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->item[0]->mediadescription[0] && trim($node->rss[0]->channel[0]->item[0]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->item[0]->mediadescription[0]->__toString();
                                }
                                else if(null != $node->rss[0]->channel[0]->mediadescription[0] && trim($node->rss[0]->channel[0]->mediadescription[0]->__toString()))
                                {
                                        $attachment_title = $node->rss[0]->channel[0]->mediadescription[0]->__toString();
                                }

                                $attachment_url = "";
                                if(null != $node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]["url"]) $attachment_url = trim($node->rss[0]->channel[0]->item[0]->mediagroup[$g]->mediacontent[$j]["url"]);
                                if(trim($attachment_url) == "") continue;

                                if(trim($attachment_title) == "") $attachment_title = $attachment_url;

				$alreadyThere = false;
				for($a = 0; $a < count($attachments); $a++) if(trim($attachments[$a][1]) == trim($attachment_url)) $alreadyThere = true;

				if($alreadyThere) continue;

                                $attachments[] = array
                                (
                                        $attachment_title,
                                        $attachment_url
                                );
                        }

			for($j = 0; $j < count($node->rss[0]->channel[0]->item[0]->enclosure); $j++)
			{
				if(trim($node->rss[0]->channel[0]->item[0]->enclosure[$j]["url"]))
				{
					
					$alreadyThere = false;
					for($a = 0; $a < count($attachments); $a++) if(trim($node->rss[0]->channel[0]->item[0]->enclosure[$j]["url"]) == trim($attachments[$a][1])) $alreadyThere = true;
					if($alreadyThere) continue;
					$attachments[] = array
					(
						$node->rss[0]->channel[0]->item[0]->enclosure[$j]["url"],
						$node->rss[0]->channel[0]->item[0]->enclosure[$j]["url"]
					);
				}	
			}

			if(1 == count($attachments) && ( $attachments[0][0] == $attachments[0][1] || trim($attachments[0][0]) == "") && trim($title) != "")
			{
				$attachments[0][0] = $title;
			}

		}
		else if(null != $node->RDF[0]) 
		{ 

			$id = $node->id[0]->__toString();

                        $datetime = $node->datetime[0]->__toString();

                        $newstype = $node->type[0]->__toString();

			if($node->RDF[0]->item[0]->title[0]) $title = $node->RDF[0]->item[0]->title[0]->__toString();

			if($node->RDF[0]->item[0]->description[0]) $description = $node->RDF[0]->item[0]->description[0]->__toString();

			if(trim(str_replace(chr(194).chr(160)," ",$description)) == "") $description = "";

			if($node->RDF[0]->item[0]->link[0]) $link = $node->RDF[0]->item[0]->link[0]->__toString();

			for($j = 0; $j < count($node->RDF[0]->item[0]->encenclosure); $j++)
			{
				$attachment_url = "";
				if($node->RDF[0]->item[0]->encenclosure[$j]["encurl"] && trim($node->RDF[0]->item[0]->encenclosure[$j]["encurl"]) != "")
				{
					$attachment_url = trim($node->RDF[0]->item[0]->encenclosure[$j]["encurl"]);
				}
				else if($node->RDF[0]->item[0]->encenclosure[$j]["rdfresource"] && trim($node->RDF[0]->item[0]->encenclosure[$j]["rdfresource"]) != "")
				{
					$attachment_url = trim($node->RDF[0]->item[0]->encenclosure[$j]["rdfresource"]);
				} 
				else
				{
					continue;
				}

				if(trim($attachment_url) == "") continue;
			
				$alreadyThere = false;
				for($a = 0; $a < count($attachments); $a++) if(trim($attachments[$a][1]) == trim($attachment_url)) $alreadyThere = true;
				if($alreadyThere) continue;

				$attachments[] = array
				(
					$attachment_url,
					$attachment_url
				);
			}

		}
		else if(null != $node->children("http://www.w3.org/2005/Atom")->feed) 
		{ 
			
			$id = $node->id[0]->__toString();

                        $datetime = $node->datetime[0]->__toString();

                        $newstype = $node->type[0]->__toString();
			
			if($node->children("http://www.w3.org/2005/Atom")->feed->entry->title) $title = $node->children("http://www.w3.org/2005/Atom")->feed->entry->title->__toString();

                  	if($node->children("http://www.w3.org/2005/Atom")->feed->entry->summary) $description = $node->children("http://www.w3.org/2005/Atom")->feed->entry->summary->__toString();

                        if(strlen(mb_strcut($description,0,100000)) < strlen(mb_strcut($description,0,150000))) return false;

                        if(trim(str_replace(chr(194).chr(160)," ",$description)) == "")
                        {
				if($node->children("http://www.w3.org/2005/Atom")->feed->entry->content) $description = $node->children("http://www.w3.org/2005/Atom")->feed->entry->content->__toString();
				if(trim(str_replace(chr(194).chr(160)," ",$description)) == "") $description = "";

			}

			$link = "";
			foreach($node->children("http://www.w3.org/2005/Atom")->feed->children("http://www.w3.org/2005/Atom")->entry->children("http://www.w3.org/2005/Atom")->link as $atomlink)
			{	
				
				$atomlinkAttributes = array();

				foreach ($atomlink->attributes() as $attrname=>$attrval) 
				{
					$atomlinkAttributes[$attrname] = $attrval;
				}
				
				if( (!array_key_exists("rel",$atomlinkAttributes)) || strtolower(trim($atomlinkAttributes["rel"])) == "alternate" )
				{
					if(array_key_exists("href",$atomlinkAttributes) && 0 < strlen(trim($atomlinkAttributes["href"])))
					{
						$link = $atomlinkAttributes["href"];
					}
				}
				else
				{
					if(array_key_exists("href",$atomlinkAttributes) && 0 < strlen(trim($atomlinkAttributes["href"])))
					{
						$attachment_url = trim($atomlinkAttributes["href"]);
						$attachment_title = "";
						if(array_key_exists("title",$atomlinkAttributes) && 0 < strlen(trim($atomlinkAttributes["title"])))
						{
							$attachment_title = trim($atomlinkAttributes["title"]);
						}
						else
						{
							$attachment_title = $attachment_url;
						}
						
						$alreadyThere = false;
						for($a = 0; $a < count($attachments); $a++) if(trim($attachments[$a][1]) == trim($attachment_url)) $alreadyThere = true;
						if($alreadyThere) continue;

						$attachments[] = array
						(
							$attachment_title,
							$attachment_url
						);
					}
				} 
				
			}

		}

		if
		(
			0 < strlen(trim($id)) &&
			0 < strlen(trim($newstype)) &&
			0 < strlen(trim($datetime)) &&
			0 < strlen(trim($title).trim($description).trim($link))
		) 
		{
			
			$listlast[] = array
			(
				"id" => trim($id), 
				"type" => trim($newstype), 
				"datetime" => trim($datetime), 
				"title" => trim($title), 
				"description" => trim($description), 
				"link" => trim($link),
				"attachments" => $attachments
			);

		}
	
	}
	
	return $listlast;

	}
	catch(Exception $e)
	{
		return false;
	}
}
