<?php

require_once("config.php");
require_once("emulate.php");
require_once("myphplib.php");
require_once("postTweet.php");
require_once("fbpost.php");

date_default_timezone_set("Europe/Rome");

$prevMemUsage = -1;
$last_post_time = 0;

while(true)
{

	$added = 0;

	while(true) 
	{

		if(file_exists($stop_newsd_now_cmdfile))
	        {
                	mylog(date("d/m/Y H:i:s")." [INFO] Admin command stop_newsd_now sent. Daemon killed.\n");
			unlink($stop_newsd_now_cmdfile);
                	exit(0);
        	}

		if(memory_get_usage(1) > $prevMemUsage)
		{
			if($prevMemUsage == -1)
			{
				$prevMemUsage = memory_get_usage(1);
			}
			else
			{
				mylog(date("d/m/Y H:i:s")." [WARN] Memory degradation detected. Restarting fetcher...\n");
				exit(1);
			}
		}
		else
		{
			$prevMemUsage = memory_get_usage(1);
		}

		if(!file_exists($tmpSrcTxtFile)) break;

		$channelsfile = fopen($tmpSrcTxtFile,"r");
		$channel = trim(str_replace("\n","",fgets($channelsfile)));
		fclose($channelsfile);
			
		if(trim($channel) == "") 
		{
			mylog(date("d/m/Y H:i:s")." [WARN] Empty channel URL. Stepping to next...\n");
			break;
		}

		// Se e' stato modificato, o se non lo so, procedo con lo scaricamento e tutti i successivi processamenti.
		
		if(!download($channel, $tmpCurrXmlFile)) // Se non sono riuscito a scaricare il canale
        	{
               
			$added = 0;
		 	mylog(date("d/m/Y H:i:s")." [WARN] Unable to download from $channel.\n");
        	
		}
		else
		{
			
			mylog(date("d/m/Y H:i:s")." [INFO] Start fetching news in $channel.\n");

			// Prevediamo la possibilita' che il canale sia fornito in formato compresso.

			$file = fopen($tmpCurrUnzippedXmlFile,"w");

			$lines = gzfile($tmpCurrXmlFile);
			foreach ($lines as $line) {
			   fwrite($file, $line);
			}

			fclose($file);

			exec("rm -f $tmpCurrXmlFile");
			exec("mv $tmpCurrUnzippedXmlFile $tmpCurrXmlFile");

			mylog(date("d/m/Y H:i:s")." [INFO] Unzipped $channel.\n");

			// E andiamo avanti...
			if(filesize($tmpCurrXmlFile) < 1000000)
			{	

				mylog(date("d/m/Y H:i:s")." [INFO] No need to split channel.\n");

	                        $fixstr = str_replace(chr(226).chr(128).chr(147),"",file_get_contents($tmpCurrXmlFile));
        	                $fixstr = str_replace(chr(226).chr(128).chr(156),"&quot;",$fixstr);
                	        $fixstr = str_replace(chr(226).chr(128).chr(157),"&quot;",$fixstr);
                        	$fixstr = str_replace(chr(226).chr(128).chr(153),"&#180;",$fixstr);
	                        $fixfile = fopen($tmpCurrXmlFile,"w"); fwrite($fixfile, $fixstr); fclose($fixfile);
       
				$added = myGetNewsFunction($channel);
				mylog(date("d/m/Y H:i:s")." [INFO] File $tmpCurrXmlFile parsed.\n");
			}
			else
			{
	        	
				mylog(date("d/m/Y H:i:s")." [INFO] Channel splitting needed.\n");

				$added = 0;
               			$numero_suddivisioni = mySplitChannel();
				
				if(-1 == $numero_suddivisioni) 
				{
               	                        mylog(date("d/m/Y H:i:s")." [WARN] Unable to split channel $channel.\n");
				}
				else if (2 == $numero_suddivisioni) // in realta' significa che dalla suddivisione si e' generata una sola parte
				{
					mylog(date("d/m/Y H:i:s")." [WARN] Uneffective channel splitting at $channel.\n");
		                        $fixstr = str_replace(chr(226).chr(128).chr(147),"",file_get_contents($tmpCurrXmlFile));
        		                $fixstr = str_replace(chr(226).chr(128).chr(156),"&quot;",$fixstr);
                		        $fixstr = str_replace(chr(226).chr(128).chr(157),"&quot;",$fixstr);
                        		$fixstr = str_replace(chr(226).chr(128).chr(153),"&#180;",$fixstr);
		                        $fixfile = fopen($tmpCurrXmlFile,"w"); fwrite($fixfile, $fixstr); fclose($fixfile);
					$added = myGetNewsFunction($channel);
					mylog(date("d/m/Y H:i:s")." [INFO] File $tmpCurrXmlFile parsed.\n");	
				}
				else
				{
					mylog(date("d/m/Y H:i:s")." [INFO] Channel $channel splitted into ".($numero_suddivisioni-1)." parts.\n");
               	       			for($j = 1; $j < $numero_suddivisioni; $j++)
               				{
                       				$partname = "$tmpPartFolder/channelpart";
						for($k = 0; $k < 10-strlen($j); $k++) $partname.="0";
						$partname.=$j;
						$chpartstr = file_get_contents($partname); 
						$chpartfile = fopen($tmpCurrXmlFile,"w");
						if(0 === strpos($chpartstr, "<?xml")) fwrite($chpartfile,$chpartstr);
						else fwrite($chpartfile,substr($chpartstr,3)); 
						fclose($chpartfile);

	                        		$fixstr = str_replace(chr(226).chr(128).chr(147),"",file_get_contents($tmpCurrXmlFile));
		        	                $fixstr = str_replace(chr(226).chr(128).chr(156),"&quot;",$fixstr);
                			        $fixstr = str_replace(chr(226).chr(128).chr(157),"&quot;",$fixstr);
                        			$fixstr = str_replace(chr(226).chr(128).chr(153),"&#180;",$fixstr);
		                        	$fixfile = fopen($tmpCurrXmlFile,"w"); fwrite($fixfile, $fixstr); fclose($fixfile);

						$added += myGetNewsFunction($channel);
						mylog(date("d/m/Y H:i:s")." [INFO] File $tmpCurrXmlFile parsed.\n");

						unlink($partname);
               				}
				}
	
			}

		}

	        mylog(date("d/m/Y H:i:s")." [INFO] Retrieved ".$added." news from channel $channel."."\n");
		
		exec("sed -i 1d $tmpSrcTxtFile");
		
		// Twitter

		if(time() - $last_post_time > 120)
		{

			mylog(date("d/m/Y H:i:s")." [INFO] Posting to Twitter.\n");

                        $conn = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname);
                        $result = mysqli_query($conn, "SELECT $dbtable__stats_visualizzazioni.$dbfield__stats_visualizzazioni_newsid newsid, $dbtable__twitter_shorturl.$dbfield__twitter_shorturl_id tweetid, $dbtable__stats_visualizzazioni.$dbfield__stats_visualizzazioni_newstitle newstitle, count(*) visualizzazioni FROM $dbtable__stats_visualizzazioni JOIN $dbtable__twitter_shorturl ON $dbtable__stats_visualizzazioni.$dbfield__stats_visualizzazioni_newsid = $dbtable__twitter_shorturl.$dbfield__news WHERE $dbtable__stats_visualizzazioni.$dbfield__stats_visualizzazioni_newsid <> ALL ( SELECT $dbfield__newsid FROM $dbtable__twitter_twitted ) GROUP BY newsid, tweetid, newstitle ORDER BY visualizzazioni DESC, tweetid DESC LIMIT 1");
if(mysqli_num_rows($result)) {
                        $tweet = mysqli_fetch_array($result, MYSQLI_ASSOC);
mylog("hashtag debug: ".$tweet["newsid"]);
                        $hashtag = substr($tweet["newsid"],1+strpos($tweet["newsid"],"@"));
mylog("hashtag debug: ".$hashtag);
			$result_hashtag = mysqli_query($conn, "SELECT $dbfield__admin_channels_referer FROM $dbtable__admin_channels WHERE $dbfield__admin_channels_channel = '$hashtag'");
			$fetched_hashtag = mysqli_fetch_array($result_hashtag, MYSQLI_ASSOC);
			
			$hashtag = $fetched_hashtag[$dbfield__admin_channels_referer];
mylog("hashtag debug: " .$hashtag);
mylog("hashtag debug: ------------------------");
			if(false !== strpos($hashtag, "/", 8)) $hashtag = substr($hashtag, 0, strpos($hashtag, "/", 8))."/";

                        $text = $tweet["newstitle"];
                        $text = trim(str_replace("\n"," ",strip_tags(tidy(str_replace("&#39;","'",htmlspecialchars_decode($text))))));
                        while(false !== strpos($text,"  ")) $text = str_replace("  "," ",$text);
                        $link = "$websiteUrl/?{$tweet["tweetid"]}";
                        if(140 < strlen("#$hashtag $text $link"))
                        {
                                $eccedenza = strlen("#$hashtag $text $link")-140;
                                $text = substr($text,0,strlen($text)-$eccedenza);
                        }
			if(trim($hashtag) != "" && trim($text) != "" && trim($link) != "$websiteUrl/?")
			{
                        	if($tweets_enabled) { $responseCode = post_tweet("#$hashtag $text $link"); }
				if($fbposting_enabled) post_facebook_link("", $link);
	                        if($tweets_enabled && $responseCode != 200) mylog(date("d/m/Y H:i:s")." [WARN] Twitter API Error $responseCode.\n"); 
				if($tweets_enabled) mysqli_query($conn,"INSERT INTO $dbtable__twitter_twitted($dbfield__newsid) VALUES ('{$tweet["newsid"]}')");
                	        $last_post_time = time();
			}
                        mysqli_close($conn);

			mylog(date("d/m/Y H:i:s")." [INFO] Posted to Twitter.\n");
			}
		}

		// resetMylog();
		resetTempFiles();
	
	}
	
	$cchannels = fopen($tmpSrcTxtFile,"w");
       	$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
	$query = mysqli_query($conn, "SELECT $dbfield__admin_channels_channel FROM $dbtable__admin_channels WHERE $dbfield__skip = 0");
	while($channel = mysqli_fetch_array($query, MYSQL_ASSOC)) fwrite($cchannels, $channel[$dbfield__admin_channels_channel]."\n");
	mysqli_close($conn);
	fclose($cchannels);

	if(file_exists($stop_newsd_cmdfile)) 
	{
		mylog(date("d/m/Y H:i:s")." [INFO] Admin command stop_newsd sent. Daemon killed.\n");
		unlink($stop_newsd_cmdfile);
		exit(0);
	}

}

