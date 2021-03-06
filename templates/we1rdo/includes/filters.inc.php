<?php
	// We definieeren hier een aantal settings zodat we niet steeds dezelfde check hoeven uit te voeren
	$count_newspots = ($currentSession['user']['prefs']['count_newspots']);
	$show_multinzb_checkbox = ($currentSession['user']['prefs']['show_multinzb']);
?>
		
			<div id="toolbar">
				<div class="notifications">
					<?php if ($show_multinzb_checkbox) { ?>
					<p class="multinzb"><a class="button" onclick="downloadMultiNZB()" title="<?php echo _('MultiNZB'); ?>"><span class="count"></span></a><a class="clear" onclick="uncheckMultiNZB()" title="<?php echo _('Reset selectie'); ?>">[x]</a></p>
					<?php } ?>
				</div>

				<div class="logininfo"><p><a onclick="toggleSidebarPanel('.userPanel')" title='<?php echo _('Open "Gebruikers Paneel"'); ?>'>
<?php if ($currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) { ?>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '')) { ?>
					Inloggen
	<?php } ?>
<?php } else { ?>
					<?php echo $currentSession['user']['firstname']; ?>
<?php } ?>
				</a></p></div>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_post_spot, '')) {
		if ($currentSession['user']['userid'] > 2) { ?>
				<div class="addspot"><p><a onclick="return openDialog('editdialogdiv', '<?php echo _('Spot toevoegen'); ?>', '<?php echo $tplHelper->getPageUrl('postspot'); ?>', 'newspotform', function() { new spotPosting().postNewSpot(this.form, postSpotUiStart, postSpotUiDone); return false; }, true, null);" title='<?php echo _('Spot toevoegen'); ?>'><?php echo _('Spot toevoegen'); ?></a></p></div>
<?php 	} 
	  }
?>

				<span class="scroll"><input type="checkbox" name="filterscroll" id="filterscroll" value="Scroll" title="<?php echo _('Wissel tussen vaste en meescrollende sidebar'); ?>"><label>&nbsp;</label></span>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_search, '')) { ?>
				<form id="filterform" action="" onsubmit="submitFilterBtn(this)">
				<input type="hidden" id="searchfilter-includeprevfilter-toggle" name="search[includeinfilter]" value="false" />
<?php
	// Omdat we nu op meerdere criteria tegelijkertijd kunnen zoeken is dit onmogelijk
	// om 100% juist in de UI weer te geven. We doen hierdoor een gok die altijd juist
	// is zolang je maar zoekt via de UI.
	// Voor uitgebreide filters tonen we een lijst met op dat moment actieve filters
	$searchType = 'Titel'; 
	$searchText = '';
	
	# Zoek nu een filter op dat eventueel matched, dan gebruiken we die. We willen deze 
	# boom toch doorlopen ook al is er meer dan 1 filter, anders kunnen we de filesize
	# en reportcount niet juist zetten
	foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Poster', 'Tag', 'SpotterID'))) {
			$searchType = $filterType['fieldname'];
			$searchText = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'filesize' && $filterType['operator'] == ">") {
			$minFilesize = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'filesize' && $filterType['operator'] == "<") {
			$maxFilesize = $filterType['value'];
		} elseif ($filterType['fieldname'] == 'reportcount' && $filterType['operator'] == "<=") {
			$maxReportCount = $filterType['value'];
		} # if
	} # foreach

	# Als er een sortering is die we kunnen gebruiken, dan willen we ook dat
	# in de UI weergeven
	$tmpSort = $tplHelper->getActiveSorting();
	$sortType = strtolower($tmpSort['friendlyname']);
	$sortOrder = strtolower($tmpSort['direction']);
	
	/*
	 * Als er geen sorteer volgorde opgegeven is door de user, dan gebruiken we de user
	 * preference om een sorteerveld te pakken
	 */	
	if (empty($sortType)) {
		$sortType = $currentSession['user']['prefs']['defaultsortfield'];
	} # if

	# als er meer dan 1 filter is, dan tonen we dat als een lijst
	if (count($parsedsearch['filterValueList']) > 1) {
		$searchText = '';
		$searchType = 'Titel';
	} # if

	# Zorg er voor dat de huidige filterwaardes nog beschikbaar zijn
	foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Poster', 'Tag', 'SpotterID'))) {
			echo '<input data-currentfilter="true" type="hidden" name="search[value][]" value="' . $filterType['fieldname'] . ':=:'  . htmlspecialchars($filterType['value'], ENT_QUOTES, 'utf-8') . '">';
		} # if
	} # foreach
	
