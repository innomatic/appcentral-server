<?php

namespace Appcentral\Server;

class Repository {
	protected $dataAccess;
	protected $id;
	protected $name;
	protected $description;
	protected $profileId;
	protected $user;
	protected $logEvents;
	protected $logFile;
	protected $logHandler;

	public function __construct($repId = 0, $profileId = '', $user = '')
	{
	    $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
		$this->dataAccess = $container->getDataAccess();

		if ($repId) {
			$rep_query = $this->dataAccess->execute(
			    'SELECT name,description,logevents ' .
			    'FROM appcentral_reps ' . 
			    'WHERE id='.$repId
            );

			if ($rep_query->getNumberRows()) {
				$this->id = $repId;
				$this->name = $rep_query->getFields('name');
				$this->description = $rep_query->getFields('description');
				$this->profileId = $profileId;
				$this->user = $user;
				$this->logEvents = $rep_query->getFields('logevents') == $this->dataAccess->fmttrue ? true : false;
				if ($this->logEvents) {
					$this->logFile = $container->getHome().'core/applications/'.'appcentral-server/repository_'.$this->id.'.log';
					$this->logHandler = new \Innomatic\Logging\Logger($this->logFile);
				}
			}
		}
	}

	public function create($repName, $repDescription, $repLogEvents)
	{
		$result = false;

		$rep_id = $this->dataAccess->getNextSequenceValue('appcentral_reps_id_seq');

		if ($this->dataAccess->execute(
		    'INSERT INTO appcentral_reps ' .
		    'VALUES (' . $rep_id .
		    ',' . $this->dataAccess->formatText($repName) .
		    ',' . $this->dataAccess->formatText($repDescription) .
		    ',' . $this->dataAccess->formatText($repLogEvents == true ? $this->dataAccess->fmttrue : $this->dataAccess->fmtfalse) . ')')
        ) {
			$this->id = $rep_id;
			$this->name = $repName;
			$this->description = $repDescription;
			$this->logEvents = $repLogEvents;
			if ($this->logEvents) {
				$this->logFile = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() .
				    'core/applications/'.'appcentral-server/repository_'.$this->id.'.log';

				$this->logHandler = new Logger($this->logFile);
			}

			$result = true;
		}

		return $result;
	}

	public function remove()
	{
		$result = false;

		if ($this->dataAccess->execute('DELETE FROM appcentral_reps '.'WHERE id='.$this->id)) {
			$this->dataAccess->execute('DELETE FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->id);

			$this->dataAccess->execute('DELETE FROM appcentral_reps_access '.'WHERE repositoryid='.$this->id);

			if (file_exists($this->logFile))
				unlink($this->mLogfile);
			$this->id = 0;
			$this->name = '';
			$this->description = '';

			$result = true;
		}

		return $result;
	}

	public function logEvent($event, $type = Logger::NOTICE)
	{
		if ($this->logEvents)
			$this->logHandler->logEvent($this->name, $event, $type);

		return true;
	}

	public function getLogContent()
	{
		$result = '';

		if (file_exists($this->logFile) and $fh = fopen($this->logFile, 'r')) {
			$result = fread($fh, filesize($this->logFile));
			fclose($fh);
		}

		return $result;
	}

	public function eraseLog()
	{
		$result = false;

		if (file_exists($this->logFile)) {
			if (unlink($this->logFile)) {
				$result = true;
			}
		} else {
			$result = true;
		}

		return $result;
	}

	public function setName($newName)
	{
		$result = false;

		if ($this->dataAccess->execute('UPDATE appcentral_reps '.'SET name='.$this->dataAccess->formatText($newName).' '.'WHERE id='.$this->id)) {
			$this->name = $newName;
			$result = true;
		}

		return $result;
	}

	public function setDescription($newDescription)
	{
		$result = false;

		if ($this->dataAccess->execute('UPDATE appcentral_reps '.'SET description='.$this->dataAccess->formatText($newDescription).' '.'WHERE id='.$this->id)) {
			$this->description = $newDescription;
			$result = true;
		}

		return $result;
	}

	public function setLogEvents($logEvents)
	{
		$result = false;

		if ($this->dataAccess->execute('UPDATE appcentral_reps '.'SET logevents='.$this->dataAccess->formatText($logEvents ? $this->dataAccess->fmttrue : $this->dataAccess->fmtfalse).' '.'WHERE id='.$this->id)) {
			$this->logEvents = $logEvents;
			$result = true;
		}

		return $result;
	}

	// ----- Applications methods -----

	public function enableApplication($appId)
	{
		$result = false;

		$app_check = $this->dataAccess->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->id.' '.'AND applicationid='.$appId);

		if ($app_check->getNumberRows() == 0) {
			if ($this->dataAccess->execute('INSERT INTO appcentral_reps_applications '.'VALUES('.$this->id.','.$appId.')')) {
				$result = true;
			}
		} else {
			$result = true;
		}

		return $result;
	}