// Funzioni

function myGetNewsFunction($channelurl)
{

global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	mylog(date("d/m/Y H:i:s")." [INFO] Start parsing file $tmpCurrXmlFile\n");

	exec("cp $tmpCurrXmlFile $tmpCurrOriginalXmlFile");

	$added = 0;

	// Gestisco qualche problema di codifica che empiricamente si e' scoperto poter essere risolto soltanto a questo livello.
	
	$str = file_get_contents($tmpCurrXmlFile);
	$str = substr($str,strpos($str,"<?xml"));
	$str = str_replace("<media:", "<media", $str);
	$str = str_replace("</media:","</media",$str);
	$str = str_replace("<content:encoded","<contentencoded",$str);
	$str = str_replace("</content:encoded","</contentencoded",$str);
	$str = str_replace("<enc:enclosure","<encenclosure",$str);
	$str = str_replace("</enc:enclosure","</encenclosure",$str);
	$str = str_replace("rdf:resource","rdfresource",$str);
	$str = str_replace("enc:url","encurl",$str);
	$str = str_replace("enc:type","enctype",$str);
	$str = str_replace("enc:length","enclength",$str);
	$str = str_replace("\x92", "&#x0092;", $str);
	$str = str_replace("\x93", "&#x0093;", $str);
	$str = str_replace("\x94", "&#x0094;", $str);
	$str = str_replace("\x95", "&#x0095;", $str);
	$str = str_replace("\x96", "&#x0096;", $str);
	$str = str_replace("\x85", "&#x0085;", $str);
	$str = str_replace("\x8a", "&#x008a;", $str);
        $str = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $str);

	$str = trim($str);

	// Aggiungo le CDATA dappertutto dentro le description, se no il DOM quando va a correggere se un caso smatta.
        
	$offset = 0;
        while(false !== strpos($str, "<description>",$offset))
        {
	        $substr = substr($str, strpos($str, "<description>", $offset), strpos($str, "</description>",strpos($str,"<description>",$offset))-strpos($str,"<description>",$offset));
                if(false === strpos($substr,"<![CDATA[") && trim($substr) != "<description>")
                {
                        $str = str_replace($substr, "<description><![CDATA[".substr($substr,13)."]]>", $str);
                }
                $offset = 10+strpos($str, "<description>",$offset);
        }

        $offset = 0;
        while(false !== strpos($str, "<contentencoded>",$offset))
        {
                $substr = substr($str, strpos($str, "<contentencoded>", $offset), strpos($str, "</contentencoded>",strpos($str,"<contentencoded>",$offset))-strpos($str,"<contentencoded>",$offset));
                if(false === strpos($substr,"<![CDATA[") && trim($substr) != "<contentencoded>")
                {
                        $str = str_replace($substr, "<contentencoded><![CDATA[".substr($substr,16)."]]>", $str);
                }
                $offset = 10+strpos($str, "<contentencoded>",$offset);
        }
        $offset = 0;
        while(false !== strpos($str, "<content",$offset) && strpos($str, "<content", $offset) != strpos($str,"<contentencoded",$offset))
        {
		$inizio_content = strpos($str, "<content", $offset);
		$fine_content = strpos($str, ">", $inizio_content);
		$content = substr($str, $inizio_content, 1+$fine_content-$inizio_content);
                $substr = substr($str, strpos($str, $content, $offset), strpos($str, "</content>",strpos($str,$content,$offset))-strpos($str,$content,$offset));
                if(false === strpos($substr,"<![CDATA[") && trim($substr) != $content)
                {
                        $str = str_replace($substr, "$content<![CDATA[".substr($substr,strlen($content))."]]>", $str);
                }
                $offset = 10+strpos($str, $content,$offset);
        }

        $offset = 0;
        while(false !== strpos($str, "<summary>",$offset))
        {
                $substr = substr($str, strpos($str, "<summary>", $offset), strpos($str, "</summary>",strpos($str,"<summary>",$offset))-strpos($str,"<summary>",$offset));
                if(false === strpos($substr,"<![CDATA[") && trim($substr) != "<summary>")
                {
                        $str = str_replace($substr, "<summary><![CDATA[".substr($substr,9)."]]>", $str);
                }
                $offset = 10+strpos($str, "<summary>",$offset);
        }

        // Cerco di rendere sicuro il canale.

        $jsEvents = array
        (
                "onclick",              "onrowenter",           "oninput",
                "ondblclick",           "onrowexit",            "DOMMenuItemActive",
                "onmousedown",          "onrowsdelete",         "DOMMenuItemInactive",
                "onmouseup",            "onrowinserted",        "oncontextmenu",
                "onmouseover",          "oncontextmenu",        "onoverflow",
                "onmousemove",          "ondrag",               "onoverflowchanged",
                "onmouseout",           "ondragstart",          "onunderflow",
                "onkeydown",            "ondragenter",          "onpopuphidden",
                "onkeypress",           "ondragover",           "onpopuphiding",
                "onkeyup",              "ondragleave",          "onpopupshowing",
                "onload",               "ondragend",            "onpopupshown",
                "onunload",             "ondrop",               "onbroadcast",
                "onabort",              "onselectstart",        "oncommandupdate",
                "onerror",              "onhelp",               "DOMContentLoaded",
                "onresize",             "onbeforeunload",       "DOMFrameContentLoaded",
                "onscroll",             "onstop",               "DOMControlValueChanged",
                "onselect",             "onbeforeditfocus",     "invalid ",
                "onchange",             "onstart",              "forminput",
                "onsubmit",             "onfinish",             "formchange",
                "onreset",              "onbounce",		"invalid=",
                "onfocus",              "onbeforeprint",	"invalid\n",
                "onblur",               "onafterprint",
                "oncut",                "onpropertychange",
                "oncopy",               "onfilterchange",
                "onpaste",              "onreadystatechange",
                "onbeforecut",          "onlosecapture",
                "onbeforecopy",         "onbeforeunload",
                "onbeforepaste",        "oncontextmenu",
                "onafterupdate",        "DOMMouseScroll",
                "onbeforeupdate",       "ondragdrop",
                "oncellchange",         "ondragenter",
                "ondataavailable",      "ondragexit",
                "ondatasetchanged",     "ondraggesture",
                "ondatasetcomplete",    "ondragover",
                "onerrorupdate",        "onclose",
                "oncommand"
        );

        for($i = 0; $i < count($jsEvents); $i++) $str = str_replace($jsEvents[$i], "unsafe$i", $str);
	$str = str_replace("<script","<span style=\"display:none;\"",$str); 
	$str = str_replace("</script","</span",$str);

	// Altri problemi con i caratteri tanto per cambiare
	$str = str_replace(chr(226).chr(128)."&#x0094;","-",$str);
	$str = str_replace(chr(226).chr(128).chr(166),"...",$str);

	// Risalvo il canale con le variazioni apportate.

	$file = fopen($tmpCurrXmlFile,"w");
	fwrite($file, $str);
	fclose($file);	
	
	// Carico il documento
	$channeldoc = new DOMDocument();
	$loaded = $channeldoc->load($tmpCurrXmlFile);
	if(!$loaded)
	{
		$channeldoc = new DOMDocument();
		$channeldoc->recover = true;
		$channeldoc->load($tmpCurrXmlFile);
	}
	
	// Individuo il tipo di canale.

	$channeltype = "";
	if(false !== strpos(strtolower($channeldoc->documentElement->tagName),"feed")) $channeltype = "atom";
	if(false !== strpos(strtolower($channeldoc->documentElement->tagName),"rdf")) $channeltype = "rss 1.0";
	if(false !== strpos(strtolower($channeldoc->documentElement->tagName),"rss")) 
	{
		
		if
		(
			$channeldoc->documentElement->hasAttribute("version") &&
			trim($channeldoc->documentElement->getAttribute("version")) == "0.91"
		)
		{
			$channeltype = "rss 0.91";
		}
		else if 
		(
                        $channeldoc->documentElement->hasAttribute("version") &&
                        trim($channeldoc->documentElement->getAttribute("version")) == "0.92"
		)
		{
			$channeltype = "rss 0.92";
		}
		else
		{
			$channeltype = "rss 2.0";
		}

	}

	// A seconda del tipo di canale, individuo gli elementi notevoli.
	// I canali RSS che non siano RDF sono in realta' trattati tutti allo stesso modo.

	$witype = false;

        if($channeltype == "rss 0.91")
        {
                $itempath = "/rss/channel/item";
		$itemtitlepath = "//synd/rss/channel/item/title";
		$itemdescriptionpath = "//synd/rss/channel/item/description";
                $itemdeletionphp = '$synddocbody->documentElement->getElementsByTagName("channel")->item(0)->removeChild($synditem);';
		$namespaces = array(array("media","http://search.yahoo.com/mrss/"));
		$witype = true;
        }

        if($channeltype == "rss 0.92")
        {
                $itempath = "/rss/channel/item";
		$itemtitlepath = "//synd/rss/channel/item/title";
		$itemdescriptionpath = "//synd/rss/channel/item/description";
                $itemdeletionphp = '$synddocbody->documentElement->getElementsByTagName("channel")->item(0)->removeChild($synditem);';
		$namespaces = array(array("media","http://search.yahoo.com/mrss/"));
		$witype = true;
        }

        if($channeltype == "rss 1.0")
        {
                $itempath = "//rdf:RDF/default:item";
		$itemtitlepath = "//synd/rdf:RDF/default:item/default:title";
		$itemdescriptionpath = "//synd/rdf:RDF/default:item/default:description";
                $itemdeletionphp = '$synddocbody->documentElement->removeChild($synditem);';
		$namespaces = array(array("default","http://purl.org/rss/1.0/"),array("rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#"),array("enc","http://purl.oclc.org/net/rss_2.0/enc#"));
		$witype = true;
        }

	if($channeltype == "rss 2.0") 
	{
		$itempath = "/rss/channel/item";
		$itemtitlepath = "//synd/rss/channel/item/title";
		$itemdescriptionpath = "//synd/rss/channel/item/description";
		$itemdeletionphp = '$synddocbody->documentElement->getElementsByTagName("channel")->item(0)->removeChild($synditem);';
		$namespaces = array(array("media","http://search.yahoo.com/mrss/"));
		$witype = true;
	}

        if($channeltype == "atom")
        {
                $itempath = "//default:feed/default:entry";
		$itemtitlepath = "//synd/default:feed/default:entry/default:title";
		$itemdescriptionpath = "//synd/default:feed/default:entry/default:summary";
                $itemdeletionphp = '$synddocbody->documentElement->removeChild($synditem);';
		$namespaces = array(array("default","http://www.w3.org/2005/Atom"), array("base","http://xahlee.org/js/"));
		$witype = true;
        }

	if(!$witype)
	{
		mylog(date("d/m/Y H:i:s")." [WARN] Channel type unknown for $channelurl. Channel skypped.\n");
		$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
		mysqli_query($conn, "UPDATE $dbtable__admin_channels SET $dbfield__skip = 1 WHERE $dbfield__admin_channels_channel = '$channelurl'");
		mysqli_close($conn);
		return $added;
	}
	
	// Gestisco il problema delle sezioni CDATA.

	$descriptionxpath = new DOMXPath($channeldoc);
        for($i = 0; $i < count($namespaces); $i++) $descriptionxpath->registerNamespace($namespaces[$i][0], $namespaces[$i][1]);
	$descriptionnodes = null;
	if($channeltype == "rss 0.91" || $channeltype == "rss 0.92" || $channeltype == "rss 2.0") 
	{
		$descriptionnodes = $descriptionxpath->query("//description");
	}
	else if($channeltype == "rss 1.0")
	{
		$descriptionnodes = $descriptionxpath->query("//default:description");
	}
	else 
	{
		$descriptionnodes = $descriptionxpath->query("//default:summary | //default:content");
	}

	foreach($descriptionnodes as $descriptionnode)
	{

		$alreadyCDATA = false;
		for($cni = 0; $cni < $descriptionnode->childNodes->length; $cni++)
		{
			if($descriptionnode->childNodes->item($cni)->nodeType == XML_CDATA_SECTION_NODE)
			{
				$alreadyCDATA = true;
			}
		}

		$emptyDescription = false;
		if(trim($descriptionnode->textContent) == "") $emptyDescription = true;

		if((!$alreadyCDATA) && (!$emptyDescription))
		{
			$cdatanode = $channeldoc->createCDATASection($channeldoc->saveXML($descriptionnode));
			$newdescriptionnode = null;
		        if($channeltype == "rss 0.91" || $channeltype == "rss 0.92" || $channeltype == "rss 2.0")
			{
				$newdescriptionnode = $channeldoc->createElement("description");
			}
			else if($channeltype == "rss 1.0")
			{
				$newdescriptionnode = $channeldoc->createElement("description");
			}
			else
			{
				if(false !== strpos(strtolower($descriptionnode->tagName),"summary"))
				{
					$newdescriptionnode = $channeldoc->createElementNS("http://www.w3.org/2005/Atom","default:summary");
				}
				else
				{
					$newdescriptionnode = $channeldoc->createElementNS("http://www.w3.org/2005/Atom","default:content");
				}
			}
	
			$newdescriptionnode->appendChild($cdatanode);
			$itemnode = $descriptionnode->parentNode;
			$itemnode->replaceChild($newdescriptionnode, $descriptionnode);
		}

	}
	
	$escapexpath = new DOMXPath($channeldoc);
	$allnodes = $escapexpath->query("//text()");
	foreach($allnodes as $currnode)
	{
		if($currnode->nodeType == XML_CDATA_SECTION_NODE)
		{
			$oldnode = $currnode;
			$txtcontent = $currnode->textContent;
			$newtxtnode = $channeldoc->createTextNode($txtcontent);
			$currnode->parentNode->replaceChild($newtxtnode, $oldnode);
		}
		else
		{
			$currnode->data = htmlspecialchars($currnode->data);
		}		
	}
	

	// Gestiamo il caso che come mille altri non ci dovrebbe essere, di file RSS2 che contengono piu' di un canale
	
	$RSS2Channels = $channeldoc->documentElement->getElementsByTagNameNS("","channel");
	$channelsCount = $RSS2Channels->length;
	if($channelsCount == 0) $channelsCount = 1;
	$originalChanneldoc = clone $channeldoc;
	for($x = 0; $x < $channelsCount; $x++)
	{ 
	if($channelsCount > 1) 
	{ 
	$channeldoc = clone $originalChanneldoc;
	$iRSS2Channels = $channeldoc->documentElement->getElementsByTagNameNS("","channel");
	for($i = 0; $i < $iRSS2Channels->length; $i++)
	{
	if($i != $x) $channeldoc->documentElement->removeChild($iRSS2Channels->item($i)); 
	}
	}
	try
	{
	
	// Ciclo sulle notizie presenti nel canale

	$channelitems_xpath = new DOMXPath($channeldoc);
	for($i = 0; $i < count($namespaces); $i++) $channelitems_xpath->registerNamespace($namespaces[$i][0], $namespaces[$i][1]);
	$channelitems = $channelitems_xpath->query($itempath);
	
	foreach($channelitems as $channelitem)
	{
	
		// E per ogni notizia genero il documento XML relativo alla stessa da inviare al server Clusterpoint.

		$synddocbody = clone $channeldoc;
		$synditems_xpath = new DOMXPath($synddocbody);
		for($i = 0; $i < count($namespaces); $i++) $synditems_xpath->registerNamespace($namespaces[$i][0], $namespaces[$i][1]);
		$synditems = $synditems_xpath->query($itempath); 
		foreach($synditems as $synditem) 
		{
			if($channeldoc->saveXML($channelitem) != $synddocbody->saveXML($synditem))
			{
				eval($itemdeletionphp); 
			}
		}
		
		$synddoc = new DOMDocument($channeldoc->version, $channeldoc->xmlEncoding);
		$synddoc->appendChild($synddoc->createElement("synd"));
		$syndidvalue = md5($channelitem->textContent)."@".$channelurl;
		$synddoc->documentElement->appendChild($synddoc->createElement("id", $syndidvalue));
		$synddoc->documentElement->appendChild($synddoc->createElement("type", "news"));
                $synddoc->documentElement->appendChild($synddoc->createElement("datetime", time()));
		$synddoc->documentElement->appendChild($synddoc->createElement("channelurl",$channelurl));
		$importedsynddocbody = $synddoc->importNode($synddocbody->documentElement, true);
		$synddoc->documentElement->appendChild($importedsynddocbody);
		
		// E invio la richiesta di inserimento della notizia al server Clusterpoint.
		
		$synddocxml = $synddoc->saveXML();
		if($channeltype == "rss 1.0")
                {
                        $synddocxml = str_replace("rdf:","",$synddocxml);
                        $synddocxml = str_replace("xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"","",$synddocxml);
                        $synddocxml = str_replace("xmlns=\"http://purl.org/rss/1.0/\"", "", $synddocxml);
			$synddoc = new DOMDocument();
			$synddoc->loadXML($synddocxml);
                }
		
		try
		{
		

// E QUESTO E' IL TENTATIVO DI SIMULAZIONE. SE NON RIESCE, VA TOLTA QUESTA ROBA E RIMESSO QUELLO CHE E' STATO COMMENTATO SOPRA
			$notiziaInseritaArray = emulateFirstInsertAndListlast($syndidvalue, simplexml_import_dom($synddoc));
			if(!$notiziaInseritaArray) throw new Exception("myownexception");
// FINE DEL TENTATIVO DI SIMULAZIONE
                        
			foreach($notiziaInseritaArray as $notizia)
                        {
			        $notizia["description"] = htmlspecialchars_decode($notizia["description"]); 
                                $notizia["description"] = str_replace("&#39;","'",$notizia["description"]); 
                                $notizia["title"] = strip_tags(htmlspecialchars_decode($notizia["title"])); 
                                mycharfix($notizia, "description"); 
                                mycharfix($notizia, "title"); 
                                $cdataNotiziaCorretta = htmlspecialchars($notizia["description"]); 
                                $cdataTitoloCorretto = htmlspecialchars($notizia["title"]); 
                        }
			
			$newsfix_xpath = new DOMXPath($synddoc);
                        for($i = 0; $i < count($namespaces); $i++) $newsfix_xpath->registerNamespace($namespaces[$i][0], $namespaces[$i][1]);
                        $descriptionitems = $newsfix_xpath->query($itemdescriptionpath);
                        foreach($descriptionitems as $descriptionitem)
                        {
                                $newDescriptionNode = $synddoc->createElement($descriptionitem->tagName,$cdataNotiziaCorretta);
                                $descriptionitem->parentNode->replaceChild($newDescriptionNode,$descriptionitem);
                        }
                        
			$titlefix_xpath = new DOMXPath($synddoc);
                        for($i = 0; $i < count($namespaces); $i++) $titlefix_xpath->registerNamespace($namespaces[$i][0], $namespaces[$i][1]);
                        $titleitems = $titlefix_xpath->query($itemtitlepath);
                        foreach($titleitems as $titleitem)
                        {
                                $newTitleNode = $synddoc->createElement($titleitem->tagName, $cdataTitoloCorretto);
                                $titleitem->parentNode->replaceChild($newTitleNode, $titleitem);
                        }
			
			$conn = mysqli_connect($dbhost,$dbuser,$dbpass,$dbname);
			mysqli_query($conn, "INSERT INTO $dbtable__admin_news($dbfield__id, $dbfield__admin_news_channel, $dbfield__content) VALUES ('".mysqli_escape_string($conn, $syndidvalue)."','".(false !== strpos($channelurl,"?")?substr($channelurl,0,strpos($channelurl,"?")):$channelurl)."', '".mysqli_escape_string($conn, $synddoc->saveXML())."')");	
			mysqli_close($conn);		

			sleep(3);

			$added++;

			$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
			mysqli_query($conn, "INSERT INTO $dbtable__twitter_shorturl($dbfield__news) VALUES ('$syndidvalue')");
			mysqli_close($conn);

		}
		catch(Exception $e){ }   
	
	}

	// Chiusura del for per la gestione del caso in cui vi siano piu' canali all'interno di un singolo file RSS2
	}
	catch(Exception $e2){ }  
	}

	// Rimuovo il file tempoeaneo relativo al canale scaricato.

	exec("rm -f $tmpCurrXmlFile &>/dev/null || echo \"".date("d/m/Y H:i:s")."\t"."ERROR - SOMETHING WRONG HAPPENED WHILE CLEANING TEMPORARY FOLDER\""."\n");
	
	// Ritorno al chiamante il numero delle notizie estratte dal canale.

	return $added;

}

