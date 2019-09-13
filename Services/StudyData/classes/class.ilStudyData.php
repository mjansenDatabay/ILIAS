<?php
/* fim: [studydata] new class. */

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
* Class ilStudyData
*
* @author Fred Neumann <fred.neumann@fim.uni-erlangen.de> 
*
*/

class ilStudyData
{
	const TYPE_FULL = "V";
	const TYPE_PART = "T";
	const TYPE_NONE  = "";

	static $study_data_visible;

	/**
	 * get the privacy setting for the visibility of study data (cached)
	 * 
	 * @return boolean	study data visible to the current user (true false)
	 */
	static function _getStudyDataVisibility()
	{
		global $rbacsystem;

		if (!isset(self::$study_data_visible))
		{
			include_once 'Services/PrivacySecurity/classes/class.ilPrivacySettings.php';
			$privacy = ilPrivacySettings::_getInstance();

			if($rbacsystem->checkAccess('export_member_data',$privacy->getPrivacySettingsRefId()))
			{
            	self::$study_data_visible = true;
            }
            else
            {
                self::$study_data_visible = false;
            }
        }
		return self::$study_data_visible;
	}


	/**
	 * lookup a school title
	 * 
	 * @param	integer		school id
	 * @return 	string		school title
	 */
	static function _lookupSchool($a_school_id)
	{
		global $DIC;
		$ilDB = $DIC->database();
		
		$query = "SELECT school_title FROM study_schools"
		.		" WHERE school_id=". $ilDB->quote($a_school_id, 'integer');
		$result = $ilDB->query($query);
		if($row = $ilDB->fetchAssoc($result))
		{
			return $row["school_title"];
		}
		return '';
	}
	
	/**
	 * get an array of option for a school selection
	 * 
	 * @return array	school_id => school_title
	 */
	static function _getSchoolSelectOptions()
	{
		global $DIC;
		$ilDB = $DIC->database();
		$lng = $DIC->language();

		$query = "SELECT school_id, school_title FROM study_schools"
		.		" ORDER by school_title";
		$result = $ilDB->query($query);
		$options = array();
		$options[-1] = $lng->txt("please_select");
		while ($row = $ilDB->fetchAssoc($result))
		{
			$options[$row["school_id"]] = $row["school_title"]. " (" . $row["school_id"] . ")";
		}
		return $options;
	}

	/**
	 * get an array of option for a study type
	 *
	 * @return array	type_code => type title
	 */
	static function _getStudyTypeSelectOptions()
	{
		global $lng;
		$options = array(
			self::TYPE_NONE => $lng->txt("please_select"),
			self::TYPE_FULL => $lng->txt('studydata_type_full'),
			self::TYPE_PART => $lng->txt('studydata_type_part')
		);

		return $options;
	}


