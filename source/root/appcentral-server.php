<?php

// Initialization
//

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('appcentral/server/AppCentralRepository.php');
require_once('appcentral/server/AppCentralApplication.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innomatic/locale/LocaleCountry.php'); 

global $gLocale, $gXml_def, $gPage_title, $gStatus;
	
$gWui = Wui::instance('wui');
$gWui->LoadWidget('xml');
$gWui->LoadWidget('innomaticpage');
$gWui->LoadWidget('innomatictoolbar');

$gLocale = new LocaleCatalog(
	'appcentral-server::root_server',
	InnomaticContainer::instance('innomaticcontainer')->getLanguage());

$gPage_content = $gStatus = $gToolbars = $gXml_def = '';
$gPage_title = $gLocale->getStr('appcentral-server.title');

$gToolbars['repository'] = array('repository' => array('label' => $gLocale->getStr('repository.toolbar'), 'themeimage' => 'view_text', 'horiz' => true, 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', '')))), 'newrepository' => array('label' => $gLocale->getStr('newrepository.toolbar'), 'horiz' => true, 'themeimage' => 'filenew', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newrepository', '')))));

$gToolbars['applications'] = array('applications' => array('label' => $gLocale->getStr('applications.toolbar'), 'themeimage' => 'view_detailed', 'horiz' => true, 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'applications', '')))), 'newrepository' => array('label' => $gLocale->getStr('newapplication.toolbar'), 'horiz' => true, 'themeimage' => 'filenew', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newapplication', '')))));

// Action dispatcher
//
$gAction_disp = new WuiDispatcher('action');

// ----- Repositories ------
//
$gAction_disp->addEvent('newrepository', 'action_newrepository');
function action_newrepository($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess());
	if ($rep->Create($eventData['name'], $eventData['description'], $eventData['logevents'] == 'on' ? true : false))
		$gStatus = $gLocale->getStr('repository_created.status');
	else
		$gStatus = $gLocale->getStr('repository_not_created.status');
}

$gAction_disp->addEvent('editrepository', 'action_editrepository');
function action_editrepository($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	$rep->SetName($eventData['name']);
	$rep->SetDescription($eventData['description']);
	$rep->SetLogEvents($eventData['logevents'] == 'on' ? true : false);
	$gLocale->getStr('repository_updated.status');
}

$gAction_disp->addEvent('removerepository', 'action_removerepository');
function action_removerepository($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	if ($rep->Remove())
		$gStatus = $gLocale->getStr('repository_removed.status');
	else
		$gStatus = $gLocale->getStr('repository_not_removed.status');
}

$gAction_disp->addEvent('eraselog', 'action_eraselog');
function action_eraselog($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	if ($rep->EraseLog())
		$gStatus = $gLocale->getStr('log_erased.status');
	else
		$gStatus = $gLocale->getStr('log_not_erased.status');
}

$gAction_disp->addEvent('enableapplications', 'action_enableapplications');
function action_enableapplications($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['repid']);

	if (isset($eventData['applications']) and is_array($eventData['applications'])) {
		while (list (, $id) = each($eventData['applications'])) {
			$rep->EnableApplication($id);
		}
	}
}

$gAction_disp->addEvent('disableapplications', 'action_disableapplications');
function action_disableapplications($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['repid']);

	if (isset($eventData['applications']) and is_array($eventData['applications'])) {
		while (list (, $id) = each($eventData['applications'])) {
			$rep->DisableApplication($id);
		}
	}
}

$gAction_disp->addEvent('enableprofiles', 'action_enableprofiles');
function action_enableprofiles($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['repid']);

	if (isset($eventData['profiles']) and is_array($eventData['profiles'])) {
		while (list (, $id) = each($eventData['profiles'])) {
			$rep->EnableProfile($id);
		}
	}
}

$gAction_disp->addEvent('disableprofiles', 'action_disableprofiles');
function action_disableprofiles($eventData) {
	global $gLocale, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['repid']);

	if (isset($eventData['profiles']) and is_array($eventData['profiles'])) {
		while (list (, $id) = each($eventData['profiles'])) {
			$rep->DisableProfile($id);
		}
	}
}

