<?php
	require_once (realpath(dirname(__FILE__) . '/../../includes/common_func.php'));	
	require_once (realpath(dirname(__FILE__) . "/../../includes/session.php"));
	$res = initialize_session();
	$encodeOn = $res['encodeOn'];
	$in_debug = $res['in_debug'];
	$genemoOn = $res['genemoOn'];
	unset($res);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<title>Main Content</title>
<link href="mainstyles.css" rel="stylesheet" type="text/css" />
</head>
<?php
	if(empty($_REQUEST)) {
		// new page, show something else
		if(!$genemoOn) {
			require 'introduction.php';
		}
	} else {
		$num_spc = $_REQUEST["num_spc"];
?>
<script type="text/javascript" src="js/jquery-1.7.js"></script>
<script language="javascript">

//var spcReady = new Array();
var spcNum = <?php echo $num_spc; ?>;
var spcNumVisible = spcNum;	// number of species that have their panel expanded
var spcDbName = new Array();
var spcCmnName = new Object();
var spcName = new Object();

var cmnTracks = new Array();			// this is the array placing the common tracks
var cmnTracksStatus = new Array();			// this is the array placing the common tracks

var cmnTracksTableNames = new Object();	
// this is the "associative array" linking compSeries name to 
// an "associative array" of db/table names sent to download

var uniTracks = new Array();			// this is an array of arrays placing the unique tracks
var uniTracksStatus = new Array();			// this is an array of arrays placing the unique tracks
var uniTracksTableNames = new Array();

var tracksInitialized = false;

var listPanels = new Array('trackSettings', 'tableBrowser');

var isInDownload = false;


function resize_tbody() {
	
	if (document.getElementById("internal_table")) {
		$('#internal_table').height($(window).height());
		for(var i = 0; i < spcNum; i++) {
			$('#' + spcDbName[i]).height(($(window).height() - 20 * spcNum) / spcNumVisible);
		}
	}
	$('#trackSettings').height($(window).height() - 50); 
	$('#trackSettings').width($(window).width() - 50); 
	$('#trackSettingFrame').height($('#trackSettings').height() - 100); 
	$('#trackSettingFrame').width($('#trackSettings').width() - 10); 
	$('#tableBrowser').height($(window).height() - 50); 
	$('#tableBrowser').width($(window).width() - 50); 
	$('#tableBrowserFrame').height($('#tableBrowser').height() - 100); 
	$('#tableBrowserFrame').width($('#tableBrowser').width() - 10); 
}

function callViewChange(db, change) {
	var conDoc = (document.getElementById(db).contentWindow || document.getElementById(db).contentDocument);
	if(conDoc.document) {
		conDoc = conDoc.document;
	}
	conDoc.getElementById("hgt." + change).click();
}

function changeSettings(db, settings, val) {
	var conDoc = (document.getElementById(db).contentWindow || document.getElementById(db).contentDocument);
	if(conDoc.document) {
		conDoc = conDoc.document;
	}
	conDoc.getElementById(settings).value = val;
}

function markTrackInitialized(flag) {
	tracksInitialized = flag;
	if(tracksInitialized) {
		$('#trackSelectLoading').addClass('trackSelectHide');
	} else {
		$('#trackSelectLoading').removeClass('trackSelectHide');
	}
}

function setReady(index) {
	//spcReady[i] = true;
	var db = spcDbName[index];
	var conDoc = (document.getElementById(db).contentWindow || document.getElementById(db).contentDocument);
	if(conDoc.document) {
		conDoc = conDoc.document;
	}
	parent.setReady(db, conDoc.getElementById("positionHidden").value);
}

function callDownloadMenu(cmnName, isCommon, btnID) {
	var btnPos = $('#' + btnID).offset();
	var btnWidth = $('#' + btnID).width();
	var btnHeight = $('#' + btnID).height();
	$('#downloadBox').css({left: btnPos.left - $('#downloadBox').width() + btnWidth,
		top: btnPos.top + btnHeight});
	$('#downloadContent').html('<em>Loading...</em>');
	$('#downloadBox').show();
	if(isCommon) {
		// This comes from common, send the whole associative array to download page
		$.getJSON('getdownload.php', cmnTracksTableNames[cmnName], function(data) {
			// The return will have basically one key (spcDbName+'__'+tableName), 
			// and one value (shortLabel + '||' + type + '||' + longLabel) to display
			// no super track will be returned (will be filtered by jsondownload.php)
			// also returns will be ordered by species for grouping
			var currentDb = "";
			var items = [];
			$.each(data, function(key, val) {
				var db = key.split("__")[0];
				if(currentDb != db) {
					// db has changed
					items.push("<div class='speciesTrackHeader'>" + spcCmnName[db] + "</div>");
					currentDb = db;
				}
				// split the value into shortlabel, type, and long label
				values = val.split("||");
				// put the short label into display and key in the link
				items.push("<div style='padding: 0px 8px;'><a class='downloadFile' href='download.php?file="
					+ key + "' title='"
					+ values[2] + "'>" 
					+ values[0] + "</a> <div class='downloadType'>"
					+ values[1] + "</div></div>");
			});
			$('#downloadContent').html(items.join(''));
		});
	} else {
		var uniIDNames = cmnName.split("__");
		var jsondata = new Object();
		jsondata[uniIDNames[0]] = uniIDNames[1];
		$.getJSON('getdownload.php', jsondata, function(data) {
			// The return will have basically one key (spcDbName+'__'+tableName), 
			// and one value (shortLabel + '||' + type + '||' + longLabel) to display
			// no super track will be returned (will be filtered by jsondownload.php)
			// also returns will be ordered by species for grouping
			var items = [];
			$.each(data, function(key, val) {
				// split the value into shortlabel, type, and long label
				values = val.split("||");
				// put the short label into display and key in the link
				items.push("<div style='padding: 0px 4px;'><a class='downloadFile' href='download.php?file="
					+ key + "' title='"
					+ values[2] + "'>" 
					+ values[0] + "</a> <div class='downloadType'>"
					+ values[1] + "</div></div>");
			});
			$('#downloadContent').html(items.join(''));
		});
	}
	return false;
}

function inDownload(flag) {
	isInDownload = flag;
}

function hideDownload() {
	$('#downloadBox').hide();
}

function toggleHeaderText(header) {
	if($('#' + header).html() == '≪') {
		$('#' + header).html('≫');
	} else {
		$('#' + header).html('≪');
	}
}

function hideHeaderText(header) {
	$('#' + header).html('≪');
}

function showHeaderText(header) {
	$('#' + header).html('≫');
}

function hidePanel(panel) {
	$('#' + panel).fadeOut('fast', hideHeaderText(panel + 'Indicator'));
	hideDownload();
}

function trackSettingSubmit(db) {
	parent.setUnReady(db);
	hidePanel('trackSettings');
}

function showPanel(panel) {
	$('#' + panel).fadeIn('fast', showHeaderText(panel + 'Indicator'));
}

function togglePanel(panel) {
	for(var i = 0; i < listPanels.length; i++) {
		if(listPanels[i] == panel) {
			continue;
		}
		hidePanel(listPanels[i]);
	}
	hideDownload();
	$('#' + panel).fadeToggle('fast', toggleHeaderText(panel + 'Indicator'));
}

function resetTracks() {
	for(var index = 0; index < spcDbName.length; index++) {
		var db = spcDbName[index];
		var conDoc = (document.getElementById(db).contentWindow || document.getElementById(db).contentDocument);
		if(conDoc.document) {
				conDoc = conDoc.document;
		}
		var conForm = conDoc.getElementById('TrackForm');
		var resetVar = conDoc.createElement("input");
		resetVar.type = "hidden";
		resetVar.name = "hgt.reset";
		resetVar.value = "TRUE";
		conForm.appendChild(resetVar);
		var resetOrder = conDoc.createElement("input");
		resetOrder.type = "hidden";
		resetOrder.name = "hgt.defaultImgOrder";
		resetOrder.value = "TRUE";
		conForm.appendChild(resetOrder);
		conForm.submit();
		parent.setUnReady(db);
	}
	markTrackInitialized(false);
	togglePanel('trackSelect');

}

function trackSettingsOnLoad() {
	if(document.getElementById('trackSettingFrame').contentWindow.location.href != "about:blank" && !$('#trackSettings').is(":visible")) {
		togglePanel('trackSettings');
	}
}

function toggleSpcVisible(db) {
	if($('#' + db).is(":visible") && spcNumVisible <= 1) {
		// only one species
		return;
	}
	spcNumVisible += ($('#' + db).is(":visible")? -1: 1);
	$('#' + db).toggle();
	$('#' + db + 'Indicator').html($('#' + db).is(":visible")? '[-]': '[+]');
	resize_tbody();
}

$(document).ready( function () {
	resize_tbody();
});
</script>
<style type="text/css">
<!--
body {
	overflow: hidden;
}
-->
</style>
<body onresize="resize_tbody();">
<?php include_once(realpath(dirname(__FILE__) . '/../../includes/analyticstracking.php')); ?>
<div style="position: absolute; top: 0px; bottom: 0px; right: 0px; width: 22px; padding: 0px; 
	font-family:Verdana, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: bold;
    background: #999999; color: #FFFFFF;">
<!-- Move this to index.php 
  <div class="header" style="height: 170px; float: right;" onclick="togglePanel('trackSelect');"> <span id="trackSelectIndicator">≪</span>
    <div class="rotated" style="width: 150px;">Tracks &amp; Data</div>
  </div>
  <div style="clear: both; height: 5px;"></div>
-->
  <div class="header" style="height: 200px; float: right; display: none;" onclick="togglePanel('trackSettings');"> <span id="trackSettingsIndicator">≪</span>
    <div class="rotated">Track Info &amp; Settings</div>
  </div>
  <div class="header" style="height: 160px; float: right; width: 12px;" onclick="togglePanel('tableBrowser');"> <span id="tableBrowserIndicator">≪</span>
    <div class="rotated" style="width: 140px;">Table Browser</div>
  </div>
</div>
<!-- Move this to index.php 
<div id="trackSelect" class="trackSelectClass">
  <div class="loadingCover" id="trackSelectLoading">
    <div class="loadingCoverBG"></div>
    <div class="loadingCoverImage"></div>
  </div>
  <div class="headerNoHover">Tracks &amp; Data</div>
  <div class="settingsNormal"> Tracks can be turn on/off via the checkboxes below: </div>
  <div class="subBox">
    <div class="subHeader" onclick="toggleSubPanel('cmnTrack', false);"><span class="headerIndicator" id="cmnTrackIndicator">[-]</span> Common tracks</div>
    <div class="trackHolder" id="cmnTrackHolder"></div>
  </div>
  <div class="subBox">
    <div class="subHeader" onclick="toggleSubPanel('unique', false);"><span class="headerIndicator" id="uniqueIndicator">[-]</span> Unique tracks</div>
    <div id="uniqueHolder"></div>
  </div>
  <div class="header buttons" style="float: right;" onclick="updateTracks();">Update &amp; close ≫</div>
  <div class="header buttons" style="float: right;" onclick="resetTracks();">Reset view ≫</div>
  <div style="clear: both"></div>
</div>
<div id="downloadBox" onmouseover="inDownload(true);" onmouseout="inDownload(false);">
  <div class="subHeaderNoHover">Download Data
    <div class="header buttons" style="float: right; padding: 2px 3px; margin: -2px;" onclick="hideDownload();">Close</div>
    <div style="clear: both;"></div>
  </div>
  <div id="downloadContent" style="padding: 4px;"></div>
</div>
<?php
		if(!isset($_COOKIE['NoTipTrackSettings']) || $_COOKIE['NoTipTrackSettings'] != 'true') {
?>
<script type="text/javascript">
function doNotShow() {
	$.post('postcookie.php', { varName: 'NoTipTrackSettings', value: 'true' } );
	$('#trackSelectHint').fadeOut('fast');
}
setTimeout("$('#trackSelectHint').fadeOut('fast')", 7500);
</script>
<div id="trackSelectHint" style="z-index: 20; width: 250px; display: block; padding: 5px; font-family: Verdana, Arial, Helvetica, sans-serif;
font-size: 12px; line-height: 17px; background: #FFFFCC;" class="trackSelectClass"> Hint: tracks can be turned on/off via the <span class="panel">track selection</span> panel; click button on the right to show. --&gt;
  <div class="header buttons" style="float: right; margin-top: 5px;" onclick="doNotShow();">Do not show in the future</div>
  <div style="clear: both"></div>
</div>
<?php
		}
?>
<div style="display: none;" id="uniqueTemplate">
  <div class="speciesTrackHeader">spcCmnName</div>
  <div class="trackHolder" id="spcDbNameHolder"></div>
</div>
-->
<div id="trackSettings" class="trackSettingsClass" style="display: none;">
  <div id="trackSettingsHeader" class="headerNoHover2">Track information &amp; settings</div>
  <div style="position: absolute; top: 45px; left: 0px;">
    <iframe onload="trackSettingsOnLoad();" id="trackSettingFrame" name="trackSettingFrame" class="trackSettingFrame" src="about:blank">Your browser doesn't support &lt;iframe&gt; tag. You need a browser supporting &lt;iframe&gt; tag to use Comparison Browser. (Latest versions of mainstream browsers should all support this tag.)</iframe>
    <div class="header buttons" style="float: right; width: 150px;" onclick="hidePanel('trackSettings');">Close</div>
    <div style="clear: both"></div>
  </div>
</div>
<div id="tableBrowser" class="trackSettingsClass" style="display: none; z-index: 30;">
  <div id="tableBrowserHeader" class="headerNoHover2">Data Download &amp; More</div>
  <div style="position: absolute; top: 45px; left: 0px;">
    <iframe id="tableBrowserFrame" name="tableBrowserFrame" class="trackSettingFrame" src="/cgi-bin/hgTables">Your browser doesn't support &lt;iframe&gt; tag. You need a browser supporting &lt;iframe&gt; tag to use Comparison Browser. (Latest versions of mainstream browsers should all support this tag.)</iframe>
    <div class="header buttons" style="float: right; width: 150px;" onclick="hidePanel('tableBrowser');">Close</div>
    <div style="clear: both"></div>
  </div>
</div>
<div style="position: absolute; top: 0px; bottom: 0px; left: 0px; right: 25px; overflow: hidden;">
  <table width="100%" border="0" bgcolor="#999999" id="internal_table" cellpadding="0" cellspacing="0">
    <?php
		// TODO: may need to do something about the species here
		for($i = 0; $i < $num_spc; $i++) {
	?>
    <script type="text/javascript">
  	//spcReady.push(false);
	spcDbName.push("<?php echo $_REQUEST["speciesdb"][$i]; ?>");
	spcCmnName["<?php echo $_REQUEST["speciesdb"][$i]; ?>"] = "<?php echo $_REQUEST["speciescmnname"][$i]; ?>";
	spcName["<?php echo $_REQUEST["speciesdb"][$i]; ?>"] = "<?php echo $_REQUEST["speciesname"][$i]; ?>";
	uniTracks.push(false);
  </script>
    <tr>
      <td class="tableHeader speciesTableHeader" 
        onclick="toggleSpcVisible('<?php echo $_REQUEST["speciesdb"][$i]; ?>');"><!-- Insert Species 1 Name here --> 
        <span class="headerIndicator" id="<?php echo $_REQUEST["speciesdb"][$i]; ?>Indicator">[-]</span> <em><?php echo $_REQUEST["speciesname"][$i]; ?></em> (<?php echo $_REQUEST["speciescmnname"][$i]; ?>)
        [Database: <?php echo $_REQUEST["speciesdb"][$i]; ?>] </td>
    </tr>
    <tr>
      <td><iframe onload="setReady(<?php echo $i; ?>);" id="<?php echo $_REQUEST["speciesdb"][$i]; ?>" 
         name="<?php echo $_REQUEST["speciesdb"][$i]; ?>" class="cpbrowserFrame"
         src="<?php 
	  echo "/cgi-bin/hgTracks?clade=mammal&org=" . $_REQUEST["speciescmnname"][$i] . "&db=" . $_REQUEST["speciesdb"][$i] . "&position=" . urlencode($_REQUEST[$_REQUEST["speciesdb"][$i]]) . "&pix=850&guidelines=off&Submit=submit&hgControlOnly=off&hgsid=" . requestSpeciesHgsID($_REQUEST["speciesdb"][$i]) . '&showEncode=' . ($encodeOn? 'on': 'off') . ($num_spc <= 1? '&multishade=hide': '&multishade=dense');  ?>">Your browser doesn't support &lt;iframe&gt; tag. You need a browser supporting &lt;iframe&gt; tag to use Comparison Browser. (Latest versions of mainstream browsers should all support this tag.)</iframe></td>
    </tr>
    <?php
  		}
	?>
  </table>
</div>
<?php
	}
?>
</body>
</html>