?>
					<div><input type="hidden" id="search-tree" name="search[tree]" value="<?php echo $tplHelper->categoryListToDynatree(); ?>"></div>
<?php
	$filterColCount = 3;
	if ($settings->get('retrieve_full')) {
		$filterColCount++;
	} # if
?>
					<div class="search"><input class='searchbox' type="text" name="search[text]" value="<?php echo htmlspecialchars($searchText); ?>"><input type='submit' class="filtersubmit" value='+' onclick='$("#searchfilter-includeprevfilter-toggle").val("true");' title='<?php echo _('Zoeken in huidige filters'); ?>'><input type='submit' class="filtersubmit default" onclick='$("#searchfilter-includeprevfilter-toggle").val(""); return true;' value='>>' title='<?php echo _('Zoeken'); ?>'></div>

					<div class="sidebarPanel advancedSearch">
					<h4><a class="toggle" onclick="toggleSidebarPanel('.advancedSearch')" title='<?php echo _('Sluit "Advanced Search"'); ?>'>[x]</a><?php echo _('Zoeken op:'); ?></h4>
						<ul class="search<?php if ($filterColCount == 3) {echo " threecol";} else {echo " fourcol";} ?>">
							<li> <input type="radio" name="search[type]" value="Titel" <?php echo $searchType == "Titel" ? 'checked="checked"' : "" ?> ><label><?php echo _('Titel'); ?></label></li>
							<li> <input type="radio" name="search[type]" value="Poster" <?php echo $searchType == "Poster" ? 'checked="checked"' : "" ?> ><label><?php echo _('Poster'); ?></label></li>
							<li> <input type="radio" name="search[type]" value="Tag" <?php echo $searchType == "Tag" ? 'checked="checked"' : "" ?> ><label><?php echo _('Tag'); ?></label></li>
<?php if ($settings->get('retrieve_full')) { ?>
							<li> <input type="radio" name="search[type]" value="SpotterID" <?php echo $searchType == "SpotterID" ? 'checked="checked"' : "" ?> ><label><?php echo _('SpotterID'); ?></label></li>
<?php } ?>
						</ul>

<?php
	if (count($parsedsearch['filterValueList']) > 0) {
?>
						<h4><?php echo _('Actieve filters:'); ?></h4>
						<table class='search currentfilterlist'>
<?php
	foreach($parsedsearch['filterValueList'] as $filterType) {
		if (in_array($filterType['fieldname'], array('Titel', 'Poster', 'Tag', 'SpotterID'))) {
?>
							<tr> <th> <?php echo _($filterType['fieldname']); ?> </th> <td> <?php echo $filterType['value']; ?> </td> <td> <a href="javascript:location.href=removeFilter('?page=index<?php echo addcslashes(urldecode($tplHelper->convertFilterToQueryParams()), "\\\'\"&\n\r<>"); ?>', '<?php echo $filterType['fieldname']; ?>', '<?php echo $filterType['operator']; ?>', '<?php echo addcslashes(htmlspecialchars($filterType['value'], ENT_QUOTES, 'utf-8'), "\\\'\"&\n\r<>"); ?>');">x</a> </td> </tr>
<?php
		} # if
	} # foreach
?>
						</table>
<?php						
	}
