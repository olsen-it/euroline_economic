<?php
include("economic.php");
$conf = simplexml_load_file("config.xml");
$dir = $conf->eue_config->general->csv_file_path[0];
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if (!strcasecmp("csv",substr($file,-3))) {
             	$fil = csv_to_array("$dir/$file");
		put_in_economic($conf,$fil,$dir,$file);
	    }
	}
    }
}
else
	die("Mappe '$dir' er ikke en mappe\n");


function csv_to_array($filename='', $delimiter=',') {
        if(!file_exists($filename) || !is_readable($filename))
                return FALSE;

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                        if(!$header)
                                $header = $row;
                        else
                                $data[] = array_combine($header, $row);
                }
                fclose($handle);
        }
        $retval['rows'] = $data;
        $retval['header'] = $header;
        return $retval;
}
