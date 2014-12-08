<?php

namespace Appcentral\Server;

use \Innomatic\Application\Application as InnomaticApplication;

class Application {
	protected $dataAccess;
	protected $id;
	protected $application;
	protected $description;
	protected $lastVersion;
	protected $category;

	public function __construct($applicationId = 0)
	{
	    $container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
		$this->dataAccess = $container->getDataAccess();

		if ($applicationId) {
			$app_query = $this->dataAccess->execute(
			    'SELECT appid,description,lastversion,category ' .
			    'FROM appcentral_applications ' .
			    'WHERE id=' . $applicationId
            );

			if ($app_query->getNumberRows()) {
				$this->id          = $applicationId;
				$this->application = $app_query->getFields('appid');
				$this->description = $app_query->getFields('description');
				$this->lastVersion = $app_query->getFields('lastversion');
				$this->category    = $app_query->getFields('category');
			}
		}
	}

	protected function create($appName, $appDescription, $appCategory, $appLastVersion)
	{
		$result = false;

		$app_id = $this->dataAccess->getNextSequenceValue('appcentral_applications_id_seq');

		if ($this->dataAccess->execute(
		    'INSERT INTO appcentral_applications ' .
		    'VALUES (' . $app_id . ',' .
		    $this->dataAccess->formatText($appName) . ',' .
		    $this->dataAccess->formatText($appDescription) . ',' .
		    $this->dataAccess->formatText($appLastVersion) . ',' .
		    $this->dataAccess->formatText($appCategory) .')')
        ) {
			$this->id          = $app_id;
			$this->application = $appName;
			$this->description = $appDescription;
			$this->category    = $appCategory;
			$this->lastVersion = $appLastVersion;

			$result = true;
		}

		return $result;
	}

	public function remove($checkVersions = true)
	{
		$result = false;

		if ($this->dataAccess->execute(
		    'DELETE FROM appcentral_applications ' .
		    'WHERE id=' . $this->id)
        ) {
			$this->dataAccess->execute(
			    'DELETE FROM appcentral_reps_applications ' .
			    'WHERE applicationid=' . $this->id
            );

			if ($checkVersions) {
				$versions = $this->getVersionsList();

				while (list (, $version) = each($versions)) {
					$this->removeVersion($version, false);
				}
			}

			$this->id = 0;
			$this->application = '';
			$this->description = '';

			$result = true;
		}

		return $result;
	}

	public function addVersion($filePath)
	{
		$result = false;

		if (file_exists($filePath)) {
			$orig_tmp_dir = $tmp_dir = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() .
			    'core/temp/appcentral-server/' . md5(uniqid(rand()));
			mkdir($tmp_dir);

			require_once('innomatic/io/archive/Archive.php');
            
			$app_archive = new Archive($filePath, Archive::FORMAT_TGZ);
			$app_archive->extract($tmp_dir);

			// Check if the files are into a directory instead of the root
			//
			if (!is_dir($tmp_dir.'/setup')) {
				$dhandle = opendir($tmp_dir);
				while (false != ($file = readdir($dhandle))) {
					if ($file != '.' && $file != '..' && (is_dir($tmp_dir.'/'.$file.'/setup') or is_dir($tmp_dir.'/'.$file.'/innomatic/setup'))) {
						if (is_dir($tmp_dir.'/'.$file.'/innomatic/setup')) {
							// Handles innomatic archive special case.
							$tmp_dir = $tmp_dir.'/'.$file.'/innomatic';
						} else {
							// Normal application archive.
							$tmp_dir = $tmp_dir.'/'.$file;
						}
					}
				}
				closedir($dhandle);
			}

			// Check for definition and structure files
			//
			if (file_exists($tmp_dir.'/setup/application.xml')) {
				$gen_config = InnomaticApplication::parseApplicationDefinition($tmp_dir.'/setup/application.xml');

				$app_name = $gen_config['ApplicationIdName'];
				$app_version = $gen_config['ApplicationVersion'];
				$app_date = $gen_config['ApplicationDate'];
				$app_description = $gen_config['ApplicationDescription'];
				$app_dependencies = $gen_config['ApplicationDependencies'];
				$app_suggestions = $gen_config['ApplicationSuggestions'];
				$app_category = $gen_config['ApplicationCategory'];

				if (!$this->id) {
					$appcheck_query = $this->dataAccess->execute(
                        'SELECT * ' .
					    'FROM appcentral_applications ' .
					    'WHERE appid='.$this->dataAccess->formatText($app_name)
                    );

					if ($appcheck_query->getNumberRows()) {
						$this->id = $appcheck_query->getFields('id');
						$this->application = $appcheck_query->getFields('appid');
						$this->description = $appcheck_query->getFields('description');
						$this->category = $appcheck_query->getFields('category');
						$this->lastVersion = $appcheck_query->getFields('lastversion');
					} else
						$this->create($app_name, $app_description, $app_category, $app_version);
				}

				$version_check = $this->dataAccess->execute(
                    'SELECT version ' .
				    'FROM appcentral_applications_versions ' .
				    'WHERE applicationid=' . $this->id .
				    ' AND version=' . $this->dataAccess->formatText($app_version)
                );

				if (!$version_check->getNumberRows()) {
					$this->dataAccess->execute(
					    'INSERT INTO appcentral_applications_versions ' .
					    'VALUES (' . $this->id . ',' .
					    $this->dataAccess->formatText($app_version) . ',' .
					    $this->dataAccess->formatText($app_date) . ',' .
					    $this->dataAccess->formatText($app_dependencies) . ',' .
					    $this->dataAccess->formatText($app_suggestions) . ')'
                    );
				} else {
					$this->dataAccess->execute(
					    'UPDATE appcentral_applications_versions ' .
					    'SET date=' . $this->dataAccess->formatText($app_date) .
					    ',dependencies=' . $this->dataAccess->formatText($app_dependencies) .
					    ',suggestions=' . $this->dataAccess->formatText($app_suggestions) . 
					    ' WHERE applicationid=' . $this->id .
					    ' AND version=' . $this->dataAccess->formatText($app_version)
                    );
				}

				$this->UpdateLastVersion();

				if ($this->lastVersion == $app_version) {
					$this->SetDescription($app_description);
					$this->SetCategory($app_category);
				}

				@copy($filePath, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() .
				    'core/applications/appcentral-server/'.$app_name.'-'.$app_version.'.tgz');
				
				$result = true;
			}

			// Clean up
			//
			\Innomatic\Io\Filesystem\DirectoryUtils::unlinkTree($orig_tmp_dir);
		}

		return $result;
	}

