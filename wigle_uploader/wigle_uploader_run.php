<?php

include './wigle_uploader_config.php';

if (substr($files_dir, -1) != "/") {
	$files_dir .= "/";
}
if (substr($uploades_dir, -1) != "/") {
	$uploades_dir .= "/";
}

// This is the entire file that was uploaded to a temp location.
// $localFile = $_FILES[$fileKey]['tmp_name'];

// function execute($ho, $state, $array, $newsess) {
// 	global $wigle_api_encoded;

// 	$log_hoster = $array;
// 	$ch = curl_init();

// 	$url = $log_hoster[$ho][0];
// 	$postdata = $log_hoster[$ho][1];
// 	$ref = $log_hoster[$ho][2];

// 	$headers = array(
// 		'Content-Type:application/json',
// 		'Authorization: Basic ' . $wigle_api_encoded,
// 	);
// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// 	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
// 	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
// 	curl_setopt($ch, CURLOPT_COOKIESESSION, $newsess);
// 	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// 	curl_setopt($ch, CURLOPT_HEADER, false);
// 	curl_setopt($ch, CURLOPT_POST, true);
// 	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
// 	curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
// 	curl_setopt($ch, CURLOPT_REFERER, $ref);
// 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// 	curl_setopt($ch, CURLOPT_URL, $url);
// 	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
// 	$retu = curl_exec($ch);
// 	sleep(1);
// 	return $retu;
// }

// function getsite($zielurl, $postdata = "", $ref = "", $newsess = true) {
// 	$log_hoster = array(
// 		array($zielurl, $postdata, $ref),
// 	);
// 	$temp = execute(0, true, $log_hoster, $newsess);
// 	return $temp;
// }

// Step 1: Look for any file

if (isset($files_dir) && trim($files_dir) != '') {
	$dh = opendir($files_dir);
	while (false !== ($filename = readdir($dh))) {
		$files[] = $filename;
	}

	if (count($files) == 0) {
		die('No files to process!');
	} else {
		// Step 2: Should the *.gpsxml be merged?
		if ($merge_gpsxml) {
			$gpsxml_files = array();
			foreach (glob($files_dir . "*.gpsxml") as $file) {
				$gpsxml_files[] = $file;
			}

			// merge them
			$firstfile = true;
			$final_gpsxml_header = '';
			$final_gpsxml_footer = '';
			$final_gpsxml_points = '';
			foreach ($gpsxml_files as $gpsxml_file) {
				if ($firstfile) {
					// get header of first file
					$firstfilecontent = explode("\n", file_get_contents($gpsxml_file));
					$firstfilecontent_i = 0;
					while (substr(trim($firstfilecontent[$firstfilecontent_i]), 0, 10) != "<gps-point") {
						$final_gpsxml_header .= $firstfilecontent[$firstfilecontent_i] . "\n";
						$firstfilecontent_i++;
					}
					$final_gpsxml_footer = "</gps-run>";
					$firstfile = false;
				}

				//get content of every gps-point
				$gpsxml_file_content = explode("\n", file_get_contents($gpsxml_file));
				foreach ($gpsxml_file_content as $gps_points) {
					if (substr(trim($gps_points), 0, 10) == "<gps-point") {
						$final_gpsxml_points .= $gps_points . "\n";
					}
				}
			}
			$final_gpsxml_content = $final_gpsxml_header . $final_gpsxml_points . $final_gpsxml_footer;
			if (trim($final_gpsxml_content) != '') {
				file_put_contents($uploades_dir . "merged_gpsxml_files__" . time() . ".gpsxml", $final_gpsxml_content);
				array_map('unlink', glob($files_dir . "*.gpsxml"));
			}
		}

		// move all files to upload directory
		$delete = array();
		$files = array();
		$dh = opendir($files_dir);
		while (false !== ($filename = readdir($dh))) {
			$files[] = $filename;
		}

		foreach ($files as $file) {
			// do not move directories and invalid file names
			if ((trim($file) != '' || in_array($file, array(".", "..")) || is_file($files_dir . $file)) && !preg_match("/[0-9a-zA-Z]{1,}\.[0-9a-zA-Z]{1,}/", $file)) {
				continue;
			}

			if (copy($files_dir . $file, $uploades_dir . $file)) {
				$delete[] = $files_dir . $file;
			}
		}
		foreach ($delete as $file) {
			unlink($file);
		}

		// Step 3: Create a zip file
		$dh_u = opendir($uploades_dir);
		while (false !== ($filename = readdir($dh_u))) {
			$upload_files[] = $filename;
		}
		$delete = array();
		$zip = new ZipArchive();
		$filename = $uploades_dir . "upload_to_wigle.zip";

		if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
			exit("cannot open <$filename>\n");
		}

		foreach ($upload_files as $file) {
			// do not process directories and invalid file names
			if ((trim($file) != '' || in_array($file, array(".", "..")) || is_file($uploades_dir . $file)) && !preg_match("/[0-9a-zA-Z]{1,}\.[0-9a-zA-Z]{1,}/", $file)) {
				continue;
			}
			if ($zip->addFile($uploades_dir . $file, $file)) {
				$delete[] = $uploades_dir . $file;
			}

		}
		$zip->close();
		foreach ($delete as $file) {
			unlink($file);
		}

		// Step 4: Upload to WiGLE
	}
}