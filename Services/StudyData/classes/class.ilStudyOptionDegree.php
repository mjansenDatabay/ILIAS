<?php
/* fau: studyData - new class ilStudyOptionDegree. */

require_once(__DIR__ . '/abstract/class.ilStudyOption.php');

/**
 * Structured doc program
 */
class ilStudyOptionDegree extends ilStudyOption
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

        $query = "SELECT degree_id, degree_title FROM study_degrees";
        if (!empty($ids)) {
            $query .= " WHERE " . $ilDB->in('degree_id', $ids, false, 'integer');
        }
        $query .= " ORDER BY degree_title";
        $result = $ilDB->query($query);

        $options = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $option = new static;
            $option->id = $row['degree_id'];
            $option->title = $row['degree_title'];
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

        $query = "DELETE FROM study_degrees";
        if (!empty($ids)) {
            $query .= "WHERE" . $ilDB->in('degree_id', $ids, false, 'integer');
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

        $query = "REPLACE INTO study_degrees(degree_id, degree_title) VALUES ("
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