// ----- Applications -----
//
$gAction_disp->addEvent('addapplication', 'action_addapplication');
function action_addapplication($eventData) {
	global $gLocale, $gStatus;

	$app = new AppCentralApplication(InnomaticContainer::instance('innomaticcontainer')->getDataAccess());
	if ($app->AddVersion($eventData['application']['tmp_name']))
		$gStatus = $gLocale->getStr('application_added.status');
	else
		$gStatus = $gLocale->getStr('application_not_added.status');

	unlink($eventData['application']['tmp_name']);
}

$gAction_disp->addEvent('removeapplication', 'action_removeapplication');
function action_removeapplication($eventData) {
	global $gLocale, $gStatus;

	$app = new AppCentralApplication(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	if ($app->Remove())
		$gStatus = $gLocale->getStr('application_removed.status');
	else
		$gStatus = $gLocale->getStr('application_not_removed.status');
}

$gAction_disp->addEvent('removeversion', 'action_removeversion');
function action_removeversion($eventData) {
	global $gLocale, $gStatus;

	$app = new AppCentralApplication(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	$app_name = $app->mApplication;

	if ($app->RemoveVersion($eventData['version']))
		$gStatus = sprintf($gLocale->getStr('version_removed.status'), $app_name, $eventData['version']);
	else
		$gStatus = sprintf($gLocale->getStr('version_not_removed.status'), $app_name, $eventData['version']);
}

$gAction_disp->Dispatch();

// Main dispatcher
//
$gMain_disp = new WuiDispatcher('view');

// ----- Repositories -----
//
function repositories_list_action_builder($pageNumber) {
	return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('pagenumber' => $pageNumber))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
	global $gLocale, $gXml_def, $gPage_title, $gStatus;

	$reps_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT * FROM appcentral_reps ORDER BY name');

	if ($reps_query->getNumberRows()) {
		$headers[0]['label'] = $gLocale->getStr('repository_name.header');
		$headers[1]['label'] = $gLocale->getStr('repository_description.header');

		$gXml_def = '<vertgroup><name>vg</name>
		  <children>
		    <table><name>repositories</name>
		      <args>
		        <headers type="array">'.WuiXml::encode($headers).'</headers>
		        <rowsperpage>10</rowsperpage>
		        <pagesactionfunction>repositores_list_action_builder</pagesactionfunction>
		        <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
		      </args>
		      <children>
		';

		$row = 0;

		while (!$reps_query->eof) {			
			$toolbar = '<horizgroup row="'.$row.'" col="2"><children>';
			
			$toolbar .= '<button><name>applications</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('repository_applications.button')).'</label>
					<themeimage>view_detailed</themeimage>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryapplications', array('id' => $reps_query->getFields('id')))))).'</action></args></button>';
			
			$toolbar .= '<button><name>profiles</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('repository_profiles.button')).'</label>
					<themeimage>view_detailed</themeimage>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryprofiles', array('id' => $reps_query->getFields('id')))))).'</action></args></button>';
			
			$toolbar .= '<button><name>edit</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('edit_repository.button')).'</label>
					<themeimage>edit</themeimage>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'editrepository', array('id' => $reps_query->getFields('id')))))).'</action></args></button>';	

			if (file_exists(InnomaticContainer::instance('innomaticcontainer')->getHome().'core/applications/appcentral-server/repository_'.$reps_query->getFields('id').'.log')) {
				$toolbar .= '<button><name>log</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('repository_log.button')).'</label>
					<themeimage>toggle_log</themeimage>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositorylog', array('id' => $reps_query->getFields('id')))))).'</action></args></button>';				
			}

			$toolbar .= '<button><name>remove</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('remove_repository.button')).'</label>
							<needconfirm>true</needconfirm>
							<confirmmessage>'.WuiXml::cdata($gLocale->getStr('remove_repository.confirm')).'</confirmmessage>
					<themeimage>edittrash</themeimage>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'removerepository', array('id' => $reps_query->getFields('id')))))).'</action></args></button>';
							
			$toolbar .= '</children></horizgroup>';
			
			$gXml_def.= '<label row="'.$row.'" col="0"><name>name</name>
			  <args>
			    <label type="encoded">'.urlencode($reps_query->getFields('name')).'</label>
			  </args>
			</label>
			<label row="'.$row.'" col="1"><name>description</name>
			  <args>
			    <label type="encoded">'.urlencode($reps_query->getFields('description')).'</label>
			  </args>
			</label>'.$toolbar;
			$row ++;
			$reps_query->moveNext();
		}

		$gXml_def.= '      </children>
		    </table>
		  </children>
		</vertgroup>';
	} else {
		if (!strlen($gStatus))
			$gStatus = $gLocale->getStr('no_repositories.status');
	}

	$gPage_title.= ' - '.$gLocale->getStr('repositories.title');
}

