<?php

include './wigle_uploader_config.php';

if (substr($config_files_dir, -1) != "/") {
	$config_files_dir .= "/";
}
if (substr($config_uploades_dir, -1) != "/") {
	$config_uploades_dir .= "/";
}

// Step 1: Look for any file

if (isset($config_files_dir) && trim($config_files_dir) != '') {
	$dh = opendir($config_files_dir);
	while (false !== ($filename = readdir($dh))) {
		$files[] = $filename;
	}

	if (count($files) == 0) {
		die('No files to process!');
	} else {
		// Step 2: Should the *.gpsxml be merged?
		if ($config_merge_gpsxml) {
			$gpsxml_files = array();
			foreach (glob($config_files_dir . "*.gpsxml") as $file) {
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
				file_put_contents($config_uploades_dir . "merged_gpsxml_files__" . time() . ".gpsxml", $final_gpsxml_content);
				array_map('unlink', glob($config_files_dir . "*.gpsxml"));
			}
		}

		// move all files to upload directory
		$delete = array();
		$files = array();
		$dh = opendir($config_files_dir);
		while (false !== ($filename = readdir($dh))) {
			$files[] = $filename;
		}

		foreach ($files as $file) {
			// do not move directories and invalid file names
			if ((trim($file) != '' || in_array($file, array(".", "..")) || is_file($config_files_dir . $file)) && !preg_match("/[0-9a-zA-Z]{1,}\.[0-9a-zA-Z]{1,}/", $file)) {
				continue;
			}

			if (copy($config_files_dir . $file, $config_uploades_dir . $file)) {
				$delete[] = $config_files_dir . $file;
			}
		}
		foreach ($delete as $file) {
			unlink($file);
		}

		// Step 3: Create a zip file
		$dh_u = opendir($config_uploades_dir);
		while (false !== ($filename = readdir($dh_u))) {
			$upload_files[] = $filename;
		}
		$delete = array();
		$zip = new ZipArchive();
		$filename = $config_uploades_dir . "upload_to_wigle.zip";

		if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
			exit("cannot open <$filename>\n");
		}

		foreach ($upload_files as $file) {
			// do not process directories and invalid file names
			if ((trim($file) != '' || in_array($file, array(".", "..")) || is_file($config_uploades_dir . $file)) && !preg_match("/[0-9a-zA-Z]{1,}\.[0-9a-zA-Z]{1,}/", $file)) {
				continue;
			}
			if ($zip->addFile($config_uploades_dir . $file, $file)) {
				$delete[] = $config_uploades_dir . $file;
			}

		}
		$zip->close();
		foreach ($delete as $file) {
			unlink($file);
		}

		// Step 4: Upload to WiGLE
		class curl {
			public function __construct($config_wigle_api_encoded) {
				$this->wigle_api_encoded = $config_wigle_api_encoded;
				$this->ch = curl_init();
				curl_setopt($this->ch, CURLOPT_POST, 1);
				curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'cookie.txt');
				curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($this->ch, CURLOPT_AUTOREFERER, 1);
				curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
			}

			public function upload($file) {
				curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
					"Content-type: multipart/form-data",
					'Authorization: Basic ' . $this->wigle_api_encoded,
				)
				);

				curl_setopt($this->ch, CURLOPT_URL, 'https://api.wigle.net/api/v2/file/upload/');

				curl_setopt($this->ch, CURLOPT_POSTFIELDS, array(
					"file" => new CurlFile($file, 'application/zip'),
					"donate" => "true",
				));

				return curl_exec($this->ch);
			}
		}

		$getit = new curl($config_wigle_api_encoded);
		$repsonse = json_decode($getit->upload($config_uploades_dir . "upload_to_wigle.zip"));
		if ($repsonse->success == "1") {
			// Step 5: delete all files or archive them
			@unlink($config_uploades_dir . "upload_to_wigle.zip");
		} else {
			echo "Error during upload to WiGLE!";
		}
	}
}