<?php
/* fau: exStatusFile - new class ilExAssignmentStatusFile. */

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Class ilExAssignmentStatusFile
 */
class ilExAssignmentStatusFile extends ilExcel
{
    const FORMAT_CSV = "Csv";

    /** @var ilExAssignment */
    protected $assignment;

    /** @var string */
    protected $format;

    /**
     * @var array[]  data of exercise members, indexed by usr_id
     * @see ilExAssignment::getMemberListData()
     */
    protected $members = [];

    /** @var ilExAssignmentTeam[]  indexed by team_id */
    protected $teams = [];

    /** @var string[] */
    protected $member_titles =  ['update','usr_id','login','lastname','firstname','status','mark','notice','comment', 'plagiarism', 'plag_comment'];

    /** @var string[] */
    protected $team_titles =  ['update','team_id','logins','status','mark','notice','comment', 'plagiarism', 'plag_comment'];

    /** @var string[] */
    protected $valid_states = ['notgraded','passed','failed'];

    /** @var string[] */
    protected $valid_plag_flags = ['none','suspicion','detected'];

    /** @var array[] row data to be used for updates */
    protected $updates = [];

    /** @var bool */
    protected $updates_applied = false;

    /** @var string */
    protected $error;

    /**
     * Initialize the data
     * @param ilExAssignment $assignment
     * @param array           $user_ids
     */
    public function init(ilExAssignment $assignment, $user_ids = null) {
        $this->members = [];
        $this->teams = [];
        $this->updates = [];
        $this->updates_applied = false;
        $this->error = null;
        $this->assignment = $assignment;

        $this->initMembers($user_ids);

        if ($this->assignment->getAssignmentType()->isSubmissionAssignedToTeam()) {
            $this->initTeams($user_ids);
        }
    }

    /**
     * Get valid file formats
     *
     * @return array
     */
    public function getValidFormats() {
        return array(self::FORMAT_XML, self::FORMAT_BIFF, self::FORMAT_CSV);
    }


    /**
     * Get the filename that should be used
     * @return string
     */
    public function getFilename() {
        switch($this->format) {
            case self::FORMAT_XML:
                return "status.xlsx";
            case self::FORMAT_BIFF:
                return "status.xls";
            case self::FORMAT_CSV:
            default:
                return "status.csv";
        }
    }