$gMain_disp->addEvent('newrepository', 'main_newrepository');
function main_newrepository($eventData) {
	global $gLocale, $gXml_def, $gPage_title, $gStatus;

	$gXml_def = '<vertgroup><name>new</name>
	  <children>
	    <label><name>newrep</name>
	      <args>
	        <label type="encoded">'.urlencode($gLocale->getStr('newrepository.title')).'</label>
	        <bold>true</bold>
	      </args>
	    </label>
	    <form><name>newrepository</name>
	      <args>
	        <method>post</method>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'newrepository', '')))).'</action>
	      </args>
	      <children>
	        <grid><name>new</name>
	          <children>
	            <label row="0" col="0"><name>name</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_name.label')).'</label>
	              </args>
	            </label>
	            <string row="0" col="1"><name>name</name>
	              <args>
	                <disp>action</disp>
	                <size>20</size>
	              </args>
	            </string>
	            <label row="1" col="0"><name>description</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_description.label')).'</label>
	              </args>
	            </label>
	            <text row="1" col="1"><name>description</name>
	              <args>
	                <disp>action</disp>
	                <cols>80</cols>
	                <rows>5</rows>
	              </args>
	            </text>
	            <label row="2" col="0"><name>description</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_logevents.label')).'</label>
	              </args>
	            </label>
	            <checkbox row="2" col="1"><name>logevents</name>
	              <args>
	                <disp>action</disp>
	              </args>
	            </checkbox>
	          </children>
	        </grid>
	      </children>
	    </form>
	    <horizbar><name>hb</name></horizbar>
	    <button><name>apply</name>
	      <args>
	        <themeimage>button_ok</themeimage>
	        <formsubmit>newrepository</formsubmit>
	        <horiz>true</horiz>
	        <frame>false</frame>
	        <label type="encoded">'.urlencode($gLocale->getStr('new_repository.submit')).'</label>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'newrepository', '')))).'</action>
	      </args>
	    </button>
	  </children>
	</vertgroup>';

	$gPage_title.= ' - '.$gLocale->getStr('newrepository.title');
}

$gMain_disp->addEvent('editrepository', 'main_editrepository');
function main_editrepository($eventData) {
	global $gLocale, $gXml_def, $gPage_title, $gStatus;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);

	$gXml_def = '<vertgroup><name>edit</name>
	  <children>
	    <label><name>editrep</name>
	      <args>
	        <label type="encoded">'.urlencode($gLocale->getStr('editrepository.title')).'</label>
	        <bold>true</bold>
	      </args>
	    </label>
	    <form><name>editrepository</name>
	      <args>
	        <method>post</method>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editrepository', array('id' => $eventData['id']))))).'</action>
	      </args>
	      <children>
	        <grid><name>edit</name>
	          <children>
	            <label row="0" col="0"><name>name</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_name.label')).'</label>
	              </args>
	            </label>
	            <string row="0" col="1"><name>name</name>
	              <args>
	                <disp>action</disp>
	                <size>20</size>
	                <value type="encoded">'.urlencode($rep->mName).'</value>
	              </args>
	            </string>
	            <label row="1" col="0"><name>description</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_description.label')).'</label>
	              </args>
	            </label>
	            <text row="1" col="1"><name>description</name>
	              <args>
	                <disp>action</disp>
	                <cols>80</cols>
	                <rows>5</rows>
	                <value type="encoded">'.urlencode($rep->mDescription).'</value>
	              </args>
	            </text>
	            <label row="2" col="0"><name>description</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('repository_logevents.label')).'</label>
	              </args>
	            </label>
	            <checkbox row="2" col="1"><name>logevents</name>
	              <args>
	                <disp>action</disp>
	                <checked>'. ($rep->mLogEvents ? 'true' : 'false').'</checked>
	              </args>
	            </checkbox>
	          </children>
	        </grid>
	      </children>
	    </form>
	    <horizbar><name>hb</name></horizbar>
	    <button><name>apply</name>
	      <args>
	        <themeimage>button_ok</themeimage>
	        <formsubmit>editrepository</formsubmit>
	        <horiz>true</horiz>
	        <frame>false</frame>
	        <label type="encoded">'.urlencode($gLocale->getStr('edit_repository.submit')).'</label>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'editrepository', array('id' => $eventData['id']))))).'</action>
	      </args>
	    </button>
	  </children>
	</vertgroup>';

	$gPage_title.= ' - '.$gLocale->getStr('editrepository.title');
}

