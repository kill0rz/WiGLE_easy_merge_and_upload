# WiGLE_easy_merge_and_upload
Automated merging and uploading files to WiGLE.net using the API

You need to get the API-Key from WiGLE.net: https://wigle.net/account

This tool is _not_ official!

# Features (done)
+ search if any file exists in the directory
+ choose: merge all *.gpsxml files into one single file
+ zip everything
+ upload
+ try if upload succeeded

# Feates (todo)
+ del all files OR archive them

# How to use

1. Install php on your server
2. open and edit _wigle_uploader_config.php_ --> set you configurations (you need to get the API from WiGLE.net)
3. just run _wigle_uploader_run.php_ and wait
