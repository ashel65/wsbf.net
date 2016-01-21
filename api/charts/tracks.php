<?php
require_once("../connect.php");

/**
 * Get the most played tracks in a time period. Includes
 * tracks currently in rotation that weren't played by
 * Automation.
 *
 * @param mysqli  MySQL connection
 * @param date1   start timestamp
 * @param date2   end timestamp
 * @param count   number of tracks
 * @return array of top tracks
 */
function get_top_tracks($mysqli, $date1, $date2, $count)
{
	$keys = array(
		"l.lb_track_name",
		"l.lb_artist",
		"l.lb_album",
		"l.lb_album_code",
		"l.lb_rotation",
		"COUNT(*) AS plays"
	);

	$q = "SELECT " . implode(",", $keys) . " FROM `logbook` AS l "
		. "INNER JOIN `show` AS s ON l.showID=s.showID "
		. "WHERE '$date1' < UNIX_TIMESTAMP(s.start_time) "
		. "AND UNIX_TIMESTAMP(s.end_time) < '$date2' "
		. "AND s.show_typeID != 8 "
		. "AND l.lb_rotation IN ('N','H','M','L') "
		. "GROUP BY l.lb_track_name "
		. "ORDER BY plays DESC "
		. "LIMIT $count;";
	$result = $mysqli->query($q);

	$tracks = array();
	while ( ($t = $result->fetch_assoc()) ) {
		$tracks[] = $t;
	}

	return $tracks;
}

$date1 = $_GET["date1"];
$date2 = $_GET["date2"];

if ( !is_numeric($date1) || !is_numeric($date2) ) {
	header("HTTP/1.1 404 Not Found");
	exit("Start and end dates are empty or invalid.");
}

// remove millisecond component used in Javascript
$date1 = $date1 / 1000;
$date2 = $date2 / 1000;

$mysqli = construct_connection();
$tracks = get_top_tracks($mysqli, $date1, $date2, 20);
$mysqli->close();

header("Content-Type: application/json");
exit(json_encode($tracks));
?>
