<?php
class Ff_XmlLint extends Plugin {

	function about() {
		return array(1.0,
			"XmlLint",
			"cheetah@fastcat.org",
			false);
	}

	function api_version() {
		return 2;
	}

	function init($host) {
		$host->add_hook($host::HOOK_FEED_FETCHED, $this);
	}
	
	function hook_feed_fetched($feed_data) {
	    $descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("file", "/dev/null", "a")
		);
		
		$process = proc_open('xmllint --recover -', $descriptorspec, $pipes);
		
		if (is_resource($process)) {
			fwrite($pipes[0], $feed_data);
			fclose($pipes[0]);
			$new_feed_data = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			
			$return_value = proc_close($process);
			if ($return_value == 0)
				$feed_data = $new_feed_data;
		}
		
		return $feed_data;
	}

}
?>
