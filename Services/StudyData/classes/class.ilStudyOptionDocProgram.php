<?php
/* fau: studyData - new class ilStudyOptionDocProgram. */

require_once(__DIR__ . '/abstract/class.ilStudyOption.php');

/**
 * Structured doc program
 */
class ilStudyOptionDocProgram extends ilStudyOption
{
    /** @var integer */
    public $id;

    /** @var string */
    public $text;

    /** @var ilDateTime|null */
    public $end;

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

        $query = "SELECT prog_id, prog_text, prog_end FROM study_doc_prog";
        if (!empty($ids)) {
            $query .= " WHERE " . $ilDB->in('prog_id', $ids, false, 'integer');
        }
        $query .= " ORDER BY prog_text";
        $result = $ilDB->query($query);

        $options = [];
        while ($row = $ilDB->fetchAssoc($result)) {
            $option = new static;
            $option->id = $row['prog_id'];
            $option->text = $row['prog_text'];
            if (!empty($row['prog_end'])) {
                try {
                    $option->end = new ilDateTime($row['prog_end'], IL_CAL_DATETIME);
                }
                catch (ilDateTimeException $e) {
                    $option->end = null;
                }
            }
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

        $query = "DELETE FROM study_doc_prog";
        if (!empty($ids)) {
            $query .= "WHERE" . $ilDB->in('prog_id', $ids, false, 'integer');
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

        $prog_end = ($this->end instanceof ilDateTime) ? $this->end->get(IL_CAL_DATETIME) : null;

        $query = "REPLACE INTO study_doc_prog(prog_id, prog_text, prog_end) VALUES ("
            . $ilDB->quote($this->id, 'integer') . ', '
            . $ilDB->quote($this->text, 'text') . ', '
            . $ilDB->quote($prog_end, 'text') . ')';

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
        global $DIC;
        $lng = $DIC->language();

        $text = $this->text. ' [' . $this->id . ']';

        if ($this->end instanceof ilDateTime) {
            $text .=  ' ' . $lng->txt('studydata_doc_prog_until') . ' ' . ilDatePresentation::formatDate($this->end);
        }
        return $text;
    }
}