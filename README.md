# WiGLE_easy_merge_and_upload
Automated merging and uploading files to WiGLE.net using the API

You need to get the API-Key from WiGLE.net: https://wigle.net/account

This tool is _not_ official!

# Features
+ Search for alle uploadable files in given directory
+ optional: merge all *.gpsxml-files to one file? (useful for tracking one run in different files by exporting the file from WiGLE as KML)
+ put all files into a ZIP and upload it via WiGLE API (saving bandwith)
+ if upload succeeded all files will be deleted from disc
+ optional: uploaded files can be archived

# How to use

1. install php on your server
2. open and edit _wigle_uploader_config.php_
    * set you configurations (you need to get the API from WiGLE.net)
    * make sure all configured directories do exist
3. just run _wigle_uploader_run.php_ and wait

# Contact and contributing
Pull request are always appreated!
Please report any bug and feature request.
Contact me via Github, please.

I am not a member of WiGLE team and I am not associated with them.