?>
						<h4>Sorteren op:</h4>
						<input type="hidden" name="sortdir" value="<?php if($sortType == "stamp" || $sortType == "spotrating" || $sortType == "commentcount") {echo "DESC";} else {echo "ASC";} ?>">
						<ul class="search sorting threecol">
							<li> <input type="radio" name="sortby" value="" <?php echo $sortType == "" ? 'checked="checked"' : "" ?>><label><?php echo _('Relevantie'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="title" <?php echo $sortType == "title" ? 'checked="checked"' : "" ?>><label><?php echo _('Titel'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="poster" <?php echo $sortType == "poster" ? 'checked="checked"' : "" ?>><label><?php echo _('Poster');?></label> </li>
							<li> <input type="radio" name="sortby" value="stamp" <?php echo $sortType == "stamp" ? 'checked="checked"' : "" ?>><label><?php echo _('Datum');?></label> </li>
							<li> <input type="radio" name="sortby" value="commentcount" <?php echo $sortType == "commentcount" ? 'checked="checked"' : "" ?>><label><?php echo _('Comments'); ?></label> </li>
							<li> <input type="radio" name="sortby" value="spotrating" <?php echo $sortType == "spotrating" ? 'checked="checked"' : "" ?>><label><?php echo _('Rating'); ?></label> </li>
						</ul>

						<h4><?php echo _('Leeftijd limiteren'); ?></h4>
						<ul class="search age onecol">
<?php if (!isset($activefilter['filterValues']['date'])) { $activefilter['filterValues']['date'] = ''; } ?>
							<li><select name="search[value][]">
								<option value=""><?php echo _('Alles tonen'); ?></option>
								<option value="date:>:-1 day" <?php echo $activefilter['filterValues']['date'] == ">:-1 day" ? 'selected="selected"' : "" ?>><?php echo _('1 dag'); ?></option>
								<option value="date:>:-3 days" <?php echo $activefilter['filterValues']['date'] == ">:-3 days" ? 'selected="selected""' : "" ?>><?php echo _('3 dagen'); ?></option>
								<option value="date:>:-1 week" <?php echo $activefilter['filterValues']['date'] == ">:-1 week" ? 'selected="selected""' : "" ?>><?php echo _('1 week'); ?></option>
								<option value="date:>:-2 weeks" <?php echo $activefilter['filterValues']['date'] == ">:-2 weeks" ? 'selected="selected"' : "" ?>><?php echo _('2 weken'); ?></option>
								<option value="date:>:-1 month" <?php echo $activefilter['filterValues']['date'] == ">:-1 month" ? 'selected="selected"' : "" ?>><?php echo _('1 maand'); ?></option>
								<option value="date:>:-3 months" <?php echo $activefilter['filterValues']['date'] == ">:-3 months" ? 'selected="selected"' : "" ?>><?php echo _('3 maanden'); ?></option>
								<option value="date:>:-6 months" <?php echo $activefilter['filterValues']['date'] == ">:-6 months" ? 'selected="selected"' : "" ?>><?php echo _('6 maanden'); ?></option>
								<option value="date:>:-1 year" <?php echo $activefilter['filterValues']['date'] == ">:-1 year" ? 'selected="selected"' : "" ?>><?php echo _('1 jaar'); ?></option>
							</select></li>
						</ul>
					
						<h4><?php echo _('Omvang'); ?></h4>
						<input type="hidden" name="search[value][]" id="min-filesize" />
						<input type="hidden" name="search[value][]" id="max-filesize" />
						<div id="human-filesize"></div>
						<div id="slider-filesize"></div>

						<h4><?php echo _('Categori&euml;n'); ?></h4>
						<div id="tree"></div>
						<ul class="search clearCategories onecol">
							<li> <input type="checkbox" name="search[unfiltered]" value="true" <?php echo $parsedsearch['unfiltered'] == "true" ? 'checked="checked"' : '' ?>>
							
							<label><?php if ($parsedsearch['unfiltered'] == 'true') { echo _('Categori&euml;n gebruiken'); } else { echo _('Categori&euml;n niet gebruiken'); } ?></label> </li>
						</ul>

<?php if ($settings->get('retrieve_reports')) { ?>
						<h4><?php echo _('Aantal reports'); ?></h4>
						<input type="hidden" name="search[value][]" id="max-reportcount" />
						<div id="human-reportcount"></div>
						<div id="slider-reportcount"></div>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_filters, '')) { ?>
						<br>
						<h4><?php echo _('Filters'); ?></h4>
						<br>
						<a onclick="return openDialog('editdialogdiv', '<?php echo _('Voeg een filter toe'); ?>', '?page=render&amp;tplname=editfilter&amp;data[isnew]=true<?php echo $tplHelper->convertTreeFilterToQueryParams() .$tplHelper->convertTextFilterToQueryParams() . $tplHelper->convertSortToQueryParams(); ?>', 'editfilterform', null, true, null); " class="greyButton"><?php echo _('Sla opdracht op als filter'); ?></a>
<?php } ?>
				</div>
			</form>
<?php } # if perform search ?>

				<div class="sidebarPanel userPanel">
					<h4><a class="toggle" onclick="toggleSidebarPanel('.userPanel')" title='<?php echo _('Sluit "Gebruikers paneel"'); ?>'>[x]</a><?php echo _('Gebruikers paneel'); ?></h4>
					<ul class="userInfo">