function mylog($str)
{

global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	$logfile = fopen("$newsLogFolder/".date("dmY").".log", "a+");
	fwrite($logfile, $str);
	fclose($logfile);
	sleep(1);

}

function resetMylog()
{
	
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

exec("rm -rf $newsLogFolder/*");
}

function resetTempFiles()
{
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;	

exec("rm -rf $tmpPartFolder/*");
}

function mySplitChannel()
{

global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	exec("sed -i 's/<item/\\n<item/g' $tmpCurrXmlFile");
	exec("csplit -f '$tmpPartFolder/rawchannelpart' -n 10 $tmpCurrXmlFile '/<item/' {*}"); 
	if(!file_exists("$tmpPartFolder/rawchannelpart0000000001"))
	{
		exec("rm -f $tmpPartFolder/rawchannelpart0000000000");
		exec("sed -i 's/<entry/\\n<entry/g' $tmpCurrXmlFile");
		exec("csplit -f '$tmpPartFolder/rawchannelpart' -n 10 $tmpCurrXmlFile '/<entry/' {*}");
	}
	if(!file_exists("$tmpPartFolder/rawchannelpart0000000001"))
	{
		return -1;
	}

	$channel_ending = shell_exec("tail $tmpCurrXmlFile");
	$channel_ending = substr($channel_ending,strrpos($channel_ending,"<"));
	if(false !== strpos(strtolower($channel_ending), "rss")) $channel_ending = "</channel>$channel_ending";
	$partexists = true;
	$partno = 1;
	while($partexists)
	{
		$partname = "$tmpPartFolder/rawchannelpart";
		for($i = 0; $i < 10-strlen($partno); $i++) $partname.="0";
		$partname.=$partno;
		if(file_exists($partname))
		{
			exec("cat $tmpPartFolder/rawchannelpart0000000000 $partname > ".str_replace("rawchannelpart","semirawchannelpart",$partname));
			exec("echo \"$channel_ending\" >> ".str_replace("rawchannelpart","semirawchannelpart",$partname));
			exec("mv ".str_replace("rawchannelpart","semirawchannelpart",$partname)." ".str_replace("rawchannelpart","channelpart",$partname));
			$partno++;
		}
		else
		{
			$partexists = false;
		}

	}

	// evitiamo di aggiungere una conclusione di canale all'ultima notizia visto che ce l'ha gia'
	$partno--;
	$lnfn = "$tmpPartFolder/channelpart";
	for($i = 0; $i < 10-strlen($partno); $i++) $lnfn.="0";
	$lnfn.=$partno;
	$lnstr = file_get_contents($lnfn);
	$lnstr = substr($lnstr,0,strrpos($lnstr,$channel_ending));
	$lnfh = fopen($lnfn, "w");
	fwrite($lnfh, $lnstr);
	fclose($lnfh);
	$partno++;
	if(2 < $partno) exec("rm -f $tmpCurrXmlFile");
	exec("rm -f $tmpPartFolder/rawchannelpart*");
	
	return $partno;

}

