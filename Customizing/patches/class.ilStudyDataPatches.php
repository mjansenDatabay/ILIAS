<?php
/**
 * fau: studyData - study data and idm related patches.
 */
class ilStudyDataPatches
{
    /**
     * Update the select options for study data
     */
	public function updateStudyDataOptions()
    {
        require_once('Services/Idm/classes/class.ilIdmData.php');
        $idmData = new ilIdmData();
        $idmData->updateDocPrograms();
        $idmData->updateStudyDegrees();
        $idmData->updateStudySchools();
        $idmData->updateStudySubjects();
    }
}