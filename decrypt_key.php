#!/usr/bin/php
<?php

# ./decrypt_key.php crypted_key.bin > key.bin

if( isset( $argv[1] ) ) {

	$key = file_get_contents( $argv[1] );

	if( strlen( $key ) == 20 ) {
		$k = $key[0 + 4]
		 . $key[4 + 4]
		 . $key[8 + 4]
		 . $key[12 + 4]
		 . $key[2 + 4]
		 . $key[6 + 4]
		 . $key[10 + 4]
		 . $key[14 + 4]
		 . $key[15 + 4]
		 . $key[11 + 4]
		 . $key[7 + 4]
		 . $key[3 + 4]
		 . $key[13 + 4]
		 . $key[9 + 4]
		 . $key[5 + 4]
		 . $key[1 + 4];
		
		echo $k;
		exit(0);
	} else {
		echo "Key more than 20 bytes - Error\r\n";
		exit(1);
	}

} else {
	echo "Error key is missing\r\n";
	exit(1);
}

?>
