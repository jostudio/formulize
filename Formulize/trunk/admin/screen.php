<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

// this file gets all the data about applications, so we can display the Settings/forms/relationships tabs for applications

include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

// need to listen for $_GET['aid'] later so we can limit this to just the application that is requested
$aid = intval($_GET['aid']);
$appName = "All forms"; // needs to be set based on aid in future
$elements = array();
if($_GET['fid'] != "new") {
  $fid = intval($_GET['fid']);
  $form_handler = xoops_getmodulehandler('forms', 'formulize');
  $formObject = $form_handler->get($fid);
  $formName = $formObject->getVar('title');
  $singleentry = $formObject->getVar('single');
  //$screen_handler = xoops_getmodulehandler('screen', 'formulize');
} else {
  $fid = $_GET['fid'];
}


$sid = $_GET['sid'];


$links = array();
$sql = 'SELECT DISTINCT frameworks.frame_id, frameworks.frame_name FROM '.$xoopsDB->prefix("formulize_framework_links").' as links, '.$xoopsDB->prefix("formulize_frameworks").' as frameworks WHERE fl_form1_id='.intval($fid).' OR fl_form2_id='.intval($fid).' AND links.fl_frame_id=frameworks.frame_id';
$res = $xoopsDB->query($sql);
if ($res) {
  while ($row = mysql_fetch_row($res)) {
    $links[] = array("id"=>$row[0], "name"=>$row[1]);
  }
}


// common values should be assigned to all tabs
$common['name'] = $screenName;
$common['sid'] = $sid;
$common['fid'] = $fid;

// screen settings data
$settings = array();
$settings['links'] = $links;

if($_GET['sid'] == "new") {
  $settings['title'] = '';
  $settings['type'] = 'listOfEntries';
  $settings['frid'] = 0;
  $settings['useToken'] = 1;
} else {
  $screen_handler = xoops_getmodulehandler('screen', 'formulize');
  $screen = $screen_handler->get($sid);
  $settings['type'] = $screen->getVar('type');
  $settings['frid'] = $screen->getVar('frid');
  $settings['useToken'] = $screen->getVar('useToken');

  if($settings['type'] == 'listOfEntries') {
    $screen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
  } else if($settings['type'] == 'form') {
    $screen_handler = xoops_getmodulehandler('formScreen', 'formulize');
  } else if($settings['type'] == 'multiPage') {
    $screen_handler = xoops_getmodulehandler('multiPageScreen', 'formulize');
  }

  $screen = $screen_handler->get($sid);

  $common['title'] = $screen->getVar('title');
}


