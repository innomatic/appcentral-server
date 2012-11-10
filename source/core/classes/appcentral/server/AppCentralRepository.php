<?php

require_once('innomatic/logging/Logger.php');
require_once('appcentral/server/AppCentralApplication.php');

class AppCentralRepository {
	var $mrRootDb;
	var $mId;
	var $mName;
	var $mDescription;
	var $mProfileId;
	var $mUser;
	var $mLogEvents;
	var $mLogFile;
	var $mLogHandler;

	function AppCentralRepository(& $rrootDb, $repId = 0, $profileId = '', $user = '') {
		$this->mrRootDb = & $rrootDb;

		if ($repId) {
			$rep_query = & $this->mrRootDb->execute('SELECT name,description,logevents '.'FROM appcentral_reps '.'WHERE id='.$repId);

			if ($rep_query->getNumberRows()) {
				$this->mId = $repId;
				$this->mName = $rep_query->getFields('name');
				$this->mDescription = $rep_query->getFields('description');
				$this->mProfileId = $profileId;
				$this->mUser = $user;
				$this->mLogEvents = $rep_query->getFields('logevents') == $this->mrRootDb->fmttrue ? true : false;
				if ($this->mLogEvents) {
					$this->mLogFile = InnomaticContainer::instance('innomaticcontainer')->getHome().'core/applications/'.'appcentral-server/repository_'.$this->mId.'.log';
					$this->mLogHandler = new Logger($this->mLogFile);
				}
			}
		}
	}

	function Create($repName, $repDescription, $repLogEvents) {
		$result = false;

		$rep_id = $this->mrRootDb->getNextSequenceValue('appcentral_reps_id_seq');

		if ($this->mrRootDb->execute('INSERT INTO appcentral_reps '.'VALUES ('.$rep_id.','.$this->mrRootDb->formatText($repName).','.$this->mrRootDb->formatText($repDescription).','.$this->mrRootDb->formatText($repLogEvents == true ? $this->mrRootDb->fmttrue : $this->mrRootDb->fmtfalse).')')) {
			$this->mId = $rep_id;
			$this->mName = $repName;
			$this->mDescription = $repDescription;
			$this->mLogEvents = $repLogEvents;
			if ($this->mLogEvents) {
				$this->mLogFile = InnomaticContainer::instance('innomaticcontainer')->getHome().'core/applications/'.'appcentral-server/repository_'.$this->mId.'.log';
				$this->mLogHandler = new Logger($this->mLogFile);
			}

			$result = true;
		}

		return $result;
	}

	function Remove() {
		$result = false;

		if ($this->mrRootDb->execute('DELETE FROM appcentral_reps '.'WHERE id='.$this->mId)) {
			$this->mrRootDb->execute('DELETE FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->mId);

			$this->mrRootDb->execute('DELETE FROM appcentral_reps_access '.'WHERE repositoryid='.$this->mId);

			if (file_exists($this->mLogFile))
				unlink($this->mLogfile);
			$this->mId = 0;
			$this->mName = '';
			$this->mDescription = '';

			$result = true;
		}

		return $result;
	}

	function LogEvent($event, $type = Logger::NOTICE) {
		if ($this->mLogEvents)
			$this->mLogHandler->logEvent($this->mName, $event, $type);

		return true;
	}

	function getLogContent() {
		$result = '';

		if (file_exists($this->mLogFile) and $fh = fopen($this->mLogFile, 'r')) {
			$result = fread($fh, filesize($this->mLogFile));
			fclose($fh);
		}

		return $result;
	}

	function EraseLog() {
		$result = false;

		if (file_exists($this->mLogFile)) {
			if (unlink($this->mLogFile))
				$result = true;
		} else
			$result = true;

		return $result;
	}

	function setName($newName) {
		$result = false;

		if ($this->mrRootDb->execute('UPDATE appcentral_reps '.'SET name='.$this->mrRootDb->formatText($newName).' '.'WHERE id='.$this->mId)) {
			$this->mName = $newName;
			$result = true;
		}

		return $result;
	}

	function setDescription($newDescription) {
		$result = false;

		if ($this->mrRootDb->execute('UPDATE appcentral_reps '.'SET description='.$this->mrRootDb->formatText($newDescription).' '.'WHERE id='.$this->mId)) {
			$this->mDescription = $newDescription;
			$result = true;
		}

		return $result;
	}

	function setLogEvents($logEvents) {
		$result = false;

		if ($this->mrRootDb->execute('UPDATE appcentral_reps '.'SET logevents='.$this->mrRootDb->formatText($logEvents ? $this->mrRootDb->fmttrue : $this->mrRootDb->fmtfalse).' '.'WHERE id='.$this->mId)) {
			$this->mLogEvents = $logEvents;
			$result = true;
		}

		return $result;
	}

	// ----- Applications methods -----

	function EnableApplication($appId) {
		$result = false;

		$app_check = & $this->mrRootDb->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->mId.' '.'AND applicationid='.$appId);

		if ($app_check->getNumberRows() == 0) {
			if ($this->mrRootDb->execute('INSERT INTO appcentral_reps_applications '.'VALUES('.$this->mId.','.$appId.')'))
				$result = true;
		} else
			$result = true;

		return $result;
	}