$gMain_disp->addEvent('repositoryapplications', 'main_repositoryapplications');
function main_repositoryapplications($eventData) {
	global $gLocale, $gPage_title, $gXml_def;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);

	$avail_applications = $rep->AvailableApplicationsList();
	$unavailable_applications = $available_applications = array();

	$apps_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT id,appid FROM appcentral_applications ORDER BY appid');

	while (!$apps_query->eof) {
		if (in_array($apps_query->getFields('id'), $avail_applications))
			$available_applications[$apps_query->getFields('id')] = $apps_query->getFields('appid');
		else
			$unavailable_applications[$apps_query->getFields('id')] = $apps_query->getFields('appid');

		$apps_query->moveNext();
	}

	$headers[0]['label'] = $gLocale->getStr('unavailable_applications.label');
	$headers[1]['label'] = $gLocale->getStr('available_applications.label');
	
	$toolbar = '<horizgroup row="1" col="0"><children>';
		
	$toolbar .= '<button><name>disable</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('enable_applications.button')).'</label>
					<themeimage>forward2</themeimage>
					<horiz>true</horiz>
							<formsubmit>unavailableapplications</formsubmit>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryapplications', array('id' => $eventData['id'])), array('action', 'enableapplications', array('repid' => $eventData['id']))))).'</action></args></button>';
				
	$toolbar .= '</children></horizgroup>';
	
	$toolbar_b = '<horizgroup row="1" col="1"><children>';
	
	$toolbar_b .= '<button><name>enable</name><args>
					<label>'.WuiXml::cdata($gLocale->getStr('disable_applications.button')).'</label>
					<themeimage>back2</themeimage>
							<formsubmit>availableapplications</formsubmit>
					<horiz>true</horiz>
					<action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryapplications', array('id' => $eventData['id'])), array('action', 'disableapplications', array('repid' => $eventData['id']))))).'</action></args></button>';
	
	$toolbar_b .= '</children></horizgroup>';
	
	$gXml_def = '<vertgroup><name>applications</name>
	  <args>
	    <align>center</align>
	  </args>
	  <children>
	  <label><name>rep</name>
	    <args>
	      <bold>true</bold>
	      <label type="encoded">'.urlencode($rep->mName).'</label>
	    </args>
	  </label>
	    <table><name>applications</name>
	      <args>
	        <headers type="array">'.WuiXml::encode($headers).'</headers>
	      </args>
	      <children>
	
	        <form row="0" col="0"><name>unavailableapplications</name>
	          <args>
	            <method>post</method>
	            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryapplications', array('id' => $eventData['id'])), array('action', 'enableapplications', array('repid' => $eventData['id']))))).'</action>
	          </args>
	          <children>
	              <listbox><name>applications</name>
	                <args>
	                  <elements type="array">'.WuiXml::encode($unavailable_applications).'</elements>
	                  <disp>action</disp>
	                  <size>15</size>
	                  <multiselect>true</multiselect>
	                </args>
	              </listbox>
	          </children>
	        </form>
	
	        '.$toolbar.'
	
	        <form row="0" col="1"><name>availableapplications</name>
	          <args>
	            <method>post</method>
	            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryapplications', array('id' => $eventData['id'])), array('action', 'disableapplications', array('repid' => $eventData['id']))))).'</action>
	          </args>
	          <children>
	              <listbox><name>applications</name>
	                <args>
	                  <elements type="array">'.WuiXml::encode($available_applications).'</elements>
	                  <disp>action</disp>
	                  <size>15</size>
	                  <multiselect>true</multiselect>
	                </args>
	              </listbox>
	          </children>
	        </form>
	
	        '.$toolbar_b.'
	
	      </children>
	    </table>
	  </children>
	</vertgroup>';
	$gPage_title.= ' - '.$rep->mName.' - '.$gLocale->getStr('repository_applications.title');
}

