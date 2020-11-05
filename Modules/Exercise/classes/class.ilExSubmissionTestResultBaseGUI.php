<?php
// fau: exAssTest - new class ilExSubmissionTestResultBaseGUI.

require_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeTestResultAssignment.php");

/**
 * Test result based submissions base class  (copies status and mark from the test)
 * @ingroup ModulesExercise
 */
abstract class ilExSubmissionTestResultBaseGUI extends ilExSubmissionBaseGUI
{
    /**
     * @var ilObjUser
     */
    protected $user;

    /** @var ilExAssTypeTestResultAssignment */
    protected $assTestResult;

    /**
     * Constructor
     */
    public function __construct(ilObjExercise $a_exercise, ilExSubmission $a_submission)
    {
        global $DIC;

        parent::__construct($a_exercise, $a_submission);
        $this->user = $DIC->user();
    }

    /**
     * Execute controller commands
     */
    public function executeCommand()
    {
        $ilCtrl = $this->ctrl;
        
        if (!$this->assignment ||
            !in_array($this->submission->getSubmissionType(), [ilExSubmission::TYPE_TEST_RESULT, ilExSubmission::TYPE_TEST_RESULT_TEAM]) ||
            !$this->submission->canView()) {
            return;
        }

        $class = $ilCtrl->getNextClass($this);
        $cmd = $ilCtrl->getCmd("callTest");
        
        switch ($class) {
            default:
                $this->$cmd();
                break;
        }
    }

    /**
     * Add start button to the assignment overview
     * @param ilInfoScreenGUI $a_info
     * @param ilExSubmission  $a_submission
     */
    public static function getOverviewContent(ilInfoScreenGUI $a_info, ilExSubmission $a_submission)
    {
        global $DIC;
        $lng = $DIC->language();

        // no team yet
        if ($a_submission->getSubmissionType() == ilExSubmission::TYPE_TEST_RESULT_TEAM && $a_submission->hasNoTeamYet()) {
            $a_info->addProperty($lng->txt("exc_ass_type_test_open_label"), $lng->txt("exc_ass_type_test_open_no_team"));
            return;
        }

        $assTest =  $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($a_submission->getAssignment()->getId());

        if ($a_submission->canSubmit() && !empty($assTest->getTestRefId())) {
            $button = ilLinkButton::getInstance();
            $button->setPrimary(true);
            $button->setCaption("exc_ass_type_test_open");
            $button->setTarget('_blank');
            $button->setUrl($DIC->ctrl()->getLinkTargetByClass(['ilexsubmissiongui',strtolower(get_called_class())], 'callTest'));
            $a_info->addProperty($lng->txt("exc_ass_type_test_open_label"), '<p>' .$lng->txt("exc_ass_type_test_open_info"). '</p>'. $button->render());
        }
    }

    /**
     * Add user to the exercise and call the test
     */
    public function callTest()
    {
        global $DIC;

        // no team yet
        if ($this->submission->getSubmissionType() == ilExSubmission::TYPE_TEST_RESULT_TEAM && $this->submission->hasNoTeamYet()) {
            return;
        }

        $assTest = $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($this->submission->getAssignment()->getId());

        if ($this->submission->canSubmit() && !empty($assTest->getTestRefId())) {
            $members = new ilExerciseMembers($this->exercise);
            if (!$members->isAssigned($DIC->user()->getId())) {
                $members->assignMember($DIC->user()->getId());
            }
            $this->ctrl->redirectToURL(ilLink::_getLink($assTest->getTestRefId(), 'tst'));
        }
    }
}