// prepare data for sub-page
if($_GET['sid'] != "new" && $settings['type'] == 'listOfEntries') {
  // display data
  $display = array();
  $display['toptemplate'] = $screen->getVar('toptemplate');
  $display['bottomtemplate'] = $screen->getVar('bottomtemplate');
  $display['listtemplate'] = $screen->getVar('listtemplate');

  // view data
  // gather all the available views
  // setup an option list of all views, as well as one just for the currently selected Framework setting
  $framework_handler =& xoops_getmodulehandler('frameworks', 'formulize');
  $form_handler =& xoops_getmodulehandler('forms', 'formulize');
  $formObj = $form_handler->get($fid, true); // true causes all elements to be included even if they're not visible.
  $frameworks = $framework_handler->getFrameworksByForm($fid);
  $selectedFramework = $settings['frid'];
  $views = $formObj->getVar('views');
  $viewNames = $formObj->getVar('viewNames');
  $viewFrids = $formObj->getVar('viewFrids');
  $viewPublished = $formObj->getVar('viewPublished');
  $defaultViewOptions = array();
  $limitViewOptions = array();
  $defaultViewOptions['blank'] = _AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW;
  $defaultViewOptions['mine'] = _AM_FORMULIZE_SCREEN_LOE_DVMINE;
  $defaultViewOptions['group'] = _AM_FORMULIZE_SCREEN_LOE_DVGROUP;
  $defaultViewOptions['all'] = _AM_FORMULIZE_SCREEN_LOE_DVALL;
  for($i=0;$i<count($views);$i++) {
      if(!$viewPublished[$i]) { continue; }
      $defaultViewOptions[$views[$i]] = $viewNames[$i];
      if($viewFrids[$i]) {
          $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME . $frameworks[$viewFrids[$i]]->getVar('name') . ")";
      } else {
          $defaultViewOptions[$views[$i]] .= " (" . _AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME . ")";
      }
  }
  $limitViewOptions['allviews'] = _AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT;
  $limitViewOptions += $defaultViewOptions;
  unset($limitViewOptions['blank']);
  // get the available screens
  $screen_handler = xoops_getmodulehandler('screen', 'formulize');
  $viewentryscreenOptionsDB = $screen_handler->getObjects(new Criteria("type", "multiPage"), $fid); 
  $viewentryscreenOptions["none"] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT;
  foreach($viewentryscreenOptionsDB as $thisViewEntryScreenOption) {
      $viewentryscreenOptions[$thisViewEntryScreenOption->getVar('sid')] = printSmart(trans($thisViewEntryScreenOption->getVar('title')), 100);
  }
	// get all the pageworks page IDs and include them too with a special prefix that will be picked up when this screen is rendered, so we don't confuse "view entry screens" and "view entry pageworks pages" -- added by jwe April 16 2009
	if(file_exists(XOOPS_ROOT_PATH."/modules/pageworks/index.php")) {
			global $xoopsDB;
			$pageworksSQL = "SELECT page_id, page_name, page_title FROM ".$xoopsDB->prefix("pageworks_pages")." ORDER BY page_name, page_title, page_id";
			$pageworksResult = $xoopsDB->query($pageworksSQL);
			while($pageworksArray = $xoopsDB->fetchArray($pageworksResult)) {
					$pageworksName = $pageworksArray['page_name'] ? $pageworksArray['page_name'] : $pageworksArray['page_title'];
					$viewentryscreenOptions["p".$pageworksArray['page_id']] = _AM_FORMULIZE_SCREEN_LOE_VIEWENTRYPAGEWORKS . " -- " . printSmart(trans($pageworksName), 85);
			}
	}
  // create the template information
  $view = array();
  $view['defaultviewoptions'] = $defaultViewOptions;
  $view['defaultview'] = $screen->getVar('defaultview');
  $view['usecurrentviewlist'] = $screen->getVar('usecurrentviewlist');
  $view['limitviewoptions'] = $limitViewOptions;
  $view['limitviews'] = $screen->getVar('limitviews');
  $view['useworkingmsg'] = $screen->getVar('useworkingmsg');
  $view['usescrollbox'] = $screen->getVar('usescrollbox');
  $view['entriesperpage'] = $screen->getVar('entriesperpage');
  $view['viewentryscreenoptions'] = $viewentryscreenOptions;
  $view['viewentryscreen'] = $screen->getVar('viewentryscreen');

  // headings data
  //set options for all elements in entire framework
  //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
  if($selectedFramework) {
      $allFids = $frameworks[$selectedFramework]->getVar('fids');
  } else {
      $allFids = array(0=>$fid);
  }
  $thisFidObj = "";
  $allFidObjs = array();
  $elementOptionsFid = array();
  $listTemplateHelp = array();
  $class = "odd";
  foreach($allFids as $thisFid) {
      unset($thisFidObj);
      if($fid == $thisFid) {
          $thisFidObj = $formObj;
      } else {
          $thisFidObj = $form_handler->get($thisFid, true); // true causes all elements to be included, even if they're not visible
      }
      $allFidObjs[$thisFid] = $thisFidObj; // for use later on
      $thisFidElements = $thisFidObj->getVar('elements');
      $thisFidCaptions = $thisFidObj->getVar('elementCaptions');
      $thisFidColheads = $thisFidObj->getVar('elementColheads');
			$thisFidHandles = $thisFidObj->getVar('elementHandles');
			foreach($thisFidElements as $i=>$thisFidElement) {
      //for($i=0;$i<count($thisFidElements);$i++) {
          $elementHeading = $thisFidColheads[$i] ? $thisFidColheads[$i] : $thisFidCaptions[$i];
          $elementOptions[$thisFidHandles[$i]] = printSmart(trans(strip_tags($elementHeading)), 75);
          $elementOptionsFid[$thisFid][$thisFidElement] = printSmart(trans(strip_tags($elementHeading)), 75); // for passing to custom button logic, so we know all the element options for each form in framework
          $class = $class == "even" ? "odd" : "even";
          $listTemplateHelp[] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading))) . "</b></nobr></td><td class=$class><nobr>".$thisFidHandles[$i]."</nobr></td></tr>";
      }
  }
  // create the template information
  $headings = array();
  $headings['useheadings'] = $screen->getVar('useheadings');
  $headings['repeatheaders'] = $screen->getVar('repeatheaders');
  $headings['usesearchcalcmsgs'] = $screen->getVar('usesearchcalcmsgs');
  $headings['usesearch'] = $screen->getVar('usesearch');
  $headings['columnwidth'] = $screen->getVar('columnwidth');
  $headings['textwidth'] = $screen->getVar('textwidth');
  $headings['usecheckboxes'] = $screen->getVar('usecheckboxes');
  $headings['useviewentrylinks'] = $screen->getVar('useviewentrylinks');
  $headings['elementoptions'] = $elementOptions;
  $headings['hiddencolumns'] = $screen->getVar('hiddencolumns');
  $headings['decolumns'] = $screen->getVar('decolumns');
  $headings['desavetext'] = $screen->getVar('desavetext');

  // buttons data
  $buttons = array();
  $buttons['useaddupdate'] = $screen->getVar('useaddupdate');
  $buttons['useaddmultiple'] = $screen->getVar('useaddmultiple');
  $buttons['useaddproxy'] = $screen->getVar('useaddproxy');
  $buttons['useexport'] = $screen->getVar('useexport');
  $buttons['useimport'] = $screen->getVar('useimport');
  $buttons['usenotifications'] = $screen->getVar('usenotifications');
  $buttons['usechangecols'] = $screen->getVar('usechangecols');
  $buttons['usecalcs'] = $screen->getVar('usecalcs');
  $buttons['useexportcalcs'] = $screen->getVar('useexportcalcs');
  $buttons['useadvsearch'] = $screen->getVar('useadvsearch');
  $buttons['useclone'] = $screen->getVar('useclone');
  $buttons['usedelete'] = $screen->getVar('usedelete');
  $buttons['useselectall'] = $screen->getVar('useselectall');
  $buttons['useclearall'] = $screen->getVar('useclearall');
  $buttons['usereset'] = $screen->getVar('usereset');
  $buttons['usesave'] = $screen->getVar('usesave');
  $buttons['usedeleteview'] = $screen->getVar('usedeleteview');

  // custom button data
  $custom = array();
}