$gMain_disp->addEvent('repositoryprofiles', 'main_repositoryprofiles');
function main_repositoryprofiles($eventData) {
	global $gLocale, $gPage_title, $gXml_def;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);

	$avail_profiles = $rep->AvailableProfilesList();
	$unavailable_profiles = $available_profiles = array();

	$apps_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT id,profilename FROM webservices_profiles ORDER BY profilename');

	while (!$apps_query->eof) {
		if (in_array($apps_query->getFields('id'), $avail_profiles))
			$available_profiles[$apps_query->getFields('id')] = $apps_query->getFields('profilename');
		else
			$unavailable_profiles[$apps_query->getFields('id')] = $apps_query->getFields('profilename');

		$apps_query->moveNext();
	}

	$headers[0]['label'] = $gLocale->getStr('unavailable_profiles.label');
	$headers[1]['label'] = $gLocale->getStr('available_profiles.label');

	$gXml_def = '<vertgroup><name>profiles</name>
	  <args>
	    <align>center</align>
	  </args>
	  <children>
	  <label><name>rep</name>
	    <args>
	      <bold>true</bold>
	      <label type="encoded">'.urlencode($rep->mName).'</label>
	    </args>
	  </label>
	    <table><name>profiles</name>
	      <args>
	        <headers type="array">'.WuiXml::encode($headers).'</headers>
	      </args>
	      <children>
	
	        <form row="0" col="0"><name>unavailableprofiles</name>
	          <args>
	            <method>post</method>
	            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryprofiles', array('id' => $eventData['id'])), array('action', 'enableprofiles', array('repid' => $eventData['id']))))).'</action>
	          </args>
	          <children>
	              <listbox><name>profiles</name>
	                <args>
	                  <elements type="array">'.WuiXml::encode($unavailable_profiles).'</elements>
	                  <disp>action</disp>
	                  <size>15</size>
	                  <multiselect>true</multiselect>
	                </args>
	              </listbox>
	          </children>
	        </form>
	
	        <innomatictoolbar row="1" col="0"><name>tb</name>
	          <args>
	            <toolbars type="array">'.WuiXml::encode(array('view' => array('enable' => array('label' => $gLocale->getStr('enable_profiles.button'), 'horiz' => true, 'themeimage' => 'forward2', 'horiz' => 'true', 'formsubmit' => 'unavailableprofiles', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryprofiles', array('id' => $eventData['id'])), array('action', 'enableprofiles', array('repid' => $eventData['id'])))))))).'</toolbars>
	            <frame>false</frame>
	          </args>
	        </innomatictoolbar>
	
	        <form row="0" col="1"><name>availableprofiles</name>
	          <args>
	            <method>post</method>
	            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryprofiles', array('id' => $eventData['id'])), array('action', 'disableprofiles', array('repid' => $eventData['id']))))).'</action>
	          </args>
	          <children>
	              <listbox><name>profiles</name>
	                <args>
	                  <elements type="array">'.WuiXml::encode($available_profiles).'</elements>
	                  <disp>action</disp>
	                  <size>15</size>
	                  <multiselect>true</multiselect>
	                </args>
	              </listbox>
	          </children>
	        </form>
	
	        <innomatictoolbar row="1" col="1"><name>tb</name>
	          <args>
	            <toolbars type="array">'.WuiXml::encode(array('view' => array('enable' => array('label' => $gLocale->getStr('disable_profiles.button'), 'horiz' => true, 'themeimage' => 'back2', 'horiz' => 'true', 'formsubmit' => 'availableprofiles', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'repositoryprofiles', array('id' => $eventData['id'])), array('action', 'disableprofiles', array('repid' => $eventData['id'])))))))).'</toolbars>
	            <frame>false</frame>
	          </args>
	        </innomatictoolbar>
	
	      </children>
	    </table>
	  </children>
	</vertgroup>';
	$gPage_title.= ' - '.$rep->mName.' - '.$gLocale->getStr('repository_profiles.title');
}

$gMain_disp->addEvent('repositorylog', 'main_repositorylog');
function main_repositorylog($eventData) {
	global $gLocale, $gPage_title, $gXml_def, $gToolbars;

	$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);

	$gXml_def = '<vertgroup><name>logs</name>
	  <children>
	    <text><name>log</name>
	      <args>
	        <readonly>true</readonly>
	        <rows>15</rows>
	        <cols>120</cols>
	        <value type="encoded">'.urlencode($rep->GetLogContent()).'</value>
	      </args>
	    </text>
	  </children>
	</vertgroup>';

	$gToolbars['log'] = array('refresh' => array('label' => $gLocale->getStr('refreshlog.toolbar'), 'themeimage' => 'reload', 'horiz' => true, 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'repositorylog', array('id' => $eventData['id']))))), 'eraselog' => array('label' => $gLocale->getStr('eraselog.toolbar'), 'themeimage' => 'edittrash', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'eraselog', array('id' => $eventData['id'])))), 'needconfirm' => 'true', 'confirmmessage' => $gLocale->getStr('eraselog.confirm')));
}