    /**
     * load the status of the users/teams from the status file
     * @param  $filename
     * @return bool
     */
    public function loadFromFile($filename) {
        $this->error = false;
        $this->updates = [];
        try {
            if (file_exists($filename)) {
                $this->workbook = IOFactory::load($filename);
                if ($this->assignment->getAssignmentType()->isSubmissionAssignedToTeam()) {
                    $this->loadTeamSheet();
                }
                else {
                    $this->loadMemberSheet();
                }
                return true;
            }
            return false;
        }
        catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * Write the status of the users/teams to the status file
     * @param  $a_file
     * @return boolean
     */
    public function writeToFile($a_file) {
        try {
            if ($this->assignment->getAssignmentType()->isSubmissionAssignedToTeam()) {
                $this->writeTeamSheet();
            }
            else {
                $this->writeMemberSheet();
            }
            /** @var  PhpOffice\PhpSpreadsheet\Writer\Xlsx  $writer */
            $writer = IOFactory::createWriter($this->workbook, $this->format);
            $writer->save($a_file);
            return true;
        }
        catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }


    /**
     * Init the data of the exercise members
     * @param array $usr_ids     init only the data of these ids, if given
     */
    protected function initMembers($usr_ids = null) {
        $members = $this->assignment->getMemberListData();

        // get only the filtered members
        if (isset($usr_ids)) {
            foreach ($members as $id => $member) {
                if (in_array($id, $usr_ids)) {
                    $this->members[$id] = $member;
                }
            }
        }
        else {
            $this->members = $members;
        }
    }

    /**
     * Init the data of the exercise teams
     * @param array $usr_ids     init only the data of these ids, if given
     */
    protected function initTeams($usr_ids = null) {
        /** @var ilExAssignmentTeam[] $teams */
        $teams = ilExAssignmentTeam::getInstancesFromMap($this->assignment->getId());

        if (isset($usr_ids)) {
            foreach ($teams as $id => $team) {
                if (count(array_intersect($team->getMembers(), array_keys($this->members))) > 0) {
                    $this->teams[$id] = $team;
                }
            }
        }
        else {
            $this->teams = $teams;
        }
    }

    /**
     * Add the sheet for exercise members
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function writeMemberSheet() {
        $this->addSheet('members');

        // write the title line
        $col = 0;
        foreach ($this->member_titles as $title) {
            $this->setCell(1, $col++, $title);
        }

        // write the member line
        $row = 2;
        foreach ($this->members as $member) {
            $col = 0;
            $this->setCell($row, $col++, 0, DataType::TYPE_NUMERIC);
            $this->setCell($row, $col++, $member['usr_id'], DataType::TYPE_NUMERIC);
            $this->setCell($row, $col++, $member['login'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['lastname'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['firstname'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['status'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['mark'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['notice'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, $member['comment'], DataType::TYPE_STRING);
            $this->setCell($row, $col++, ($member['plag_flag'] == 'none' ? '' : $member['plag_flag']), DataType::TYPE_STRING);
            $this->setCell($row, $col, $member['plag_comment'], DataType::TYPE_STRING);
            $row++;
        }
    }

    /**
     * Load the sheet data for members
     * @throws ilExerciseException
     */
    protected function loadMemberSheet() {
        $sheet = $this->getSheetAsArray();

        // check the titles row (all titles must be present)
        $titles = array_shift($sheet);
        if (count(array_diff($this->member_titles, (array) $titles)) > 0) {
            throw new ilExerciseException($this->lng->txt('exc_status_file_wrong_titles'));
        }

        // load the update data from the file
        $index = array_flip($this->member_titles);
        foreach ($sheet as $rowdata) {
            $data = [];
            $data['update'] = (bool)  $rowdata[$index['update']];
            $data['login'] = (string) $rowdata[$index['login']];
            $data['usr_id'] = (int) $rowdata[$index['usr_id']];
            $data['status'] = (string) $rowdata[$index['status']];
            $data['mark'] = (string) $rowdata[$index['mark']];
            $data['notice'] = (string) $rowdata[$index['notice']];
            $data['comment'] = (string) $rowdata[$index['comment']];
            $data['plag_flag'] = ((string) $rowdata[$index['plagiarism']] ? (string) $rowdata[$index['plagiarism']] : 'none');
            $data['plag_comment'] = (string) $rowdata[$index['plag_comment']];

            if (!$data['update'] || !isset($this->members[$data['usr_id']])) {
                continue;
            }

            if (!in_array($data['status'], $this->valid_states)) {
                throw new ilExerciseException(sprintf($this->lng->txt('exc_status_file_wrong_status'), $data['status']));
            }

            if (!$this->assignment->checkMark($data['mark'])) {
                throw new ilExerciseException(sprintf($this->lng->txt('exc_status_file_wrong_mark'), $data['mark'], $this->assignment->getMaxPoints()));
            }

            if (!in_array($data['plag_flag'], $this->valid_plag_flags)) {
                throw new ilExerciseException(sprintf($this->lng->txt('exc_status_file_wrong_plag_flag'), $data['plag_flag']));
            }

            $this->updates[] = $data;
        }
    }

    /**
     * Add the sheet for teams
     */
    protected function writeTeamSheet() {

    }

    /**
     * Load the sheet data for members
     */
    protected function loadTeamSheet() {

    }

    /**
     * Apply the status updates
     */
    public function applyStatusUpdates() {
        foreach ($this->updates as $data) {
            $user_ids = [];
            if (isset($data['usr_id'])) {
                $user_ids = [$data['usr_id']];
            }
            elseif (isset($data['team_id'])) {
                $user_ids = $this->teams[$data['team_id']]->getMembers();
            }

            foreach ($user_ids as $user_id) {
                $status = new ilExAssignmentMemberStatus($this->assignment->getId(), $user_id);
                $status->setStatus($data['status']);
                $status->setMark($data['mark']);
                $status->setComment($data['comment']);
                $status->setNotice($data['notice']);
                $status->setPlagFlag($data['plag_flag']);
                $status->setPlagComment($data['plag_comment']);
                $status->update();
            }
        }
        $this->updates_applied = true;
    }

    /**
     * Check if an error was detected
     * @return bool
     */
    public function hasError() {
        return !empty($this->error);
    }

    /**
     * Check if updates are read from the file
     * @return bool
     */
    public function hasUpdates() {
        return !empty($this->updates);
    }

    /**
     * Get the info message for updates
     */
    public function getInfo() {
        if ($this->hasError()) {
            return sprintf($this->lng->txt('exc_status_file_error'), $this->getFilename(), $this->error);
        }
        elseif (!$this->hasUpdates()) {
            return sprintf($this->lng->txt('exc_status_file_no_updates'),  $this->getFilename());
        }
        else {
            $logins = [];
            foreach ($this->updates as $data) {
                $logins[] = $data['login'];
            }
            if ($this->updates_applied) {
                return sprintf($this->lng->txt('exc_status_file_updated_users'),  implode(', ', $logins));
            }
            return sprintf($this->lng->txt('exc_status_file_update_users'),  $this->getFilename(), implode(', ', $logins));
        }
    }
}