<?php if ($currentSession['user']['userid'] == SPOTWEB_ANONYMOUS_USERID) { ?>
						<li><?php echo _('U bent niet ingelogd'); ?></li>
<?php } else { ?>
						<li><?php echo _("Gebruiker:") . " <strong>" . $currentSession['user']['firstname'] . " " . $currentSession['user']['lastname'] . "</strong>"; ?></li>
						<li><?php echo sprintf(_("Laatst gezien: %s"), "<strong>" . $tplHelper->formatDate($currentSession['user']['lastvisit'], 'lastvisit') . " geleden</strong>"); ?></li>
<?php } ?>
					</ul>

<?php if ($tplHelper->allowed(SpotSecurity::spotsec_create_new_user, '')) { ?>
					<a class="viewState" onclick="toggleCreateUser()"><h4><?php echo _('Gebruiker toevoegen'); ?><span class="createUser down"></span></h4></a>
					<div class="createUser"></div>
<?php } ?>

<?php if ($currentSession['user']['userid'] != SPOTWEB_ANONYMOUS_USERID) { ?>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_own_user, '')) { ?>
					<a class="viewState" onclick="toggleEditUser('<?php echo $currentSession['user']['userid'] ?>')"><h4><?php echo _('Gebruiker wijzigen'); ?><span class="editUser down"></span></h4></a>
					<div class="editUser"></div>
	<?php } ?>

	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_edit_own_userprefs, '')) { ?>
					<h4 class="dropdown"><a class="editUserPrefs down" href="?page=edituserprefs"><?php echo _('Voorkeuren wijzigen'); ?></a></h4>
					<div class="editUserPrefs"></div>
	<?php } ?>

<?php if (
			($tplHelper->allowed(SpotSecurity::spotsec_edit_other_users, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_edit_securitygroups, ''))
				|| 
			($tplHelper->allowed(SpotSecurity::spotsec_list_all_users, ''))
		 ) { ?>
					<h4 class="dropdown"><a class="listUsers down" href="?page=render&amp;tplname=adminpanel"><?php echo _('Admin panel'); ?></a></h4>
					<div class="listUsers"></div>
<?php } ?>
					
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_logout, '')) { ?>
					<h4 class="dropdown"><?php echo _('Uitloggen'); ?></h4>
					<a onclick="userLogout()" class="greyButton"><?php echo _('Uitloggen'); ?></a>
	<?php } ?>
<?php } else { ?>
	<?php if ($tplHelper->allowed(SpotSecurity::spotsec_perform_login, '')) { ?>
					<h4><?php echo _('Inloggen'); ?></h4>
					<div class="login"></div>
	<?php } ?>
<?php } ?>
				</div>

				<div class="sidebarPanel sabnzbdPanel">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_use_sabapi, '')) { ?>
					<h4><a class="toggle" onclick="toggleSidebarPanel('.sabnzbdPanel')" title='<?php echo _('Sluit "' . $tplHelper->getNzbHandlerName() . 'paneel"'); ?>'>[x]</a><?php echo $tplHelper->getNzbHandlerName(); ?></h4>
<?php 
		$apikey = $tplHelper->apiToHash($currentSession['user']['apikey']);
		echo "<input class='apikey' type='hidden' value='".$apikey."'>";
		if ($tplHelper->getNzbHandlerApiSupport() === false)
		{?>
					<table class="sabInfo" summary="SABnzbd infomatie">
						<tr><td><?php echo _('De geselecteerde methode om NZB\'s te downloaden heeft geen panel support.'); ?></td></tr>
					</table>			
<?php	}
		else
		{
?>					<table class="sabInfo" summary="SABnzbd infomatie">
						<tr><td><?php echo _('Status:'); ?></td><td class="state"></td></tr>
						<tr><td><?php echo _('Opslag (vrij):'); ?></td><td class="diskspace"></td></tr>
						<tr><td><?php echo _('Snelheid:'); ?></td><td class="speed"></td></tr>
						<tr><td><?php echo _('Max. snelheid:'); ?></td><td class="speedlimit"></td></tr>
						<tr><td><?php echo _('Te gaan:'); ?></td><td class="timeleft"></td></tr>
						<tr><td><?php echo _('ETA:'); ?></td><td class="eta"></td></tr>
						<tr><td><?php echo _('Wachtrij:'); ?></td><td class="mb"></td></tr>
					</table>
					<canvas id="graph" width="215" height="125"></canvas>
					<table class="sabGraphData" summary="SABnzbd Graph Data" style="display:none;"><tbody><tr><td></td></tr></tbody></table>
					<h4><?php echo _('Wachtrij'); ?></h4>
					<table class="sabQueue" summary="SABnzbd queue"><tbody><tr><td></td></tr></tbody></table>
<?php 	}
	  } ?>
				</div>
			</div>

			<div id="filter" class="filter">
				<a class="viewState" onclick="toggleSidebarItem(this)"><h4><?php echo _('Quick Links'); ?><span></span></h4></a>
				<ul class="filterlist quicklinks">
