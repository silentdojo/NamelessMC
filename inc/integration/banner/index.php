<?php
/* 
 *	Made by Samerton
 *  http://worldscapemc.co.uk
 *
 *  License: MIT
 */

$path = "../../../";
$page = "banner";

// Initialise
if(!in_array("init.php", get_included_files())){
	require('../../init.php');
	require('../status/MinecraftServerPing.php');
	require('../../includes/motd_format.php');
	require('../status/SRVResolver.php');
} else {
	require('inc/integration/status/MinecraftServerPing.php');
	require('inc/includes/motd_format.php');
	require('inc/integration/status/SRVResolver.php');
}

$default_server = $queries->getWhere("mc_servers", array("is_default", "=", "1"));
$server_name = htmlspecialchars($default_server[0]->name);
$default_server = htmlspecialchars($default_server[0]->ip);

/*
 *  Resolve real IP address (to support SRV records)
 */
$parts = explode(':', $default_server);
if(count($parts) == 1){
	$domain = $parts[0];
	$query_ip = SRVResolver($domain);
	$parts = explode(':', $query_ip);
	$default_ip = $parts[0];
	$default_port = $parts[1];
} else if(count($parts) == 2){
	$domain = $parts[0];
	$default_ip = $parts[0];
	$default_port = $parts[1];
	$port = $parts[1];
} else {
	echo 'Invalid IP';
	die();
}

// IP to display
if(!isset($port)){
	$address = $domain;
} else {
	$address = $domain . ':' . $port;
}

define( 'MQ_SERVER_ADDR', $default_ip );
define( 'MQ_SERVER_PORT', $default_port );
define( 'MQ_TIMEOUT', 1 );

$Timer = MicroTime( true );

$Info = false;
$Query = null;

// Ping the server
try{
	$Query = new MinecraftPing( MQ_SERVER_ADDR, MQ_SERVER_PORT, MQ_TIMEOUT );
	
	$Info = $Query->Query( );
	
	if($Info === false){
		$Query->Close( );
		$Query->Connect( );
		
		$Info = $Query->QueryOldPre17( );
	}
} catch( MinecraftPingException $e ){
	$Exception = $e;
}

if($Query !== null){
	$Query->Close( );
}

$Timer = Number_Format( MicroTime( true ) - $Timer, 4, '.', '' );