// ----- Applications -----
//
function applications_list_action_builder($pageNumber) {
	return WuiEventsCall::buildEventsCallString('', array(array('view', 'applications', array('pagenumber' => $pageNumber))));
}

$gMain_disp->addEvent('applications', 'main_applications');
function main_applications($eventData) {
	global $gLocale, $gXml_def, $gPage_title, $gStatus;

	$apps_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT * FROM appcentral_applications ORDER BY appid');

	if ($apps_query->getNumberRows()) {
		$headers[0]['label'] = $gLocale->getStr('application_name.header');
		$headers[1]['label'] = $gLocale->getStr('application_lastversion.header');
		$headers[2]['label'] = $gLocale->getStr('application_category.header');

		$gXml_def = '<vertgroup><name>vg</name>
		  <children>
		    <table><name>applications</name>
		      <args>
		        <headers type="array">'.WuiXml::encode($headers).'</headers>
		        <rowsperpage>10</rowsperpage>
		        <pagesactionfunction>applications_list_action_builder</pagesactionfunction>
		        <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
		      </args>
		      <children>
		';

		$row = 0;

		while (!$apps_query->eof) {
			$gXml_def.= '<label row="'.$row.'" col="0"><name>name</name>
			  <args>
			    <label type="encoded">'.urlencode('<strong>'.$apps_query->getFields('appid').'</strong><br>'.$apps_query->getFields('description')).'</label>
			  </args>
			</label>
			<label row="'.$row.'" col="1"><name>lastversion</name>
			  <args>
			    <label type="encoded">'.urlencode($apps_query->getFields('lastversion')).'</label>
			  </args>
			</label>
			<label row="'.$row.'" col="2"><name>name</name>
			  <args>
			    <label type="encoded">'.urlencode(ucfirst($apps_query->getFields('category'))).'</label>
			  </args>
			</label>
			<innomatictoolbar row="'.$row.'" col="3"><name>tb</name>
			  <args>
			    <toolbars type="array">'.WuiXml::encode(array('view' => array('remove' => array('label' => $gLocale->getStr('remove_application.button'), 'horiz' => true, 'needconfirm' => 'true', 'confirmmessage' => $gLocale->getStr('remove_application.confirm'), 'themeimage' => 'edittrash', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'applications', ''), array('action', 'removeapplication', array('id' => $apps_query->getFields('id')))))), 'versions' => array('label' => $gLocale->getStr('application_versions.button'), 'horiz' => true, 'themeimage' => 'view_detailed', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'applicationversions', array('id' => $apps_query->getFields('id'))))))))).'</toolbars>
			    <frame>false</frame>
			  </args>
			</innomatictoolbar>';
			$row ++;
			$apps_query->moveNext();
		}

		$gXml_def.= '      </children>
		    </table>
		  </children>
		</vertgroup>';
	} else {
		if (!strlen($gStatus))
			$gStatus = $gLocale->getStr('no_applications.status');
	}

	$gPage_title.= ' - '.$gLocale->getStr('applications.title');
}

$gMain_disp->addEvent('newapplication', 'main_newapplication');
function main_newapplication($eventData) {
	global $gLocale, $gXml_def, $gPage_title;

	$gXml_def = '<vertgroup><name>new</name>
	  <children>
	    <label><name>newapplication</name>
	      <args>
	        <label type="encoded">'.urlencode($gLocale->getStr('newapplication.title')).'</label>
	        <bold>true</bold>
	      </args>
	    </label>
	    <form><name>newapplication</name>
	      <args>
	        <method>post</method>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'applications', ''), array('action', 'addapplication', '')))).'</action>
	      </args>
	      <children>
	        <grid><name>new</name>
	          <children>
	            <label row="0" col="0"><name>file</name>
	              <args>
	                <label type="encoded">'.urlencode($gLocale->getStr('application_file.label')).'</label>
	              </args>
	            </label>
	            <file row="0" col="1"><name>application</name>
	              <args>
	                <disp>action</disp>
	                <size>20</size>
	              </args>
	            </file>
	          </children>
	        </grid>
	      </children>
	    </form>
	    <horizbar><name>hb</name></horizbar>
	    <button><name>apply</name>
	      <args>
	        <themeimage>button_ok</themeimage>
	        <formsubmit>newapplication</formsubmit>
	        <horiz>true</horiz>
	        <frame>false</frame>
	        <label type="encoded">'.urlencode($gLocale->getStr('addapplication.submit')).'</label>
	        <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'applications', ''), array('action', 'addapplication', '')))).'</action>
	      </args>
	    </button>
	  </children>
	</vertgroup>';
	$gPage_title.= ' - '.$gLocale->getStr('newapplication.title');
}

