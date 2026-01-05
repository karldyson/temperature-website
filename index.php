// this felt too small to do a whole repo for it so I'm just chucking it here for the moment

<?php
$tempjson = file_get_contents("http://localhost:9090/api/v1/query?query=temperature");
$tempdata = json_decode($tempjson, true);
$sensors = $tempdata['data']['result'];
$data = [];
foreach($sensors as $sensor) {
	$data[$sensor['metric']['friendly_name']] = [];
	$data[$sensor['metric']['friendly_name']]['temp'] = $sensor['value'][1];
	$data[$sensor['metric']['friendly_name']]['updated'] = $sensor['value'][0];
}

$chgjson = file_get_contents("http://localhost:9090/api/v1/query?query=deriv(temperature[15m])");
$chgdata = json_decode($chgjson, true);
$sensors = $chgdata['data']['result'];
foreach($sensors as $sensor) {
	$data[$sensor['metric']['friendly_name']]['chgv'] = $sensor['value'][1];
	if($sensor['value'][1] > 0) {
		$data[$sensor['metric']['friendly_name']]['chg'] = "rising";
		$data[$sensor['metric']['friendly_name']]['chgi'] = "⬆️";
	} elseif($sensor['value'][1] < 0) {
		$data[$sensor['metric']['friendly_name']]['chg'] = "falling";
		$data[$sensor['metric']['friendly_name']]['chgi'] = "⬇️";
	} else {
		$data[$sensor['metric']['friendly_name']]['chg'] = "stable";
		$data[$sensor['metric']['friendly_name']]['chgi'] = "➡️";
	}
}

if(preg_match('/^(curl|Wget|libwww)/', $_SERVER['HTTP_USER_AGENT'])) {
	header("Content-Type: text/plain");
	foreach($data as $sensor_name => $sensor_data) {
		printf("%s %6.2f °C - %s\n", $sensor_data['chgi'], $sensor_data['temp'], $sensor_name);
	}
	exit;
} else {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Temperatures</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width", initial-scale=1">
	<meta http-equiv="refresh" content="30">
	<style>
body {
	font-family: Tahoma, Verdana, Monaco, sans-serif;
}
tr:nth-child(odd) {
	background-color: #ddd;
}
table {
	width: 600px;
	margin-left: 10px;
	margin-right: 10px;
}
.smaller {
	font-size: 50%;
}
@media (max-width: 600px) {
	table {
		width: 100%;
		margin: 2px;
	}
	h1 {
		text-align: center;
	}
}
	</style>
</head>
<body>
	<p>
		<h1>Temperatures</h1>
	</p>
	<p>
		<table>
<?php
}
foreach($data as $sensor_name => $sensor_data) {
	echo "\t\t\t<tr align=center>";
	if($sensor_data['chg'] == "rising") {
		echo "<td>".sprintf("%6.2f", $sensor_data['temp'])." &deg;C &uarr;</td>";
	} elseif($sensor_data['chg'] == "falling") {
		echo "<td>".sprintf("%6.2f", $sensor_data['temp'])." &deg;C &darr;</td>";
	} else {
		echo "<td>".sprintf("%6.2f", $sensor_data['temp'])." &deg;C &rarr;</td>";
	}
	echo "<td>".$sensor_name;
	echo "<br/><span class=smaller>Latest: ".date("D M j G:i:s T Y", $sensor_data['updated'])."</span>";
	echo "</td>";
	echo "</tr>\n";
}
?>
		</table>
	</p>
</body>
</html>
