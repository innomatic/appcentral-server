<?php

class AppCentralApplication {
	var $mrRootDb;
	var $mId;
	var $mApplication;
	var $mDescription;
	var $mLastVersion;
	var $mCategory;

	public function __construct($rrootDb, $applicationId = 0) {
		$this->mrRootDb = $rrootDb;

		if ($applicationId) {
			$app_query = $this->mrRootDb->execute('SELECT appid,description,lastversion,category FROM appcentral_applications WHERE id='.$applicationId);

			if ($app_query->getNumberRows()) {
				$this->mId = $applicationId;
				$this->mApplication = $app_query->getFields('appid');
				$this->mDescription = $app_query->getFields('description');
				$this->mLastVersion = $app_query->getFields('lastversion');
				$this->mCategory = $app_query->getFields('category');
			}
		}
	}

	protected function create($appName, $appDescription, $appCategory, $appLastVersion) {
		$result = false;

		$app_id = $this->mrRootDb->getNextSequenceValue('appcentral_applications_id_seq');

		if ($this->mrRootDb->execute('INSERT INTO appcentral_applications VALUES ('.$app_id.','.$this->mrRootDb->formatText($appName).','.$this->mrRootDb->formatText($appDescription).','.$this->mrRootDb->formatText($appLastVersion).','.$this->mrRootDb->formatText($appCategory).')')) {
			$this->mId = $app_id;
			$this->mApplication = $appName;
			$this->mDescription = $appDescription;
			$this->mCategory = $appCategory;
			$this->mLastVersion = $appLastVersion;

			$result = true;
		}

		return $result;
	}

	public function remove($checkVersions = true) {
		$result = false;

		if ($this->mrRootDb->execute('DELETE FROM appcentral_applications WHERE id='.$this->mId)) {
			$this->mrRootDb->execute('DELETE FROM appcentral_reps_applications WHERE applicationid='.$this->mId);

			if ($checkVersions) {
				$versions = $this->getVersionsList();

				while (list (, $version) = each($versions)) {
					$this->removeVersion($version, false);
				}
			}

			$this->mId = 0;
			$this->mApplication = '';
			$this->mDescription = '';

			$result = true;
		}

		return $result;
	}

	public function addVersion($filePath) {
		$result = false;

		if (file_exists($filePath)) {
			$orig_tmp_dir = $tmp_dir = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/temp/appcentral-server/'.md5(uniqid(rand()));
			mkdir($tmp_dir);

			require_once('innomatic/io/archive/Archive.php');
			require_once('innomatic/application/Application.php');
            
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
				require_once('innomatic/application/Application.php');
				$gen_config = Application::parseApplicationDefinition($tmp_dir.'/setup/application.xml');

				$app_name = $gen_config['ApplicationIdName'];
				$app_version = $gen_config['ApplicationVersion'];
				$app_date = $gen_config['ApplicationDate'];
				$app_description = $gen_config['ApplicationDescription'];
				$app_dependencies = $gen_config['ApplicationDependencies'];
				$app_suggestions = $gen_config['ApplicationSuggestions'];
				$app_category = $gen_config['ApplicationCategory'];

				if (!$this->mId) {
					$appcheck_query = $this->mrRootDb->execute(
                                                'SELECT * FROM appcentral_applications WHERE appid='.$this->mrRootDb->formatText($app_name));

					if ($appcheck_query->getNumberRows()) {
						$this->mId = $appcheck_query->getFields('id');
						$this->mApplication = $appcheck_query->getFields('appid');
						$this->mDescription = $appcheck_query->getFields('description');
						$this->mCategory = $appcheck_query->getFields('category');
						$this->mLastVersion = $appcheck_query->getFields('lastversion');
					} else
						$this->create($app_name, $app_description, $app_category, $app_version);
				}

				$version_check = $this->mrRootDb->execute(
                                        'SELECT version FROM appcentral_applications_versions WHERE applicationid='.$this->mId.' AND version='.$this->mrRootDb->formatText($app_version));

				if (!$version_check->getNumberRows()) {
					$this->mrRootDb->execute('INSERT INTO appcentral_applications_versions VALUES ('.$this->mId.','.$this->mrRootDb->formatText($app_version).','.$this->mrRootDb->formatText($app_date).','.$this->mrRootDb->formatText($app_dependencies).','.$this->mrRootDb->formatText($app_suggestions).')');
				} else {
					$this->mrRootDb->execute('UPDATE appcentral_applications_versions SET date='.$this->mrRootDb->formatText($app_date).',dependencies='.$this->mrRootDb->formatText($app_dependencies).',suggestions='.$this->mrRootDb->formatText($app_suggestions).' WHERE applicationid='.$this->mId.' AND version='.$this->mrRootDb->formatText($app_version));
				}

				$this->UpdateLastVersion();

				if ($this->mLastVersion == $app_version) {
					$this->SetDescription($app_description);
					$this->SetCategory($app_category);
				}

				@copy($filePath, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/applications/appcentral-server/'.$app_name.'-'.$app_version.'.tgz');
				$result = true;
			}

			// Clean up
			//
			require_once('innomatic/io/filesystem/DirectoryUtils.php');
			DirectoryUtils::unlinkTree($orig_tmp_dir);
		}

		return $result;
	}

