<?php
/**
 * fau: studyData - study data and idm related patches.
 */
class ilStudyDataPatches
{
	/**
	* Update the study data codes
	*/
	public function updateStudyDataCodes()
	{
		global $ilDB;

		$path = CLIENT_DATA_DIR . "/study_data/";
		$file_degrees = "AbschlArt.unl";
		$file_subjects = "StudFach.unl";
		$file_schools = "Fakultaet.unl";

		// degrees
		$query = "TRUNCATE TABLE study_degrees";
		$ilDB->query($query);

		$degrees = file($path.$file_degrees);
		foreach ($degrees as $degree)
		{
			$ddata = explode("#", $degree);

			if (is_numeric($ddata[0]))
			{
				$query = "
					INSERT INTO study_degrees(degree_id, degree_title)
					VALUES ("
				. 	$ilDB->quote($ddata[0],"integer"). ", "
				. 	$ilDB->quote(utf8_encode($ddata[1]))
				.   ")";

				$ilDB->query($query);
			}
		}

		// subjects
		$query = "TRUNCATE TABLE study_subjects";
		$ilDB->query($query);

		$subjects = file($path.$file_subjects);
		foreach ($subjects as $subject)
		{
			$sdata = explode("#", $subject);

			if (is_numeric($sdata[0]))
			{
				$query = "
					INSERT INTO study_subjects(subject_id, subject_title)
					VALUES ("
				. 	$ilDB->quote($sdata[0]). ", "
				. 	$ilDB->quote(utf8_encode($sdata[1]))
				.   ")";

				$ilDB->query($query);
			}
		}

		// schools
		$query = "TRUNCATE TABLE study_schools";
		$ilDB->query($query);

		$schools = file($path.$file_schools);
		foreach ($schools as $school)
		{
			$sdata = explode("#", $school);

			if (is_numeric($sdata[0]))
			{
				$query = "
					INSERT INTO study_schools(school_id, school_title)
					VALUES ("
				. 	$ilDB->quote($sdata[0]). ", "
				. 	$ilDB->quote(utf8_encode($sdata[1]))
				.   ")";

				$ilDB->query($query);
			}
		}
	}

    /**
     * Update the select options for study data
     */
	public function updateStudyDataOptions()
    {
        require_once('Services/Idm/classes/class.ilIdmData.php');
        $idmData = new ilIdmData();
        $idmData->updateDocPrograms();
    }
}