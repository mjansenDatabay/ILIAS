<?php
// fau: exAssTest - new class ilExSubmissionTestResultGUI.

require_once("./Modules/Exercise/AssignmentTypes/classes/class.ilExAssTypeTestResultAssignment.php");

/**
 * Test result based submissions (copies status and mark from the test)
 *
 * @ilCtrl_Calls ilExSubmissionTestResultGUI:
 * @ingroup ModulesExercise
 */
class ilExSubmissionTestResultGUI extends ilExSubmissionBaseGUI
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

    public function executeCommand()
    {
        $ilCtrl = $this->ctrl;
        
        if (!$this->assignment ||
            $this->assignment->getType() != ilExAssignment::TYPE_TEST_RESULT ||
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

        $assTest =  $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($a_submission->getAssignment()->getId());

        if ($a_submission->canSubmit() && !empty($assTest->getTestRefId())) {
            $button = ilLinkButton::getInstance();
            $button->setPrimary(true);
            $button->setCaption("exc_ass_type_test_open");
            $button->setTarget('_blank');
            $button->setUrl($DIC->ctrl()->getLinkTargetByClass(['ilexsubmissiongui','ilexsubmissiontestresultgui'], 'callTest'));
            $a_info->addProperty($lng->txt("exc_ass_type_test_object"), $button->render());
        }
    }

    /**
     * Add user to the exercise and call the test
     */
    public function callTest()
    {
        global $DIC;

        $assTest =  $assTest = ilExAssTypeTestResultAssignment::findOrGetInstance($this->submission->getAssignment()->getId());

        if ($this->submission->canSubmit() && !empty($assTest->getTestRefId())) {

            $members = new ilExerciseMembers($this->exercise);
            if (!$members->isAssigned($DIC->user()->getId())) {
                $members->assignMember($DIC->user()->getId());
            }
            $this->ctrl->redirectToURL(ilLink::_getLink($assTest->getTestRefId(), 'tst'));
        }

    }

}