	/**
	 * lookup a subject title
	 * 
	 * @param	integer		subject id
	 * @return 	string		subject title
	 */
	static function _lookupSubject($a_subject_id)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$query = "SELECT subject_title FROM study_subjects"
		.		" WHERE subject_id=". $ilDB->quote($a_subject_id, 'integer');
		$result = $ilDB->query($query);
		if($row = $ilDB->fetchAssoc($result))
		{
			return $row["subject_title"] . " (" . $a_subject_id . ")";
		}
		return '';
	}

	/**
	 * get an array of option for a subject selection
	 * 
	 * @return array	subject_id => subject_title
	 */
	static function _getSubjectSelectOptions()
	{
		global $DIC;
		$ilDB = $DIC->database();
		$lng = $DIC->language();

		$query = "SELECT subject_id, subject_title FROM study_subjects"
		.		" ORDER by subject_title";
		$result = $ilDB->query($query);
		$options = array();
		$options[0] = $lng->txt("please_select");
		while ($row = $ilDB->fetchAssoc($result))
		{
			$options[$row["subject_id"]] = $row["subject_title"]. " (" . $row["subject_id"] . ")";
		}
		return $options;
	}

	/**
	 * lookup a degree title
	 * 
	 * @param	integer		$a_degree_id
	 * @return 	string		degree title
	 */
	static function _lookupDegree($a_degree_id)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$query = "SELECT degree_title FROM study_degrees"
		.		" WHERE degree_id=". $ilDB->quote($a_degree_id,'integer');
		$result = $ilDB->query($query);
		if($row = $ilDB->fetchAssoc($result))
		{
			return $row["degree_title"] . " (" . $a_degree_id . ")";
		}
		return '';
	}

	
	/**
	 * get an array of option for a degree selection
	 * 
	 * @return array	degree_id => degree_title
	 */
	static function _getDegreeSelectOptions()
	{
		global $DIC;
		$ilDB = $DIC->database();
		$lng = $DIC->language();

		$query = "SELECT degree_id, degree_title FROM study_degrees"
		.		" ORDER by degree_title";
		$result = $ilDB->query($query);
		$options = array();
		$options[0] = $lng->txt("please_select");
		while ($row = $ilDB->fetchAssoc($result))
		{
			$options[$row["degree_id"]] =  $row["degree_title"]. " (" . $row["degree_id"] . ")";
		}
		return $options;
	}


	/**
	 * get an array of option for a semester
	 * 
	 * @return array	semester code (e.g. 20112) => semester title (2011 SS)
	 */
	static function _getSemesterSelectOptions()
	{
	    global $lng;
	
		$options[''] = $lng->txt("please_select");
		for ($year = 2010; $year < date('Y') + 10; $year++)
		{
	        $options[(string) $year . '1'] = sprintf('%s SS', $year);
	        $options[(string) $year . '2'] = sprintf('%s / %s WS', $year, $year + 1);
		}
		return $options;
	}


	/**
	 * get a textual description of a user's study data
	 *
	 * @param int $a_user_id
	 * @return string	multi-line description
	 */
	static function _getStudyDataText($a_user_id)
	{
		$data = self::_readStudyData($a_user_id);
        return self::_getStudyDataTextForData($data);
	}


    /**
     * get a textual description of study data
     *
     * @param   array   study data
     * @return string	multi-line description
     */
	static function _getStudyDataTextForData($a_data)
    {
    	global $DIC;
    	$lng = $DIC->language();

        $text = '';
        foreach ($a_data as $study)
        {
            $study_text = "";
            foreach ($study["subjects"] as $subject)
            {
                $study_text .= $study_text ? ", " : "";
                $study_text .= self::_lookupSubject($subject['subject_id']);
                $study_text .= sprintf($lng->txt('studydata_semester_text'), $subject["semester"]);
            }
            $study_text .= "\n".self::_lookupDegree($study["degree_id"]);
            $study_text .= "\n".self::_lookupSchool($study["school_id"]);

            if (!empty($study_text))
            {
                $text .= $text ? "\n\n" : "";
                $text .= self::_getRefSemesterText($study['ref_semester']);
                if ($type_text = self::_getStudyTypeText($study['study_type']))
				{
					$text .= ' (' . $type_text . ')';
				}
				$text .= ' :';

				$text .= "\n". $study_text;
            }
        }

        return $text;
    }


	/**
	 * get the text for a reference semester
	 * 
	 * @param 	string	semester specification (e.g. 20112)
	 * @return 	string	textual description 
	 */
	static function _getRefSemesterText($a_ref_semester)
	{
		global $DIC;
		$lng = $DIC->language();
		
		if (substr($a_ref_semester, 4) == '1')
		{
			$reftext = sprintf($lng->txt('studydata_ref_semester_summer'), substr($a_ref_semester, 0, 4));
		}
		elseif (substr($a_ref_semester, 4) == '2')
		{
			$sem = (int) substr($a_ref_semester, 0, 4);
			$reftext = sprintf($lng->txt('studydata_ref_semester_winter'), $sem, $sem + 1);  
		}
		else
		{
			$reftext = sprintf($lng->txt('studydata_ref_semester_any'));	
		}

		return $reftext;
	}

	/**
	 * get the text for a study type
	 * @param  string $a_study_type
	 * @return string
	 */
	static function _getStudyTypeText($a_study_type)
	{
		global $DIC;
		$lng = $DIC->language();

		switch ($a_study_type)
		{
			case self::TYPE_PART:
				return $lng->txt('studydata_type_part');

			case ilStudyData::TYPE_FULL:
				return $lng->txt('studydata_type_full');

		}
		return '';
	}


	/**
	 * read the study data of a user
	 * @param 	integer		user_id
	 * @return 	array		list of assoc study data arrays with nested subject ids
	 * @see		ilStudyAccess::_getStudyData 
	 */
	static function _readStudyData($a_user_id)
	{
		// don't use the cache
		return ilStudyAccess::_getStudyData($a_user_id, false);
	}

	/**
	* Save the study data for a user
	*
	* @param    int     	user id
	* @param    array   	list of assoc study data arrays with nested subjects
	*/
	static function _saveStudyData($a_user_id, $a_data)
	{
		global $DIC;
		$ilDB = $DIC->database();

		$query = "DELETE from usr_study"
		. 		" WHERE usr_id=". $ilDB->quote($a_user_id,'integer');
		$ilDB->query($query);


		$query = "DELETE from usr_subject"
		. 		" WHERE usr_id=". $ilDB->quote($a_user_id,'integer');
		$ilDB->query($query);

		if (is_array($a_data))
		{
			$study_no = 1;
			foreach ($a_data as $study)
			{
				if (!$study["ref_semester"])
				{
					$study["ref_semester"] = ilStudyAccess::_getRunningSemesterString();
				}
				
				$query = "
					REPLACE into usr_study(
					usr_id, study_no, school_id, degree_id, ref_semester, study_type)
					VALUES("
					. $ilDB->quote($a_user_id,'integer'). ","
					. $ilDB->quote($study_no,'integer'). ","
					. $ilDB->quote($study["school_id"],'integer'). ","
					. $ilDB->quote($study["degree_id"],'integer').","
					. $ilDB->quote($study["ref_semester"],'text').","
					. $ilDB->quote($study["study_type"],'text')
					. ")";
				$ilDB->query($query);
				
				if (is_array($study["subjects"]))
				{
					$subject_no = 1;
					foreach ($study["subjects"] as $subject)
					{
						$query = "
							REPLACE into usr_subject(
							usr_id, study_no, subject_no, subject_id, semester)
							VALUES("
							. $ilDB->quote($a_user_id,'integer'). ","
							. $ilDB->quote($study_no,'integer'). ","
							. $ilDB->quote($subject_no,'integer'). ","
							. $ilDB->quote($subject['subject_id'],'integer').","
							. $ilDB->quote($subject['semester'],'integer')
							. ")";
						$ilDB->query($query);
						$subject_no++;
					}
				}
				$study_no++;	
			}
		}
	}	
}