	public function disableApplication($appId)
	{
		if ($this->dataAccess->execute('DELETE FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->id.' '.'AND applicationid='.$appId)) {
			return true;
		} else {
		    return false;
		}
	}

	public function availableApplicationsList()
	{
		$result = array();

		if ($this->profileId) {
			$app_query = $this->dataAccess->execute('SELECT appcentral_reps_applications.applicationid AS applicationid '.'FROM appcentral_reps_applications, appcentral_reps_access '.'WHERE appcentral_reps_applications.repositoryid='.$this->id.' '.'AND appcentral_reps_applications.repositoryid=appcentral_reps_access.repositoryid '.'AND appcentral_reps_access.profileid='.$this->profileId);
		} else {
			$app_query = $this->dataAccess->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->id);
		}

		while (!$app_query->eof) {
			$result[] = $app_query->getFields('applicationid');
			$app_query->moveNext();
		}

		return $result;
	}

	public function availableApplicationVersionsList($applicationId)
	{
		$result = array();

		$app = new Application($applicationId);
		$versions = $app->getVersionsList(true);

		while (list (, $version) = each($versions)) {
			$version_query = $this->dataAccess->execute('SELECT * '.'FROM appcentral_applications_versions '.'WHERE applicationid='.$applicationId.' '.'AND version='.$this->dataAccess->formatText($version));

			$result[$version]['date'] = $version_query->getFields('date');
			$result[$version]['dependencies'] = $version_query->getFields('dependencies');
			$result[$version]['suggestions'] = $version_query->getFields('suggestions');
		}

		return $result;
	}

	public function checkApplication($applicationId)
	{
		$result = false;

		if ($this->profileId) {
			$app_query = $this->dataAccess->execute('SELECT applicationid '.'FROM appcentral_reps_access,appcentral_reps_applications '.'WHERE appcentral_reps_access.profileid='.$this->profileId.' '.'AND appcentral_reps_access.repositoryid=appcentral_reps_applications.repositoryid '.'AND appcentral_reps_applications.applicationid='.$applicationId);
		} else {
			$app_query = $this->dataAccess->execute('SELECT applicationid '.'FROM appcentral_reps_applications '.'WHERE repositoryid='.$this->id.' '.'AND applicationid='.$applicationId);
		}

		if ($app_query->getNumberRows()) {
			$result = true;
		}

		return $result;
	}

	public function sendApplication($applicationId, $version = '')
	{
		$result = '';

		if ($this->checkApplication($applicationId)) {
			$application = new Application($applicationId);
			$result = $application->Retrieve($version);

			if ($result) {
				$this->logEvent('Sent application '.$application->mApplication.' to user '.$this->user.' ('.$_SERVER['REMOTE_ADDR'].')');
			}
		}

		return $result;
	}

	// ----- Profiles methods -----

	public function enableProfile($profileId)
	{
		$result = false;

		$profile_check = $this->dataAccess->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->id.' '.'AND profileid='.$profileId);

		if ($profile_check->getNumberRows() == 0) {
			if ($this->dataAccess->execute('INSERT INTO appcentral_reps_access '.'VALUES('.$this->id.','.$profileId.')')) {
				$result = true;
			}
		} else {
			$result = true;
		}

		return $result;
	}

	public function disableProfile($profileId)
	{
		if ($this->dataAccess->execute('DELETE FROM appcentral_reps_access '.'WHERE repositoryid='.$this->id.' '.'AND profileid='.$profileId)) {
			return true;
		} else {
		    return false;
		}
	}

	public function availableProfilesList()
	{
		$result = array();

		$profile_query = $this->dataAccess->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->id);

		while (!$profile_query->eof) {
			$result[] = $profile_query->getFields('profileid');
			$profile_query->moveNext();
		}

		return $result;
	}

	public function checkProfile($profileId)
	{
		$result = false;

		$profile_query = $this->dataAccess->execute('SELECT profileid '.'FROM appcentral_reps_access '.'WHERE repositoryid='.$this->id.' '.'AND profileid='.$profileId);

		if ($profile_query->getNumberRows())
			$result = true;

		return $result;
	}
}

?>