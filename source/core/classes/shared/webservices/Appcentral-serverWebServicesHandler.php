<?php

require_once('innomatic/webservices/WebServicesHandler.php');
require_once('innomatic/webservices/xmlrpc/XmlRpc_Client.php');

class AppcentralServerWebServicesHandler extends WebServicesHandler {
	public static function list_available_repositories() {
		$reps = array();

		$rep_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT appcentral_reps.id AS id, appcentral_reps.name AS name, appcentral_reps.description AS description FROM appcentral_reps_access,appcentral_reps WHERE appcentral_reps_access.profileid='.InnomaticContainer::instance('innomaticcontainer')->getWebServicesProfile().' AND appcentral_reps_access.repositoryid=appcentral_reps.id ORDER BY name');

		while (!$rep_query->eof) {
			$reps[$rep_query->getFields('id')] = array('name' => $rep_query->getFields('name'), 'description' => $rep_query->getFields('description'));

			$rep_query->moveNext();
		}

		return new XmlRpcResp(\Innomatic\Webservices\Xmlrpc\php_xmlrpc_encode($reps));
	}

	public static function list_available_applications($m) {
		require_once('appcentral/server/AppCentralRepository.php');

		$rep_id = $m->GetParam(0);
		$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $rep_id->scalarVal(), InnomaticContainer::instance('innomaticcontainer')->getWebServicesProfile(), InnomaticContainer::instance('innomaticcontainer')->getWebServicesUser());
		$avail_applications = $rep->AvailableApplicationsList();

		$applications = array();
		while (list (, $id) = each($avail_applications)) {
			$app_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT appcentral_applications.appid AS appid,appcentral_applications.description AS description,appcentral_applications.lastversion AS lastversion,appcentral_applications.category AS category,appcentral_applications_versions.dependencies AS dependencies,appcentral_applications_versions.suggestions AS suggestions,appcentral_applications_versions.date AS date FROM appcentral_applications,appcentral_applications_versions WHERE appcentral_applications.id='.$id.' AND appcentral_applications_versions.applicationid=appcentral_applications.id AND appcentral_applications_versions.version=appcentral_applications.lastversion');

			$applications[$id]['appid'] = $app_query->getFields('appid');
			$applications[$id]['description'] = $app_query->getFields('description');
			$applications[$id]['lastversion'] = $app_query->getFields('lastversion');
			$applications[$id]['category'] = $app_query->getFields('category');
			$applications[$id]['date'] = $app_query->getFields('date');
			$applications[$id]['dependencies'] = $app_query->getFields('dependencies');
			$applications[$id]['suggestions'] = $app_query->getFields('suggestions');
		}

		function list_available_applications_cmp($a, $b) {
			if ($a['appid'] == $b['appid'])
			return 0;
			return ($a['appid'] < $b['appid']) ? -1 : 1;
		}

		uasort($applications, 'appcentral_server_list_available_applications_cmp');

		return new XmlRpcResp(\Innomatic\Webservices\Xmlrpc\php_xmlrpc_encode($applications));
	}

	public static function list_available_application_versions($m) {
		require_once('appcentral/server/AppCentralRepository.php');

		$rep_id = $m->GetParam(0);
		$app_id = $m->GetParam(1);

		$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $rep_id->scalarVal(),
		InnomaticContainer::instance('innomaticcontainer')->getWebServicesProfile().InnomaticContainer::instance('innomaticcontainer')->getWebServicesUser());

		return new XmlRpcResp(\Innomatic\Webservices\Xmlrpc\php_xmlrpc_encode($rep->AvailableApplicationVersionsList($app_id->scalarVal())));
	}

	public static function retrieve_application($m) {
		require_once('appcentral/server/AppCentralRepository.php');

		$rep_id = $m->GetParam(0);
		$app_id = $m->GetParam(1);
		$app_version = $m->GetParam(2);
		$profile_id = $m->GetParam(3);

		$rep = new AppCentralRepository(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $rep_id->scalarVal(),
		InnomaticContainer::instance('innomaticcontainer')->getWebServicesProfile(), InnomaticContainer::instance('innomaticcontainer')->getWebServicesUser());

		return new XmlRpcResp(new XmlRpcVal($rep->SendApplication($app_id->scalarVal(), $app_version->scalarVal()), 'base64'));
	}

	public static function retrieve_appcentral_client() {
		require_once('appcentral/server/AppCentralApplication.php');
		require_once('innomatic/logging/Logger.php');

		$app_query = InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('SELECT id FROM appcentral_applications WHERE appid='.InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->formatText('appcentral-client'));

		//if ( $app_query->getNumberRows() )
		//{
		$application = new AppCentralApplication(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), $app_query->getFields('id'));
		$result = $application->Retrieve($version);

		$log = InnomaticContainer::instance('innomaticcontainer')->getLogger();
		$log->logEvent('appcentral-server', 'Sent AppCentral Client to remote address '.$_SERVER['REMOTE_ADDR'], Logger::NOTICE);

		//if ( $result ) $this->logEvent( 'Sent application '.$application->mApplication.' to user '.$this->mUser );

		return new XmlRpcResp(new XmlRpcVal($application->Retrieve(), 'base64'));
	}
}
?>
