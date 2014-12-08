<?php

use \Innomatic\Process;

class AppcentralServerHookHandler extends HookHandler {
	public static function innomatic_webservicesprofile_remove_profileremoved($obj, $args) {
		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')
		    ->getDataAccess()
		    ->execute('DELETE FROM appcentral_reps_access WHERE profileid='.$obj->mProfileId);
		return Hook::RESULT_OK;
	}
}

?>