	public function removeVersion($version = '', $applicationCheck = true)
	{
		$result = false;

		if (!strlen($version))
			$version = $this->lastVersion;

		if ($this->dataAccess->execute(
		    'DELETE FROM appcentral_applications_versions ' .
		    'WHERE version=' . $this->dataAccess->formatText($version) .
		    ' AND applicationid='.$this->id)
        ) {
			$this->lastVersion = '';
			$this->UpdateLastVersion();

			@unlink(
			    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() .
			    'core/applications/appcentral-server/' .
			    $this->application . '-' . $version . '.tgz'
            );
			
			$result = true;

			if ($applicationCheck) {
				$appcheck_query = $this->dataAccess->execute(
				    'SELECT version ' .
				    'FROM appcentral_applications_versions ' .
				    'WHERE applicationid='.$this->id
                );

				if ($appcheck_query->getNumberRows() == 0) {
					$this->Remove(false);
				}
			}
		}

		return $result;
	}

	public function getVersionsList($descendant = false)
	{
		$result = array();

		if ($versions_query = $this->dataAccess->execute(
		    'SELECT version ' .
		    'FROM appcentral_applications_versions ' .
		    'WHERE applicationid=' . $this->id .
		    ' ORDER BY version' . ($descendant == true ? ' DESC' : ''))
		) {
			while (!$versions_query->eof) {
				$result[] = $versions_query->getFields('version');
				$versions_query->moveNext();
			}
		}

		return $result;
	}

	public function getVersionData($version = '')
	{
		$result = array();

		if (!$version)
			$version = $this->lastVersion;

		$vers_query = $this->dataAccess->execute(
		    'SELECT * ' .
		    'FROM appcentral_applications_versions ' .
		    'WHERE applicationid=' . $this->id .
		    ' AND version='.$this->dataAccess->formatText($version)
		);

		if ($vers_query->getNumberRows()) {
			$result = $vers_query->getFields();
		}

		return $result;
	}

	public function retrieve($version = '')
	{
		$result = '';

		if (!$version)
			$version = $this->lastVersion;

		$versioncheck_query = $this->dataAccess->execute(
		    'SELECT version ' .
		    'FROM appcentral_applications_versions ' .
		    'WHERE applicationid=' . $this->id .
		    ' AND version=' . $this->dataAccess->formatText($version)
		);

		if ($versioncheck_query->getNumberRows()) {
			$app_file = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome() .
			    'core/applications/appcentral-server/' .
			    $this->application . '-' . $version . '.tgz';

			if (file_exists($app_file)) {
				if ($fh = fopen($app_file, 'rb')) {
					$result = fread($fh, filesize($app_file));
					fclose($fh);
				}
			}
		}

		return $result;
	}

	public function setDescription($newDescription)
	{
		if ($this->dataAccess->execute(
		    'UPDATE appcentral_applications ' .
		    'SET description=' . $this->dataAccess->formatText($newDescription) .
		    ' WHERE id='.$this->id)
		) {
			$this->description = $newDescription;
			return true;
		} else {
		    return false;
		}
	}

	public function setCategory($newCategory)
	{
		if ($this->dataAccess->execute(
		    'UPDATE appcentral_applications ' .
		    'SET category=' . $this->dataAccess->formatText($newCategory) .
		    ' WHERE id='.$this->id)
		) {
			$this->description = $newCategory;
			return true;
		} else {
		    return false;
		}
	}

	public function updateLastVersion()
	{
		$result = true;

		$last_version = '0';
		$versions = $this->getVersionsList();

		while (list (, $version) = each($versions)) {
			$compare = \Innomatic\Application\ApplicationDependencies::compareVersionNumbers($version, $last_version);

			if ($compare == \Innomatic\Application\ApplicationDependencies::VERSIONCOMPARE_EQUAL
			    or $compare == \Innomatic\Application\ApplicationDependencies::VERSIONCOMPARE_MORE) {
				$last_version = $version;
			}
		}

		if ($last_version != $this->lastVersion) {
			$this->dataAccess->execute(
			    'UPDATE appcentral_applications ' .
			    'SET lastversion=' . $this->dataAccess->formatText($last_version) .
			    ' WHERE id=' . $this->id
		    );

			$this->lastVersion = $last_version;
		}

		return $result;
	}
}

?>