<?php foreach($quicklinks as $quicklink) {
		if ($tplHelper->allowed($quicklink[4][0], $quicklink[4][1])) {
			$newCount = ($count_newspots && stripos($quicklink[2], 'New:0')) ? $tplHelper->getNewCountForFilter($quicklink[2]) : "";
?>
					<li> <a class="filter <?php echo " " . $quicklink[3]; if (parse_url($tplHelper->makeSelfUrl("full"), PHP_URL_QUERY) == parse_url($tplHelper->makeBaseUrl("full") . $quicklink[2], PHP_URL_QUERY)) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
					<a class="filter <?php if (parse_url($tplHelper->makeSelfUrl("full"), PHP_URL_QUERY) == parse_url($tplHelper->makeBaseUrl("full") . $quicklink[2], PHP_URL_QUERY)) { echo " selected"; } ?>" href="<?php echo $quicklink[2]; ?>">
					<span class='spoticon spoticon-<?php echo str_replace('images/icons/', '', str_replace('.png', '', $quicklink[1])); ?>'>&nbsp;</span><?php echo $quicklink[0]; if ($newCount) { echo "<span class='newspots'>".$newCount."</span>"; } ?></a>
<?php 	}
	} ?>
					</ul>

					<a class="viewState" onclick="toggleSidebarItem(this)"><h4><?php echo _('Filters'); ?><span></span></h4></a>
					<ul class="filterlist filters">

<?php
	function processFilters($tplHelper, $count_newspots, $filterList) {
		$selfUrl = $tplHelper->makeSelfUrl("path");

		foreach($filterList as $filter) {
			$strFilter = $tplHelper->getPageUrl('index') . '&amp;search[tree]=' . $filter['tree'];
			if (!empty($filter['valuelist'])) {
				foreach($filter['valuelist'] as $value) {
					$strFilter .= '&amp;search[value][]=' . $value;
				} # foreach
			} # if
			if (!empty($filter['sorton'])) {
				$strFilter .= '&amp;sortby=' . $filter['sorton'] . '&amp;sortdir=' . $filter['sortorder'];
			} # if
			$newCount = ($count_newspots) ? $tplHelper->getNewCountForFilter($strFilter) : "";

			# escape the filter vlaues
			$filter['title'] = htmlentities($filter['title'], ENT_NOQUOTES, 'UTF-8');
			$filter['icon'] = htmlentities($filter['icon'], ENT_NOQUOTES, 'UTF-8');
			
			# Output de HTML
			echo '<li class="'. $tplHelper->filter2cat($filter['tree']) .'">';
			echo '<a class="filter ' . $filter['title'];
			
			if ($selfUrl == $strFilter) { 
				echo ' selected';
			} # if
			
			echo '" href="' . $strFilter . '">';
			echo '<span class="spoticon spoticon-' . str_replace('.png', '', $filter['icon']) . '">&nbsp;</span>' . $filter['title'];
			if ($newCount) { 
				echo "<span onclick=\"gotoNew('".$strFilter."')\" class='newspots' title='" . sprintf(_('Laat nieuwe spots in filter &quot;%s&quot; zien'), $filter['title']) . "'>$newCount</span>";
			} # if 

			# als er children zijn, moeten we de category kunnen inklappen
			if (!empty($filter['children'])) {
				echo '<span class="toggle" title="' . _('Filter inklappen') . '" onclick="toggleFilter(this)">&nbsp;</span>';
			} # if
			
			echo '</a>';
			
			# Als er children zijn, output die ook
			if (!empty($filter['children'])) {
				echo '<ul class="filterlist subfilterlist">';
				processFilters($tplHelper, $count_newspots, $filter['children']);
				echo '</ul>';
			} # if
			
			echo '</li>' . PHP_EOL;
		} # foreach
	} # processFilters
	
	processFilters($tplHelper, $count_newspots, $filters);
