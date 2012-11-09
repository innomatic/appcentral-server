<?php

require_once('innomatic/process/HookHandler.php');

class AppcentralServerHookHandler extends HookHandler {
	public static function innomatic_webservicesprofile_remove_profileremoved($obj, $args) {
		InnomaticContainer::instance('innomaticcontainer')->getDataAccess()->execute('DELETE FROM appcentral_reps_access WHERE profileid='.$obj->mProfileId);
		return Hook::RESULT_OK;
	}
}

?>
