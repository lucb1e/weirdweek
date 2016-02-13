<?php 

if (isset($_GET["source"])) {
	highlight_file(__FILE__);
	exit;
}

if (!isset($_GET['dl'])) {
	?>
	<input type=button value="Groepen doorzoeken" onclick="location='query.php?search';">
	<input type=button value="E-mail ziekmelding naar je groep" onclick="location='query.php?ziek';">
	<input type=button value="Download groups.zip" onclick="location='groups.zip.htm';">
	<input type=button value="Github" onclick="location='https://github.com/lucb1e/weirdweek';">
	<br><br>
	<?php 
}

if (isset($_GET['search'])) {
	?>
		<form action='query.php'>
			<input name=search size=40 value="<?php echo htmlspecialchars($_GET['search']); ?>">
			<input type=submit>
		</form>
		<br><br>
	<?php 
	if (!empty($_GET['search'])) {
		$hits = 0;
		for ($i = 1; $i <= 113; $i++) {
			$lines = explode("\n", file_get_contents("Group$i.html"));
			foreach ($lines as $line) {
				$fline = htmlspecialchars($line);
				// If you want to do regex search, replace this 'if' below here.
				if (stripos($line, $_GET['search']) !== false) {
					echo "<a href='Group$i.html'>Group$i.html</a>: $fline<br>";
					$hits++;
				}
			}
		}
		echo "<br>$hits hits.";
	}
}

if (isset($_GET['ziek'])) {
	if (!isset($_POST['email'])) {
		?>
			<form action='query.php?ziek' method=POST>
				Jouw e-mailadres: <input name=email type=email size=40><br>
				<input type=submit value="Naar controlepagina">
			</form>
			Tip! Docenten kunnen zich hier ook ziekmelden. Of je docent bent wordt afgeleid van het
			ingevulde e-mailadres.
		<?php 
	}
	if (isset($_POST['email'])) {
		$found = false;
		for ($i = 1; $i <= 113; $i++) {
			$data = file_get_contents("Group$i.html");
			if (strpos($data, $_POST['email']) === false) {
				continue;
			}
			$found = true;
			$lines = explode("\n", $data);
			$emails = [];
			foreach ($lines as $line) {
				if (strpos($line, ';') !== false) {
					$name = str_replace(';', ',', trim($line));
				}
				if (strpos($line, '@') !== false) {
					if (trim($line) == $_POST['email']) {
						$from = [$name, trim($line)];
						continue;
					}
					$emails[$name] = trim($line);
				}
			}
		}
		if (!$found) {
			echo "Dat e-mailadres komt niet in een van de groepen voor.";
		}
		else {
			if (strpos($from[1], '@fontys.nl') === false) {
				$inleiding = 'Beste groepsgenoten en docenten';
				$tmp = strpos($from[0], ',');
				$prettyfrom = substr($from[0], $tmp + 1) . ' ' . substr($from[0], 0, -$tmp -2);
			}
			else {
				$inleiding = "Beste onderdanen en collega's";
				$tmp = strpos($from[0], ',');
				$tmp2 = strpos($from[0], ' ', $tmp);
				$prettyfrom = substr($from[0], $tmp + 1, $tmp2 - $tmp - 1);
			}
			$msg = "$inleiding,<br><br>"
				. "Ik ben ziek en verwacht er komende dagen niet te zijn. Hopelijk ben "
				. "ik voor vrijdag nog beter, maar ik kan natuurlijk niks garanderen.<br><br>"
				. "Het is erg jammer dat ik hierdoor de Weird Week moet missen. Ik vertrouw erop "
				. "dat jullie begrip hebben voor deze onfortuinlijke situatie.<br><br>"
				. "Met vriendelijke groet,<br>"
				. "$prettyfrom<br><br>\r\n";

			$to = '';
			$mailto = '';
			foreach ($emails as $name=>$email) {
				$to .= "\"$name\" <$email>, ";
				$mailto .= "$email,";
			}
			$to = substr($to, 0, -2);
			$mailto = substr($mailto, 0, -1);

			if (isset($_GET['dl'])) {
				$headers = "From: \"$from[0]\" <$from[1]>\r\n";
				if (!isset($_POST['selfmail'])) {
					$date = "Date: Mon, 15 Feb 2016 08:11:31\r\n";
					$headers .= "To: $to\r\n";
				}
				$headers .= "Subject: Ziekmelding\r\nContent-Type: text/html\r\nContent-Length: ";
				$headers .= strlen($msg) . "\r\n$date\r\n";

				if (isset($_POST['selfmail'])) {
					$mailfile = 'mailsent/' . sha1($from[1] . 'ayo5uapp8youlhabws');
					if (file_exists($mailfile)) {
						die('E-mail al verzonden.');
					}
					fclose(fopen($mailfile, 'w'));
					$msg = "Deze e-mail is verzonden door $_SERVER[REMOTE_ADDR] "
					. "op " . date('r')
					. " via http://lucb1e.com/rp/crapware/weirdshit/query.php?ziek\r\n<br>\r\n<br>"
					. $msg;
					mail("$prettyfrom <$from[1]>", "Ziekmelding", $msg, $headers);
					echo "E-mail verzonden.";
					exit;
				}

				$eml = $headers . $msg;
				header('Content-Disposition: attachment; filename="ziekmelding.eml"');
				header('Content-Type: message/rfc822');
				header('Content-Length: ' . strlen($eml));
				echo $eml;
				exit;
			}
			?>
				<b>Van:</b> <?php echo "\"$from[0]\" &lt;$from[1]&gt;"; ?><br>
				<b>Aan:</b> <?php echo htmlspecialchars($to); ?><br>
				<b>Datum:</b> Maandag 08:11:31<br>
				<b>Onderwerp:</b> Ziekmelding<br>
				<div style='width: 720px; border: 1px solid black'><?php echo $msg;?></div>
				<form method=POST action='query.php?ziek&amp;dl'>
					<input type=hidden name=email value="<?php echo htmlspecialchars($_POST['email']);?>">
					<input type=submit value="Download template om met Outlook/Thunderbird/... te versturen" name=dl><br>
					<input type=submit name=selfmail value="Stuur naar jezelf zodat je het door kan sturen vanuit Fontys Webmail"> (let op: datum zal verloren gaan bij het doorsturen) (let op 2: check ook je spambox)<br>
					<a href="mailto:<?php echo $mailto;?>?subject=Ziekmelding&body=<?php echo str_replace('+', '%20', urlencode(str_replace('<br>', "\r\n", $msg)));?>">Mailto link</a> (let op: datum gaat verloren bij mailto)
				</form>
			<?php 
		}
	}
}

?>