?>
					</ul>

					<a class="viewState" onclick="toggleSidebarItem(this)"><h4>Onderhoud<span></span></h4></a>
					<ul class="filterlist maintenancebox">
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_view_spotcount_total, '')) { ?>
						<li class="info"> <?php echo _('Laatste update:'); ?> <?php echo $tplHelper->formatDate($tplHelper->getLastSpotUpdates(), 'lastupdate'); ?> </li>
<?php } ?>
<?php 
		if ($currentSession['user']['userid'] > SPOTWEB_ADMIN_USERID) {
			if ( ($tplHelper->allowed(SpotSecurity::spotsec_retrieve_spots, '')) && ($tplHelper->allowed(SpotSecurity::spotsec_consume_api, ''))) { ?>
						<li><a href="<?php echo $tplHelper->makeRetrieveUrl(); ?>" onclick="retrieveSpots()" class="greyButton retrievespots"><?php echo _('Update Spots'); ?></a></li>
<?php 		}
		} ?>
<?php if (($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, '')) && ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_downloadlist, 'erasedls'))) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('erasedls'); ?>" onclick="eraseDownloads()" class="greyButton erasedownloads"><?php echo _('Verwijder downloadgeschiedenis'); ?></a></li>
<?php } ?>
<?php if ($tplHelper->allowed(SpotSecurity::spotsec_keep_own_seenlist, '')) { ?>
						<li><a href="<?php echo $tplHelper->getPageUrl('markallasread'); ?>" onclick="markAsRead()" class="greyButton markasread"><?php echo _('Markeer alles als gelezen'); ?></a></li>
<?php } ?>
					</ul>
				</div>

	<script>
	$(function() {
		$( "#slider-filesize" ).slider({
			range: true,
			min: 0,
			max: 375809638400,
			step: 1048576,
			values: [ <?php echo (isset($minFilesize)) ? $minFilesize : "0"; ?>, <?php echo (isset($maxFilesize)) ? $maxFilesize : "375809638400"; ?> ],
			slide: function( event, ui ) {
				$( "#min-filesize" ).val( "filesize:>:" + ui.values[ 0 ] );
				$( "#max-filesize" ).val( "filesize:<:" + ui.values[ 1 ] );
				$( "#human-filesize" ).text( "Tussen " + format_size( ui.values[ 0 ] ) + " en " + format_size( ui.values[ 1 ] ) );
			}
		});
		
		$( "#slider-reportcount" ).slider({
			range: 'max',
			min: 0,
			max: 21,
			step: 1,
			values: [ <?php echo (isset($maxReportCount)) ? $maxReportCount : "21"; ?> ],
			slide: function( event, ui ) {
				$( "#max-reportcount" ).val( "reportcount:<=:" + ui.values[0]);

				if (ui.values[0] == 21) {
					/* In de submit handler wordt 21 gefiltered */
					$( "#human-reportcount" ).text( "<?php echo _('Niet filteren op aantal reports'); ?>" );
				} else {
					$( "#human-reportcount" ).text( "<?php echo _('Maximaal %1 reports'); ?>".replace("%1", ui.values[0]) );
				} // if
			}
		});

		/* Filesizes */
		$( "#min-filesize" ).val( "filesize:>:" + $( "#slider-filesize" ).slider( "values", 0 ) );
		$( "#max-filesize" ).val( "filesize:<:" + $( "#slider-filesize" ).slider( "values", 1 ) );
		$( "#human-filesize" ).text( "Tussen " + format_size( $( "#slider-filesize" ).slider( "values", 0 ) ) + " en " + format_size( $( "#slider-filesize" ).slider( "values", 1 ) ) );
		
		/* Report counts */
		var reportSlideValue = $( "#slider-reportcount" ).slider("values", 0);
		$( "#max-reportcount" ).val( "reportcount:<=:" + reportSlideValue);
		if (reportSlideValue == 21) {
			$( "#human-reportcount" ).text("<?php echo _('Niet filteren op aantal reports'); ?>");
		} else {
			$( "#human-reportcount" ).text( "<?php echo _('Maximaal %1 reports'); ?>".replace("%1", reportSlideValue));
		} // if
	});
	</script>
