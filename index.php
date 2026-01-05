<?php
$prometheus = "http://localhost:9090";
$skipsensors = ['TestPico', 'DefaultSensorName'];
$tempjson = file_get_contents($prometheus."/api/v1/query?query=temperature");
$tempdata = json_decode($tempjson, true);
$sensors = $tempdata['data']['result'];
$data = [];
foreach($sensors as $sensor) {
	if(in_array($sensor['metric']['sensor_name'], $skipsensors)) continue;
	$data[$sensor['metric']['friendly_name']] = [];
	$data[$sensor['metric']['friendly_name']]['temp'] = $sensor['value'][1];
	$data[$sensor['metric']['friendly_name']]['updated'] = $sensor['value'][0];
	$updated = $sensor['value'][0];
	if($sensor['metric']['sensor_name'] == "Outside") $outside = $sensor['value'][1];
}
$updated_string = date("D M j G:i:s T Y", $updated);

$chgjson = file_get_contents($prometheus."/api/v1/query?query=deriv(temperature[15m])");
$chgdata = json_decode($chgjson, true);
$sensors = $chgdata['data']['result'];
foreach($sensors as $sensor) {
	if(in_array($sensor['metric']['sensor_name'], $skipsensors)) continue;
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

$minjson = file_get_contents($prometheus."/api/v1/query?query=min_over_time(temperature[24h])");
$mindata = json_decode($minjson, true);
$sensors = $mindata['data']['result'];
foreach($sensors as $sensor) {
	if(in_array($sensor['metric']['sensor_name'], $skipsensors)) continue;
	$data[$sensor['metric']['friendly_name']]['min'] = $sensor['value'][1];
}

$maxjson = file_get_contents($prometheus."/api/v1/query?query=max_over_time(temperature[24h])");
$maxdata = json_decode($maxjson, true);
$sensors = $maxdata['data']['result'];
foreach($sensors as $sensor) {
	if(in_array($sensor['metric']['sensor_name'], $skipsensors)) continue;
	$data[$sensor['metric']['friendly_name']]['max'] = $sensor['value'][1];
}

if(preg_match('/^(curl|Wget|libwww)/', $_SERVER['HTTP_USER_AGENT'])) {
	header("Content-Type: text/plain");
	#print_r($tempdata['data']['result']);
	#print_r($chgdata['data']['result']);
	#print_r($data);
	foreach($data as $sensor_name => $sensor_data) {
		printf("%s %5.1f °C %5.1f °C %5.1f °C - %s\n", $sensor_data['chgi'], $sensor_data['min'], $sensor_data['temp'], $sensor_data['max'], $sensor_name);
	}
	echo "Fetched at $updated_string\n";
	exit;
}
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
	width: 800px;
	margin-left: 10px;
	margin-right: 10px;
}
.smaller {
	font-size: 50%;
}
.outside {
	font-size: 800%;
	text-align: center;
	width: 800px;
	margin: 10px;
}
@media (max-width: 800px) {
	table {
		width: 100%;
		margin: 2px;
	}
	h1 {
		text-align: center;
	}
	.outside {
		font-size: 800%;
		text-align: center;
		width: 100%;
		margin: 2px;
	}
}
@media (orientation: landscape) {
	.landonly {
		display: table-cell;
	}
	.temp {
		width: 125px;
		text-align: right;
	}
}
@media (orientation: portrait) {
	.landonly {
		display: none;
	}
	.temp {
		text-align: right;
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
foreach($data as $sensor_name => $sensor_data) {
	echo "\t\t\t<tr align=center>";
	echo "<td align=right class='temp landonly'>".sprintf(" %5.1f", $sensor_data['min'])." &deg;C </td>";
	echo "<td align=right class='temp'>".sprintf(" %5.1f", $sensor_data['temp'])." &deg;C ".$sensor_data['chgi']."</td>";
	echo "<td align=right class='temp landonly'>".sprintf(" %5.1f", $sensor_data['max'])." &deg;C </td>";
	echo "<td>".$sensor_name;
	echo "</td>";
	echo "</tr>\n";
}
echo "<tr align=center><td colspan=4>Last updated at $updated_string</td></tr>\n";
#echo "<!--"; print_r($data); echo "-->";
?>
		</table>
	</p>
	<p>
	<h1>Outside</h1>
	<div class=outside><?php echo sprintf("%d", round($outside)); ?>&deg;C</div>
	</p>
</body>
</html>
