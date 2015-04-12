DYEFECHAPA README FILE
----------------------

What Dyefechapa is
------------------

Dyefechapa is a PHP script that extracts news from any type of feed channel, even malformed channels, whatever character encoding they have, and writes them down to a database.

How Dyefechapa works
--------------------

Dyefechapa source is a list of feed channel URLs that have to be stored in a database, in a DB table named as configured in variable $dbtable__admin_channels defined in config.php, in a text field named as configured in variable $dbfield__admin_channels_channel defined in config.php. Source DB table also must have a numeric field named as configured in variabile $dbfield__skip defined in config.php. If skip field is valorized to 1, the channel is skipped, otherwise it is read. DB connection data are configured in variables $dbhost, $dbuser, $dbpass and $dbname defined in config.php. Source DB table also must have a text field named as configured in variable $dbfield__admin_channels_referer, that must contain the URL of the Web page where the link to the channel was found.

Dyefechapa destination is a DB table named as configured in variable $dbtable__admin_news. Specifically, the table must have at least three fields: a primary key field named as configured in variable $dbfield__id defined in config.php, a text field that will contain the URL of the channel where the news was located and that has to be named as configured in variable $dbfield__admin_news_channel defined in config.php, and a last field that will contain the actual news and that has to be named as configured in variable $dbfield__content defined in config.php. DB connection data are configured in variables $dbhost, $dbuser, $dbpass and $dbname defined in config.php.

When started, Dyefechapa reads from the source table, and write down all channel URLs to a temporary text file, one per line. The temporary file has to be located where configured in variable $tmpSrcTxtFile defined in config.php. After having read news from a channel, the channel URL is removed from the temporary text file. When the temporary text file is empty, it is filled again reading from the database. The script loops this way indefinitely. You can force the script to stop immediately, that is immediately after having completed its work over the channel currently read, by creating an empty file located and named as configured in variable $stop_newsd_now_cmdfile defined in config.php. You also can force the script to stop when the temporary text file will be empty, by creating an empty file located and named as configured in variable $stop_newsd_cmdfile defined in config.php.

Dyefechapa also writes down some other temporary files while working, whose location and filenames have to be configured by valorizing variables $tmpCurrUnzippedXmlFile, $tmpCurrXmlFile, $tmpCurrOriginalXmlFile, $tmpPartFolder.

Dyefechapa also writes down log files, one per day, in a folder configured in variable $newsLogFolder defined in config.php

Dyefechapa also automatically posts extracted news over Facebook and Twitter. Posts are published every 2 minutes, choosing the most read news among the ones that have not been published to socials yet. The most read news is determined by accessing an ad hoc table named as configured in variable $dbtable__stats_visualizzazini defined in config.php, that has to be populated externally by adding one row for each user access to the extracted news. In particular, table $dbtable__stats_visualizzazini must have an autoincrement primary key field, and two additional text fields for containing id and title of the read news, respectively named as configured in variable $dbfield__stats_visualizzazioni_newsid and in variable $dbfield__stats_visualizzazioni_newstitle defined in config.php. It has to be populated externally means that it is expected that you provide users some way for accessing news you have extracted, and you track user access to news by adding one row for each news read, and you put this data at the disposal of Dyefechapa for allowing it to post the "most interesting" news only, based on the most democratic possible criteria, the user preference. For that tweets posting works properly, you also have to create two tables. The first must be named as configured in variable $dbtable__twitter_shorturl, and must contain two fields at least, the first named as configured in variable $dbfield__twitter_shorturl_id, and the second named as configured in variable $dbfield__news, respectively containing a short identifier to the news, and the complete news id that must match one of the identifiers contained in field $dbfield__id of table $dbtable__admin_news, being all mentioned variables defined in config.php. The short identifier is used for composing a short URL, obtained by the concatenation of $websiteUrl also defined in config.php, with string "/?" and the short identifier itself. The short URL is written down in the tweet. This also means that you have to externally arrange so that an appropriate Web page is provided to your user when they connect to so formed URL. The second table that you must create so that tweeting works properly is named as configured in variable $dbtable__twitter_twitted defined in config.php, and must contain at least one field named as configured in variable $dbfield__newsid defined in config.php. This table will be automatically populated by Dyefechapa for tracking news that have already been tweeted. Also fundamental, tweeing and Facebook posting must be enabled by setting to true variables named respectively $tweeting_enabled, and $fbposting_enabled, defined in config.php, and appropriate values must be provided in config.php for variables contained in sections labeled as Facebook and Twitter.

Quick start
-----------

1. Download all project files in a folder of your choice.

2. Configure the script by editing config.php
 
3. Create all needed DB tables (read above for details).

4. Enter values in source table. 

5. Run newsd.php (suggestion: create a shell script that automatically relaunch the script every time it exits smootly, with a success code).

Contributions & Bug reports
---------------------------

Dyefechapa is a GitHub project.

Browse to https://github.com/msoderi/DYEFECHAPA to contribute and/or report bugs.