function isBlacklisted($channel)
{
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

	if($channel == "http://mambasana.ru/component/option,com_rss/feed,RSS2.0/no_html,1/") return true;
	return false;
}

function download($url, $path)
{
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;
 
	mylog(date("d/m/Y H:i:s")." [INFO] Download --> Starting download from $url\n");

	$timeout = 60;

	$url = str_replace("&amp;","&",$url);

	if(false !== stripos($url, "(") || false !== stripos($url, ")") ) 
	{
		 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Malformed URL: parenthesis detected. Unable to download.\n");
		return false;
	}

	if(strlen($url) > 2083) return false;

	$redirectionTracker = array();
	$responseHeaders = array();
	$rawResponseHeaders = @http_head($url, array('timeout'=>$timeout), $responseHeaders);
	if(trim($rawResponseHeaders) == "") 
	{
		 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Unable to get HTTP headers. Unable to download.\n");		
		return false;
	}
	else
	{
		 mylog(date("d/m/Y H:i:s")." [INFO] Download --> HTTP Headers:\n$rawResponseHeaders\n");

	}

	$responseHeaders = http_parse_message($rawResponseHeaders);

	while($responseHeaders->responseCode == 301 || $responseHeaders->responseCode == 302 || $responseHeaders->responseCode == 303)
	{

		if(in_array($url, $redirectionTracker)) 
		{
			 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Redirection loop detected. Unable to download.\n");
			return false;
		}

		$redirectionTracker[] = $url;

        	if($responseHeaders->responseCode == 302 && isset($responseHeaders->headers["Location"]) && trim($responseHeaders->headers["Location"]) != "" && trim($responseHeaders->headers["Location"]) != trim($url))
	        {
			if(0 === strpos($responseHeaders->headers["Location"],"http://") || 0 === strpos($responseHeaders->headers["Location"],"https://"))
                	{
	                        $url = $responseHeaders->headers["Location"];
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 302 redirection by absolute URL: will browse to $url\n");

        	        }
                	else
	                {
        	                if(substr($responseHeaders->headers["Location"],0,2) == ".." && substr($url,strlen($url)-1) != "/") $responseHeaders->headers["Location"] = substr($responseHeaders->headers["Location"],2);
				$url = $url.($responseHeaders->headers["Location"]);
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 302 redirection by relative URL: will browse to $url\n");

                	}
	        }

        	if($responseHeaders->responseCode == 301 && isset($responseHeaders->headers["Location"]) && trim($responseHeaders->headers["Location"]) != ""  && trim($responseHeaders->headers["Location"]) != trim($url)) 
	        {
        	        if(0 === strpos($responseHeaders->headers["Location"],"http://") || 0 === strpos($responseHeaders->headers["Location"],"https://"))
                	{
	                        $url = $responseHeaders->headers["Location"];
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 301 redirection by absolute URL: will browse to $url\n");

        	        }
                	else
	                {
                                if(substr($responseHeaders->headers["Location"],0,2) == ".." && substr($url, strlen($url)-1) != "/") $responseHeaders->headers["Location"] = substr($responseHeaders->headers["Location"],2);
        	                $url = $url.($responseHeaders->headers["Location"]);
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 301 redirection by relative URL: will browse to $url\n");

                	}
	        }



                if($responseHeaders->responseCode == 303 && isset($responseHeaders->headers["Location"]) && trim($responseHeaders->headers["Location"]) != ""   && trim($responseHeaders->headers["Location"]) != trim($url)) 
                {
                        if(0 === strpos($responseHeaders->headers["Location"],"http://") || 0 === strpos($responseHeaders->headers["Location"],"https://"))
                        {
                                $url = $responseHeaders->headers["Location"];
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 303 redirection by absolute URL: will browse to $url\n");

                        }
                        else
                        {
                                if(substr($responseHeaders->headers["Location"],0,2) == ".." && substr($url, strlen($url)-1) != "/") $responseHeaders->headers["Location"] = substr($responseHeaders->headers["Location"],2);
                                $url = $url.($responseHeaders->headers["Location"]);
				 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Code 303 redirection by relative URL: will browse to $url\n");

                        }
                }

		if(!(isset($responseHeaders->headers["Location"]) && trim($responseHeaders->headers["Location"]) != "")) 
		{
			 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Redirection toward unspecified location. Unable to download.\n");
			return false;
		}
		
		if(strlen($url) > 2083) 
		{
			 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Redirection toward malformed URL: URL length exceeds 2083 chars. Unable to download.\n");
			return false;
		}

        	$responseHeaders = array();
		$rawResponseHeaders = @http_head($url, array('timeout'=>$timeout), $responseHeaders);
	        if(trim($rawResponseHeaders) == "") 
		{
			 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Unable to get HTTP headers. Unable to download.\n");
			return false;
		}
		else
		{
			 mylog(date("d/m/Y H:i:s")." [INFO] Download --> HTTP Headers:\n$rawResponseHeaders\n");
		}
	        $responseHeaders = http_parse_message($rawResponseHeaders);
 
	}

	exec("rm -f $path");

        $newfname = $path;
	// Questo tipo di controllo sul tag di inizio e fine HTML in questo caso non ha senso, perche' a cose normali qui scarico documenti XML,
	// non pagine HTML.
	$htmlStarts = true;
	$htmlEnds = true;

	if(strlen($url) > 2083) 
	{
		 mylog(date("d/m/Y H:i:s")." [WARN] Download --> Malformed URL: URL length exceeds 2083 chars. Unable to download.\n");
		return false;
	}

        $context = stream_context_create( array(
                'http'=>array(
                    'timeout' => $timeout
                  )
        ));
        
	 mylog(date("d/m/Y H:i:s")." [INFO] Download --> Attempting to get channel content via PHP fopen\n");


	$emptyFragmentCounter = 0;
	$downloadFailedInProgress = false;
	$file = @fopen($url, "rb",false, $context);

        if($file)
        {

                $newf = fopen($newfname, "wb");

                if($newf)
                {

                        while(!feof($file))
                        {

				$fragment = fread($file, 1024 * 8 );
				if(!trim($fragment)) $emptyFragmentCounter++; 
				if($emptyFragmentCounter == 10) 
				{
					$downloadFailedInProgress = true;
					break;
				}
				fwrite($newf, $fragment, 1024 * 8);
				mylog(date("d/m/Y H:i:s")." [INFO] Download --> Line added\n");

                        }

                        fclose($newf);

                }

                fclose($file);

        }
        
	if( (!$file) || $downloadFailedInProgress ) 
        {

		mylog(date("d/m/Y H:i:s")." [INFO] Download --> PHP fopen attempt failed. Attempting to get channel content via linux wget.\n");
                $wget_attempt = shell_exec("wget -O $path -t 1 -T $timeout \"$url\" &>/dev/null || echo \"DOWNLOAD FAILED\"");
                if(false !== strpos($wget_attempt,"DOWNLOAD FAILED")) 
		{
			mylog(date("d/m/Y H:i:s")." [WARN] Download --> Unable to get channel content via linux wget. Unable to download.\n");
			return false;
		}
		else
		{
			$logwgetfile = fopen($path,"rb");
			while(!feof($logwgetfile)) mylog(date("d/m/Y H:i:s")." [INFO] Download --> ".fread($logwgetfile,1024*8)."\n");
			fclose($logwgetfile);
		}

        }

	// Fine del tentativo

	// Sto trattando canali RSS. Qui dentro non ci entro mai perche' ho impostato di partenza le due variabili a true perche' in realta'
	// non devo e non voglio fare questo tipo di controllo, che va bene se sto cercando gli URL dei canali RSS tra le pagine HTML,
	// ma non se sto scaricando il contenuto di un canale RSS.
	if($htmlStarts && (!$htmlEnds))
	{
		unlink($path);
                $wget_attempt = shell_exec("wget -O $path -t 1 -T $timeout \"$url\" &>/dev/null || echo \"DOWNLOAD FAILED\"");
                if(false !== strpos($wget_attempt,"DOWNLOAD FAILED")) return false;
	}
	

	// Verifichiamo che sia andato tutto bene.

	if(!file_exists($path)) 
	{
		mylog(date("d/m/Y H:i:s")." [WARN] Download --> No downloaded file found. Download definitively failed.\n");
		return false;
	}

	return $url;

}

function uncompress($srcName, $dstName) {
global $tmpSrcTxtFile, $stop_newsd_now_cmdfile, $stop_newsd_cmdfile, $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder, $dbhost, $dbuser, $dbpass, $dbname, $dbtable__twitter_twitted, $dbtable__admin_news, $dbtable__twitter_shorturl, $dbtable__admin_channels, $dbtable__stats_visualizzazioni, $dbfield__newsid, $dbfield__id, $dbfield__admin_news_channel, $dbfield__admin_channels_channel, $dbfield__content, $dbfield__news, $dbfield__skip, $dbfield__stats_visualizzazioni_newsid, $dbfield__twitter_shorturl_id, $dbfield__stats_visualizzazioni_newstitle,$dbfield__admin_channels_referer, $newsLogFolder, $fbposting_enabled, $appid, $appsecret, $pageAccessToken, $fb_api_post_path, $tweets_enabled, $consumer_key, $consumer_secret, $user_token, $user_secret;

    $sfp = gzopen($srcName, "rb");
    if(!$sfp) return false;
    $fp = fopen($dstName, "w");

    while ($string = gzread($sfp, 4096)) {
        fwrite($fp, $string, strlen($string));
    }
    gzclose($sfp);
    fclose($fp);
    return true;
}

?>