	public function removeVersion($version = '', $applicationCheck = true) {
		$result = false;

		if (!strlen($version))
			$version = $this->mLastVersion;

		if ($this->mrRootDb->execute('DELETE FROM appcentral_applications_versions WHERE version='.$this->mrRootDb->formatText($version).' AND applicationid='.$this->mId)) {
			$this->mLastVersion = '';
			$this->UpdateLastVersion();

			@unlink(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/applications/appcentral-server/'.$this->mApplication.'-'.$version.'.tgz');
			$result = true;

			if ($applicationCheck) {
				$appcheck_query = $this->mrRootDb->execute('SELECT version FROM appcentral_applications_versions WHERE applicationid='.$this->mId);

				if ($appcheck_query->getNumberRows() == 0) {
					$this->Remove(false);
				}
			}
		}

		return $result;
	}

	public function getVersionsList($descendant = false) {
		$result = array();

		if ($versions_query = $this->mrRootDb->execute('SELECT version FROM appcentral_applications_versions WHERE applicationid='.$this->mId.' ORDER BY version'. ($descendant == true ? ' DESC' : ''))) {
			while (!$versions_query->eof) {
				$result[] = $versions_query->getFields('version');
				$versions_query->moveNext();
			}
		}

		return $result;
	}

	public function getVersionData($version = '') {
		$result = array();

		if (!$version)
			$version = $this->mLastVersion;

		$vers_query = $this->mrRootDb->execute('SELECT * FROM appcentral_applications_versions WHERE applicationid='.$this->mId.' AND version='.$this->mrRootDb->formatText($version));

		if ($vers_query->getNumberRows()) {
			$result = $vers_query->getFields();
		}

		return $result;
	}

	public function retrieve($version = '') {
		$result = '';

		if (!$version)
			$version = $this->mLastVersion;

		$versioncheck_query = $this->mrRootDb->execute('SELECT version FROM appcentral_applications_versions WHERE applicationid='.$this->mId.' AND version='.$this->mrRootDb->formatText($version));

		if ($versioncheck_query->getNumberRows()) {
			$app_file = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getHome().'core/applications/appcentral-server/'.$this->mApplication.'-'.$version.'.tgz';

			if (file_exists($app_file)) {
				if ($fh = fopen($app_file, 'rb')) {
					$result = fread($fh, filesize($app_file));
					fclose($fh);
				}
			}
		}

		return $result;
	}

	public function setDescription($newDescription) {
		$result = false;

		if ($this->mrRootDb->execute('UPDATE appcentral_applications SET description='.$this->mrRootDb->formatText($newDescription).' WHERE id='.$this->mId)) {
			$this->mDescription = $newDescription;
			$result = true;
		}

		return $result;
	}

	public function setCategory($newCategory) {
		$result = false;

		if ($this->mrRootDb->execute('UPDATE appcentral_applications SET category='.$this->mrRootDb->formatText($newCategory).' WHERE id='.$this->mId)) {
			$this->mDescription = $newCategory;
			$result = true;
		}

		return $result;
	}

	public function updateLastVersion() {
		require_once('innomatic/application/ApplicationDependencies.php');
		
		$result = true;

		$last_version = '0';
		$versions = $this->GetVersionsList();

		while (list (, $version) = each($versions)) {
			$compare = ApplicationDependencies::compareVersionNumbers($version, $last_version);
			if ($compare == ApplicationDependencies::VERSIONCOMPARE_EQUAL or $compare == ApplicationDependencies::VERSIONCOMPARE_MORE) {
				$last_version = $version;
			}
		}

		if ($last_version != $this->mLastVersion) {
			$this->mrRootDb->execute('UPDATE appcentral_applications SET lastversion='.$this->mrRootDb->formatText($last_version).' WHERE id='.$this->mId);

			$this->mLastVersion = $last_version;
		}

		return $result;
	}
}

?>