	function DisableApplication($appId) {
		$result = false;

		if ($this->mrRootDb->execute('DELETE FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->mId.' '.'AND applicationid='.$appId))
			$result = true;

		return $result;
	}

	function AvailableApplicationsList() {
		$result = array();

		if ($this->mProfileId) {
			$app_query = & $this->mrRootDb->execute('SELECT appcentral_reps_applications.applicationid AS applicationid '.'FROM appcentral_reps_applications, appcentral_reps_access '.'WHERE appcentral_reps_applications.repositoryid='.$this->mId.' '.'AND appcentral_reps_applications.repositoryid=appcentral_reps_access.repositoryid '.'AND appcentral_reps_access.profileid='.$this->mProfileId);
		} else {
			$app_query = & $this->mrRootDb->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->mId);
		}

		while (!$app_query->eof) {
			$result[] = $app_query->getFields('applicationid');
			$app_query->moveNext();
		}

		return $result;
	}

	function AvailableApplicationVersionsList($applicationId) {
		$result = array();

		$app = new AppCentralApplication($this->mrRootDb, $applicationId);
		$versions = $app->GetVersionsList(true);

		while (list (, $version) = each($versions)) {
			$version_query = & $this->mrRootDb->execute('SELECT * '.'FROM appcentral_applications_versions '.'WHERE applicationid='.$applicationId.' '.'AND version='.$this->mrRootDb->formatText($version));

			$result[$version]['date'] = $version_query->getFields('date');
			$result[$version]['dependencies'] = $version_query->getFields('dependencies');
			$result[$version]['suggestions'] = $version_query->getFields('suggestions');
		}

		return $result;
	}

	function CheckApplication($applicationId) {
		$result = false;

		if ($this->mProfileId) {
			$app_query = & $this->mrRootDb->execute('SELECT applicationid '.'FROM appcentral_reps_access,appcentral_reps_applications '.'WHERE appcentral_reps_access.profileid='.$this->mProfileId.' '.'AND appcentral_reps_access.repositoryid=appcentral_reps_applications.repositoryid '.'AND appcentral_reps_applications.applicationid='.$applicationId);
		} else {
			$app_query = & $this->mrRootDb->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->mId.' '.'AND applicationid='.$applicationId);
		}

		if ($app_query->getNumberRows())
			$result = true;

		return $result;
	}

	function SendApplication($applicationId, $version = '') {
		$result = '';

		if ($this->CheckApplication($applicationId)) {
			$application = new AppCentralApplication($this->mrRootDb, $applicationId);
			$result = $application->Retrieve($version);

			if ($result)
				$this->logEvent('Sent application '.$application->mApplication.' to user '.$this->mUser.' ('.$_SERVER['REMOTE_ADDR'].')');
		}

		return $result;
	}

	// ----- Profiles methods -----

	function EnableProfile($profileId) {
		$result = false;

		$profile_check = & $this->mrRootDb->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->mId.' '.'AND profileid='.$profileId);

		if ($profile_check->getNumberRows() == 0) {
			if ($this->mrRootDb->execute('INSERT INTO appcentral_reps_access '.'VALUES('.$this->mId.','.$profileId.')'))
				$result = true;
		} else
			$result = true;

		return $result;
	}

	function DisableProfile($profileId) {
		$result = false;

		if ($this->mrRootDb->execute('DELETE FROM appcentral_reps_access '.'WHERE repositoryid='.$this->mId.' '.'AND profileid='.$profileId))
			$result = true;

		return $result;
	}

	function AvailableProfilesList() {
		$result = array();

		$profile_query = & $this->mrRootDb->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->mId);

		while (!$profile_query->eof) {
			$result[] = $profile_query->getFields('profileid');
			$profile_query->moveNext();
		}

		return $result;
	}

	function CheckProfile($profileId) {
		$result = false;

		$profile_query = & $this->mrRootDb->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->mId.' '.'AND profileid='.$profileId);

		if ($profile_query->getNumberRows())
			$result = true;

		return $result;
	}
}

?>