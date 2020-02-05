<?php
/* fau: studyData - new class ilStudyOptionSchool. */

require_once(__DIR__ . '/abstract/class.ilStudyOption.php');

/**
 * Structured doc program
 */
class ilStudyOptionSchool extends ilStudyOption
{
    /** @var integer */
    public $id;

    /** @var string */
    public $title;

    /** @inheritdoc */
    protected static $cache;

    /** @inheritdoc */
    protected static $allCached;

    /**
     * @inheritDoc
     */
    protected static function _read(array $ids = null) : array
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "SELECT school_id, school_title FROM study_schools";
        if (!empty($ids)) {
            $query .= " WHERE " . $ilDB->in('school_id', $ids, false, 'integer');
        }
        $query .= " ORDER BY school_title";
        $result = $ilDB->query($query);

        $options = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $option = new static;
            $option->id = $row['school_id'];
            $option->title = $row['school_title'];
            $options[$option->id] = $option;
        }
        return $options;
    }


    /**
     * @inheritDoc
     */
    public static function _delete(array $ids = null)
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "DELETE FROM study_schools";
        if (!empty($ids)) {
            $query .= "WHERE" . $ilDB->in('school_id', $ids, false, 'integer');
        }
        $ilDB->manipulate($query);
    }


    /**
     * @inheritDoc
     */
    public function write()
    {
        global $DIC;
        $ilDB = $DIC->database();

        $query = "REPLACE INTO study_schools(school_id, school_title) VALUES ("
            . $ilDB->quote($this->id, 'integer') . ', '
            . $ilDB->quote($this->title, 'text'). ')';

        echo $query;
        $ilDB->manipulate($query);
    }


    /**
     * @inheritDoc
     */
    protected function getId() : int
    {
        return $this->id;
    }


    /**
     * @inheritDoc
     */
    protected function getText() : string
    {
        return $this->title. ' [' . $this->id . ']';
    }
}