if($Info){ // If the server's up..
	// Parse the MOTD to make it colourful
	$motd = MC_parseMotdColors(nl2br($Info['description']));
	$replace = array(
		'<span style="color:',
		'">',
		'</span>',
		'<br />'
	);
	$motd = str_replace($replace, '', $motd);
	$motd = preg_split('/(?=\n)/', $motd);
	foreach($motd as $item){
		$motd_explode = explode('`', $item);
		
		foreach($motd_explode as $exploded){
			$motd_formatted[] = $exploded;
		}
		
	}

	// Set the content-type
	header('Content-Type: image/png');
	 
	// Create the image
	$SourceFile = "background.png";
	$im = imagecreatefrompng($SourceFile);
	 
	// Minecraft MOTD colours
	$white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
	$light_grey = imagecolorallocate($im, 0xAA, 0xAA, 0xAA);
	$dark_grey = imagecolorallocate($im, 0x55, 0x55, 0x55);
	$black = imagecolorallocate($im, 0, 0, 0);
	$gold = imagecolorallocate($im, 0xFF, 0xAA, 0x00);
	$red = imagecolorallocate($im, 0xAA, 0x00, 0x00);
	$light_red = imagecolorallocate($im, 0xFF, 0x55, 0x55);
	$yellow = imagecolorallocate($im, 0xFF, 0xFF, 0x55);
	$green = imagecolorallocate($im, 0x00, 0xAA, 0x00);
	$light_green = imagecolorallocate($im, 0x55, 0xFF, 0x55);
	$light_blue = imagecolorallocate($im, 0x55, 0xFF, 0xFF);
	$turquoise = imagecolorallocate($im, 0x00, 0xAA, 0xAA);
	$dark_blue = imagecolorallocate($im, 0x00, 0x00, 0xAA);
	$blue = imagecolorallocate($im, 0x55, 0x55, 0xFF);
	$pink = imagecolorallocate($im, 0xFF, 0x55, 0xFF);
	$purple = imagecolorallocate($im, 0xAA, 0x00, 0xAA);

	// Font
	$font = __DIR__ . "/minecraft.ttf";

	// Make the image!
	$serverpic = imagecreatefrompng($Info['favicon']);
	imageAlphaBlending($serverpic, true);
	imageSaveAlpha($serverpic, true);
	imagettftext($im, 12, 0, 90, 30, $white, $font, $server_name);

	$x = 90;

	foreach($motd_formatted as $item){
		// Where does the text need to be situated?
		$text = substr($item, 8);

		$dimensions = imagettfbbox(10, 0, $font, $text);
		$textWidth = abs($dimensions[4] - $dimensions[0]);
		
		if(strstr($item, "\n") || isset($change)){
			if(!isset($change)){
				$x = 90;
			}
			$change = true;
			$y = 70;
		} else {
			$y = 50;
		}
		
		$chars = substr($item, 1, 6);
		// Set the colour
		switch($chars){
			case '000000':
				imagettftext($im, 10, 0, $x, $y, $black, $font, $text);
				break;
			case '0000aa':
				imagettftext($im, 10, 0, $x, $y, $dark_blue, $font, $text);
				break;
			case '0000aa':
				imagettftext($im, 10, 0, $x, $y, $light_green, $font, $text);
				break;
			case '00aa00':
				imagettftext($im, 10, 0, $x, $y, $green, $font, $text);
				break;
			case '00aaaa':
				imagettftext($im, 10, 0, $x, $y, $turquoise, $font, $text);
				break;
			case 'aa0000':
				imagettftext($im, 10, 0, $x, $y, $red, $font, $text);
				break;
			case 'aa00aa':
				imagettftext($im, 10, 0, $x, $y, $purple, $font, $text);
				break;
			case 'ffaa00':
				imagettftext($im, 10, 0, $x, $y, $gold, $font, $text);
				break;
			case 'aaaaaa':
				imagettftext($im, 10, 0, $x, $y, $light_grey, $font, $text);
				break;
			case '555555':
				imagettftext($im, 10, 0, $x, $y, $dark_grey, $font, $text);
				break;
			case '5555ff':
				imagettftext($im, 10, 0, $x, $y, $blue, $font, $text);
				break;
			case '55ff55':
				imagettftext($im, 10, 0, $x, $y, $light_green, $font, $text);
				break;
			case '55ffff':
				imagettftext($im, 10, 0, $x, $y, $light_blue, $font, $text);
				break;
			case 'ff5555':
				imagettftext($im, 10, 0, $x, $y, $light_red, $font, $text);
				break;
			case 'ff55ff':
				imagettftext($im, 10, 0, $x, $y, $pink, $font, $text);
				break;
			case 'ffff55':
				imagettftext($im, 10, 0, $x, $y, $yellow, $font, $text);
				break;
			case 'ffffff':
				imagettftext($im, 10, 0, $x, $y, $white, $font, $text);
				break;
		}
		$x = $x + $textWidth;
	}

	imagettftext($im, 10, 0, 90, 90, $white, $font, $address);

	// Where does the player count need to be situated?
	$text = $Info['players']['online'] . "/" . $Info['players']['max'];

	$dimensions = imagettfbbox(11, 0, $font, $text);
	$textWidth = abs($dimensions[4] - $dimensions[0]);
	$x = imagesx($im) - $textWidth;
	$x = $x - 40;

	imagettftext($im, 11, 0, $x, 23, $white, $font, $Info['players']['online'] . "/" . $Info['players']['max']);
	imagettftext($im, 10, 0, 550, 90, $white, $font, $Timer."ms");
	imagecopy($im, $serverpic, 10, 20, 0, 0, 64, 64);

	$online = imagecreatefrompng("online.png");
	imageAlphaBlending($online, true);
	imageSaveAlpha($online, true);

	imagecopymerge($im, $online, 595, 0, 0, 0, 32, 32, 75);

	imagepng($im);
	imagedestroy($im);
} else {
	// Set the content-type
	header('Content-Type: image/png');
	 
	// Create the image
	$SourceFile = "background.png";
	$im = imagecreatefrompng($SourceFile);
	 
	// Colours
	$white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
	$red = imagecolorallocate($im, 0xAA, 0x00, 0x00);

	// Font
	$font = __DIR__ . "/minecraft.ttf";

	// Make the image!
	$serverpic = imagecreatefrompng("favicon.png");
	imageAlphaBlending($serverpic, true);
	imageSaveAlpha($serverpic, true);
	imagettftext($im, 12, 0, 90, 30, $white, $font, $server_name);
	imagettftext($im, 12, 0, 90, 55, $red, $font, "Server offline");
	imagettftext($im, 10, 0, 90, 90, $white, $font, $address);

	imagecopy($im, $serverpic, 10, 20, 0, 0, 64, 64);

	$online = imagecreatefrompng("offline.png");
	imageAlphaBlending($online, true);

	imagecopymerge($im, $online, 595, 0, 0, 0, 32, 32, 75);

	imagepng($im);
	imagedestroy($im);
}
?>