if($_GET['sid'] != "new" && $settings['type'] == 'multiPage') {
  // conditions data
  $conditions = array();
}

if($_GET['sid'] != "new" && $settings['type'] == 'form') {
  $options = array();
  $options['donedest'] = $screen->getVar('donedest');
  $options['savebuttontext'] = $screen->getVar('savebuttontext');
  $options['alldonebuttontext'] = $screen->getVar('alldonebuttontext');
  $options['displayheading'] = $screen->getVar('displayheading');
  $options['reloadblank'] = $screen->getVar('reloadblank');
}


// define tabs for screen sub-page
$adminPage['tabs'][1]['name'] = "Settings";
$adminPage['tabs'][1]['template'] = "db:admin/screen_settings.html";
$adminPage['tabs'][1]['content'] = $settings + $common;

if($_GET['sid'] != "new" && $settings['type'] == 'form') {
  $adminPage['tabs'][2]['name'] = "Options";
  $adminPage['tabs'][2]['template'] = "db:admin/screen_form_options.html";
  $adminPage['tabs'][2]['content'] = $options + $common;
}

if($_GET['sid'] != "new" && $settings['type'] == 'multiPage') {
  $adminPage['tabs'][3]['name'] = "Conditions";
  $adminPage['tabs'][3]['template'] = "db:admin/screen_conditions.html";
  $adminPage['tabs'][3]['content'] = $conditions + $common;
}

if($_GET['sid'] != "new" && $settings['type'] == 'listOfEntries') {
  $adminPage['tabs'][2]['name'] = "Display";
  $adminPage['tabs'][2]['template'] = "db:admin/screen_list_display.html";
  $adminPage['tabs'][2]['content'] = $display + $common;

  $adminPage['tabs'][3]['name'] = "View";
  $adminPage['tabs'][3]['template'] = "db:admin/screen_list_view.html";
  $adminPage['tabs'][3]['content'] = $view + $common;

  $adminPage['tabs'][4]['name'] = "Headings";
  $adminPage['tabs'][4]['template'] = "db:admin/screen_list_headings.html";
  $adminPage['tabs'][4]['content'] = $headings + $common;

  $adminPage['tabs'][5]['name'] = "Buttons";
  $adminPage['tabs'][5]['template'] = "db:admin/screen_list_buttons.html";
  $adminPage['tabs'][5]['content'] = $buttons + $common;

  $adminPage['tabs'][6]['name'] = "Custom buttons";
  $adminPage['tabs'][6]['template'] = "db:admin/screen_list_custom.html";
  $adminPage['tabs'][6]['content'] = $custom + $common;
}

$adminPage['needsave'] = true;

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['url'] = "page=application&aid=$aid";
$breadcrumbtrail[2]['text'] = $appName;
$breadcrumbtrail[3]['text'] = $formName;
$breadcrumbtrail[4]['text'] = $screenName;

