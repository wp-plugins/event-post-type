<?php

require_once('../../../wp-load.php');

global $wpdb;

/*
note:

the returned xml has the following structure
<?xml version=\"1.0\" encoding=\"utf-8\" ?>
<results>
	<rs id="[ID]" info="[Address], [Town]" address="[Address]" town="[Town]">[Name]</rs>
	<rs id="[ID]" info="[Address], [Town]" address="[Address]" town="[Town]">[Name]</rs>
</results>
*/

$location_table = $wpdb->prefix.EVENT_LOCATION_TABLE;

$aNames = $wpdb->get_col($wpdb->prepare("SELECT event_location_name FROM $location_table ORDER BY event_location_name"));
$aAddresses = $wpdb->get_col($wpdb->prepare("SELECT event_location_address FROM $location_table ORDER BY event_location_name"));
$aCities = $wpdb->get_col($wpdb->prepare("SELECT event_location_town FROM $location_table ORDER BY event_location_name"));

	
$input = strtolower( $_GET['input'] );
$len = strlen($input);
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 0;
	
	
$aResults = array();
$count = 0;
	
if ($len) {
	for ($i=0;$i<count($aNames);$i++) {
		if (strtolower(substr(utf8_decode($aNames[$i]),0,$len)) == $input) {
			$count++;
			$aResults[] = array( "id"=>($i+1) ,"name"=>htmlspecialchars($aNames[$i]), "address"=>htmlspecialchars($aAddresses[$i]), "town"=>htmlspecialchars($aCities[$i]) );
		}
			
		if ($limit && $count==$limit)
				break;
		}
	}
	

	header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	header ("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header ("Pragma: no-cache"); // HTTP/1.0
	header("Content-Type: text/xml");

	echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?><results>";
	for ($i=0;$i<count($aResults);$i++)
		{
			echo "<rs id=\"".$aResults[$i]['id']."\" info=\"".$aResults[$i]['address'].", ".$aResults[$i]['town']."\" address=\"".$aResults[$i]['address']."\" town=\"".$aResults[$i]['town']."\" >".$aResults[$i]['name']."</rs>";
		}
		
	echo "</results>";
?>