<?php
require('mailer.php');

function mail_and_die($m)
{
  mailer('it@xinchejian.com', 'Error in '.__FILE__, $m);
  die($m);
}

// add SetEnv MYSQL_PASSWORD "blah" to this site's Apache conf
$link = mysql_connect('localhost', 'webuser', getenv('MYSQL_PASSWORD'))
	or mail_and_die('mysql_connect error');

// Register MAC address
$ipAddress = '('.$_SERVER['REMOTE_ADDR'].')';
$mac2 = '"whatever"';
// Remember to: chmod +s /usr/sbin/arp
exec('/usr/sbin/arp -na', $lines);
foreach($lines as $line)
{
   $cols=preg_split('/\s+/', trim($line));
   if ($cols[1]==$ipAddress)
   {
	$mac2 = '"'.mysql_real_escape_string($cols[3], $link).'"';
	break;
   }
}

mysql_query("UPDATE members.Users SET count = count + 1 WHERE CURDATE() <= paid AND mac = $mac2", $link)
	or mail_and_die('mysql_query UPDATE error');

if (mysql_affected_rows($link) == 1)
{
	// TEMP: use md5sum over Date, random salt and shared secret
	$req = "pin=0326&action=open";

	header('HTTP/1.1 200 OK');
	$header  = "POST / HTTP/1.1\r\n";      // HTTP POST request
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

	// Open a socket for the acknowledgement request
	$fp = fsockopen('10.0.10.10', 80, $errno, $errstr, 30)
		or mail_and_die('fsockopen returned '.$errstr);

	fputs($fp, $header . $req);
	while (!feof($fp))
		$res = fgets ($fp, 1024); 

	fclose($fp);

	header('HTTP/1.1 303 See Other');
	header("Location: /welcomeback.html");
}
else {
	header('HTTP/1.1 303 See Other');
	header("Location: /index.html");
}
mysql_close($link);
unset($link);

