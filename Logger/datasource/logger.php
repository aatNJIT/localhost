<?php
# This is a really simple decentralized logging script.

function log_message($msg) {
	$pathfile = "/var/log/it490/datasource.log";
        $timestamp = date("m/d/Y g:i:s A");

        file_put_contents($pathfile, "$timestamp - $msg\n", FILE_APPEND);
}


