<?php

// Where are your wardriving files stored?
$config_files_dir = "./testfiles/";
// tmp dir where files to be uploaded can be stored. These files will remain there in case of error!
$config_uploades_dir = "./uploadfiles/";

// copy the encoded string from https://wigle.net/account here:
$config_wigle_api_encoded = "";

// Should all *.gpsxml files be merged before further processing?
$config_merge_gpsxml = true;

// Should the uploaded zip file be delete afterwards (=true) or should it be archived (=false)?
$config_delete_all = true;
// If false, please give the path to the archive directory
$config_archive_dir = "./archivefiles/";