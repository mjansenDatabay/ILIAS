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
        require_once "Services/StudyData/classes/class.ilStudyOptionDegree.php";
        require_once "Services/StudyData/classes/class.ilStudyOptionSchool.php";
        require_once "Services/StudyData/classes/class.ilStudyOptionSubject.php";
        require_once "Services/StudyData/classes/class.ilStudyOptionDocProgram.php";

        global $DIC;
        $lng = $DIC->language();

        $text = '';
        $studydata = self::_readStudyData($a_user_id);
        foreach ($studydata as $study)
        {
            $study_text = "";
            foreach ($study["subjects"] as $subject)
            {
                $study_text .= $study_text ? ", " : "";
                $study_text .= ilStudyOptionSubject::_lookupText($subject['subject_id']);
                $study_text .= sprintf($lng->txt('studydata_semester_text'), $subject["semester"]);
            }
            $study_text .= "\n".ilStudyOptionDegree::_lookupText($study["degree_id"]);
            $study_text .= "\n".ilStudyOptionSchool::_lookupText($study["school_id"]);

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

        $docdata = self::_readDocData($a_user_id);
        $doc_text = ilStudyOptionDocProgram::_lookupText($docdata['prog_id']);

        if (!empty($doc_text)) {
            $text .= $text ? "\n\n" : "";
            $text .= $doc_text;
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
     * read the doc programme data of a user
     * @param 	integer		user_id
     * @return 	array		['prog_id' => int|null, 'prog_approval' => ilDate|null]
     * @see		ilStudyAccess::_getDocData
     */
    static function _readDocData($a_user_id)
    {
        // don't use the cache
        return ilStudyAccess::_getDocData($a_user_id, false);
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

    /**
     * Save the doc programme data for a user
     *
     * @param    int     	user id
     * @param    int|null   	doc programme id
     * @param    ilDate|null   	doc programme approval date
     */
    static function _saveDocData($a_user_id, $a_prog_id = null, $a_prog_approval = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

        if (empty($a_prog_id) && empty($a_prog_approval_date)) {
            $query = "DELETE from usr_doc_prog"
                . 		" WHERE usr_id=". $ilDB->quote($a_user_id,'integer');
            $ilDB->manipulate($query);
        }

        $approval_str = null;
        if ($a_prog_approval instanceof ilDate) {
            $approval_str = $a_prog_approval->get(IL_CAL_DATE);
        }

        $query = "  REPLACE into usr_doc_prog(usr_id, prog_id, prog_approval) VALUES("
            . $ilDB->quote($a_user_id,'integer'). ","
            . $ilDB->quote($a_prog_id,'integer'). ","
            . $ilDB->quote($approval_str,'text')
            . ")";

        $ilDB->query($query);
    }
}