function versions_list_action_builder($pageNumber) {
	$tmp_main_disp = new WuiDispatcher('view');

	$event_data = $tmp_main_disp->GetEventData();

	return WuiEventsCall::buildEventsCallString('', array(array('view', 'applicationversions', array('pagenumber' => $eventData['pagenumber'], 'id' => $event_data['id']))));
}

$gMain_disp->addEvent('applicationversions', 'main_applicationversions');
function main_applicationversions($eventData) {
	global $gLocale, $gPage_title, $gXml_def, $gStatus;

	$application = new AppCentralApplication(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $eventData['id']);
	$versions = $application->GetVersionsList(true);

	$headers[0]['label'] = $gLocale->getStr('version.header');
	$headers[1]['label'] = $gLocale->getStr('date.header');
	$headers[2]['label'] = $gLocale->getStr('dependencies.header');

	$gXml_def = '<vertgroup><name>versions</name>
	  <children>
	    <label><name>application</name>
	      <args>
	        <bold>true</bold>
	        <label type="encoded">'.urlencode($application->mApplication).'</label>
	      </args>
	    </label>
	    <table><name>versions</name>
	      <args>
	        <headers type="array">'.WuiXml::encode($headers).'</headers>
	        <rowsperpage>15</rowsperpage>
	        <pagesactionfunction>versions_list_action_builder</pagesactionfunction>
	        <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
	      </args>
	      <children>';

	$row = 0;

	while (list (, $version) = each($versions)) {
		$vers_data = $application->GetVersionData($version);

		$gXml_def.= '<label row="'.$row.'" col="0"><name>version</name>
		  <args>
		    <label type="encoded">'.urlencode($version).'</label>
		  </args>
		</label>
		<label row="'.$row.'" col="1"><name>date</name>
		  <args>
		    <label type="encoded">'.urlencode($vers_data['date']).'</label>
		  </args>
		</label>
		<label row="'.$row.'" col="2"><name>dependencies</name>
		  <args>
		    <label type="encoded">'.urlencode($vers_data['dependencies']. (strlen($vers_data['suggestions']) ? '<br>('.$vers_data['suggestions'].')' : '')).'</label>
		  </args>
		</label>
		<innomatictoolbar row="'.$row.'" col="3"><name>tb</name>
		  <args>
		    <toolbars type="array">'.WuiXml::encode(array('view' => array('remove' => array('label' => $gLocale->getStr('remove_version.button'), 'horiz' => true, 'needconfirm' => 'true', 'confirmmessage' => $gLocale->getStr('remove_version.confirm'), 'themeimage' => 'edittrash', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', (count($versions) == 1 ? 'applications' : 'applicationversions'), array('id' => $eventData['id'])), array('action', 'removeversion', array('id' => $eventData['id'], 'version' => $version)))))))).'</toolbars>
		    <frame>false</frame>
		  </args>
		</innomatictoolbar>';

		$row ++;
	}

	$gXml_def.= '      </children>
	    </table>
	  </children>
	</vertgroup>';

	$gPage_title.= ' - '.$application->mApplication.' - '.$gLocale->getStr('applicationversions.title');
}

$gMain_disp->Dispatch();

// Rendering
//
if (strlen($gXml_def))
	$gPage_content = new WuiXml('page', array('definition' => $gXml_def));

$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'toolbars' => array(new WuiInnomaticToolbar('view', array('toolbars' => $gToolbars, 'toolbar' => 'true'))), 'maincontent' => $gPage_content, 'status' => $gStatus)));

$gWui->render();

?>
