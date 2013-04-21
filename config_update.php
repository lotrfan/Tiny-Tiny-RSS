#!/usr/bin/env php
<?php
	if (!defined('STDIN')) {
		print "Please run this as a CLI script.\n";
		exit;
	}

	$CONFIG_FILE = "config.php";
	$OUTPUT_FILE = "${CONFIG_FILE}.new";
	$BACKUP_FILE = "${CONFIG_FILE}.bak";
	$DIST_FILE   = "config.php-dist";

	if (!is_readable($CONFIG_FILE)) {
		print "Could not open ${CONFIG_FILE} for reading.\n";
		exit(1);
	}

	$current = file_get_contents($CONFIG_FILE);
	# Find all the constants in the new file
	preg_match_all("/
		^([\t ]*)define\(
			(\s*)([\"'])([\w_]+)\\3(\s*
			,
			\s*)(.*?)(\s*)
			\)
			([\t ]*);([\t ]*)((\/\/|\#)[^\n]*?)?$/mx", $current, $matches, PREG_SET_ORDER);

	$current_config = array();

	foreach ($matches as $match) {
		$current_config[$match[4]] = array(
				"used" => 0,
				"value" => $match[6],
				);
	}

	$new_config = "";
	$dist_config = file($DIST_FILE);

	foreach ($dist_config as $line) {
		if (!preg_match("/
		^([\t ]*)define\(
			(\s*)([\"'])([\w_]+)\\3(\s*
			,
			\s*)(.*?)(\s*)
			\)
			([\t ]*);([\t ]*)((\/\/|\#)[^\n]*?)?$/mx", $line, $match)) {
			$new_config .= $line;
			continue;
		}
		$newline = "";
		if (isset($current_config[$match[4]])) {
			$current_config[$match[4]]["used"] = 1;
			// not a new setting, copy the old value
			$newline .= "$match[1]define($match[2]$match[3]$match[4]$match[3]$match[5]"; // everything up until the value
			$newline .= $current_config[$match[4]]["value"];
			$newline .= "$match[7])$match[8];$match[9]"; // the end of the line
			if (isset($match[10])) {
				$newline .= "$match[10]"; // everything up until the value
			}
		} else {
			print "New Setting, using default: $match[4] = $match[6]\n";
			$newline = $line;
		}
		$new_config .= "$newline\n";
	}

	foreach ($current_config as $setting => $val) {
		if (! $current_config[$setting]["used"]) {
			fwrite(STDERR, "Setting deleted: ${setting}\n");
		}
	}

	// Decide on output method

	// Try opening the backup file
	if (is_writable($OUTPUT_FILE) && ($fh = @fopen($BACKUP_FILE, "w+"))) {
		// we can open the backup file
		fclose($fh);
		print "Writing to ${OUTPUT_FILE}, backup is ${BACKUP_FILE}\n";
		file_put_contents($BACKUP_FILE, $current);
		file_put_contents($OUTPUT_FILE, $new_config);
	} else {
		print $new_config;
		fwrite(STDERR, "Could not open ${OUTPUT_FILE} or ${BACKUP_FILE} for writing\n");
	}

	// vim: ts=2 noexpandtab sw=2 sts=2
?>
