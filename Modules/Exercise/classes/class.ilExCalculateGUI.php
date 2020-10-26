<?php

include_once "./Modules/Exercise/classes/class.ilExCalculate.php";

/**
 * fau: exCalc - new GUI class for result calculations.
 * @author Fred Neumann <fred.neumann@fau.de>
 * @ingroup ModulesExercise
 *
 * @ilCtrl_IsCalledBy ilExCalculateGUI: ilObjExerciseGUI, ilExerciseManagementGUI
 */
class ilExCalculateGUI
{
    /** @var ilObjExercise */
    protected $object;
    
    /** @var ilExCalculate  */
    protected $calc;
    
    /** @var ilTemplate  */
    protected $tpl;

    /** @var ilCtrl  */
    protected $ctrl;


    /** @var ilLanguage  */
    protected $lng;

    /** @var ilObjExercise */
    protected $exercise;

    /** @var bool */
    protected $allow_calculate;

    /**
     * Constructor
     * @param ilObjExercise $a_exercise
     * @param bool $a_allow_calculate
     */
    public function __construct(ilObjExercise $a_exercise, $a_allow_calculate = false)
    {
        global $DIC;

        $this->tpl = $DIC['tpl'];
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();

        $this->exercise = $a_exercise;
        $this->allow_calculate = $a_allow_calculate;
        $this->calc = new ilExCalculate($this->exercise);
    }

    
    /**
     * Main entry
     */
    public function executeCommand()
    {
        global $DIC;

        $next_class = $this->ctrl->getNextClass($this);
        $cmd = $this->ctrl->getCmd("showForm");
  
        switch ($next_class) {
            default:
            {
                switch ($cmd) {
                    case 'showForm':
                    case 'saveSettings':
                        /** @see ilExerciseManagementGUI::addSubTabs() */
                        if ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_MANUAL ||
                            ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC
                                && $DIC->access()->checkAccess('write', '', $this->exercise->getRefId()))) {

                            if ($this->allow_calculate) {
                                $button = ilLinkButton::getInstance();
                                $button->setUrl($this->ctrl->getLinkTarget($this, 'confirmCalculateAll'));
                                $button->setCaption('exc_calculate_overall_results');
                                $DIC->toolbar()->addButtonInstance($button);
                            }
                            $this->$cmd();
                        }
                        break;

                    case 'confirmCalculateAll':
                    case 'calculateAll':
                        if ($this->allow_calculate && (
                            $this->exercise->getPassMode() == ilObjExercise::PASS_MODE_MANUAL ||
                            ($this->exercise->getPassMode() == ilObjExercise::PASS_MODE_CALC))) {
                            $this->$cmd();
                        }
                        break;

                    case 'cancel':
                        $this->$cmd();
                        break;

                    default:
                        $this->ctrl->returnToParent($this);
                }
            }
        }
    }
    
    /**
     * Show the settings form with the saved options
     */
    protected function showForm()
    {
        $form = $this->initForm();
        $this->readFormValues($form);
        $this->tpl->setContent($form->getHTML());
    }
    
    /**
     * Save the settings
     */
    protected function saveSettings()
    {
        $this->processForm();
    }

    /**
     * Cancel the calculation
     */
    protected function cancel()
    {
        $this->ctrl->returnToParent($this);
    }
    
    /**
     * Initialize the settings form
     * @return ilPropertyFormGUI
     */
    protected function initForm()
    {
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this, "submitExportForm"));
        $form->setTitle($this->lng->txt("exc_pass_result_calculation"));
        
        // calculate selection
        $group = new ilRadioGroupInputGUI($this->lng->txt('exc_calc_mark_select'), 'mark_select');
        $group->setInfo($this->lng->txt('exc_calc_mark_select_info'));
        $group->setRequired(true);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_marked'), ilExCalculate::SELECT_MARKED);
        $group->addOption($option);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_mandatory'), ilExCalculate::SELECT_MANDATORY);
        $group->addOption($option);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_select_number'), ilExCalculate::SELECT_NUMBER);
        
        // order
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_mark_select_order'), 'mark_select_order');
        $subitem->setRequired(true);
        $subitem->setOptions(array(
                ilExCalculate::ORDER_HIGHEST => $this->lng->txt('exc_calc_mark_select_highest'),
                ilExCalculate::ORDER_LOWEST => $this->lng->txt('exc_calc_mark_select_lowest')
            ));
        $option->addSubItem($subitem);
            
        // number
        $subitem = new ilNumberInputGUI($this->lng->txt('exc_calc_mark_select_count'), 'mark_select_count');
        $subitem->setInfo($this->lng->txt('exc_calc_mark_select_count_info'));
        $subitem->setSize(2);
        $subitem->setMinValue(1);
        $subitem->setDecimals(0);
        $subitem->setRequired(true);
        $option->addSubItem($subitem);
                        
        $group->addOption($option);
        $form->addItem($group);

        // calculate function
        $group = new ilRadioGroupInputGUI($this->lng->txt('exc_calc_mark_function'), 'mark_function');
        $group->setRequired(true);

        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_function_sum'), ilExCalculate::FUNCTION_SUM);
        $option->setInfo($this->lng->txt('exc_calc_mark_function_sum_info'));
        $group->addOption($option);
        
        $option = new ilRadioOption($this->lng->txt('exc_calc_mark_function_average'), ilExCalculate::FUNCTION_AVERAGE);
        $option->setInfo($this->lng->txt('exc_calc_mark_function_average_info'));
        $group->addOption($option);
        
        $form->addItem($group);
        
        // status calculation
        $item = new ilCheckboxInputGUI($this->lng->txt('exc_calc_status'), 'status_calculate');
        $item->setInfo($this->lng->txt('exc_calc_status_info'));

        // order
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_status_compare'), 'status_compare');
        $subitem->setRequired(true);
        $subitem->setOptions(array(
                ilExCalculate::COMPARE_LOWER => $this->lng->txt('exc_calc_status_compare_lower'),
                ilExCalculate::COMPARE_HIGHER => $this->lng->txt('exc_calc_status_compare_higher'),
                ilExCalculate::COMPARE_LOWER_EQUAL => $this->lng->txt('exc_calc_status_compare_lower_equal'),
                ilExCalculate::COMPARE_HIGHER_EQUAL => $this->lng->txt('exc_calc_status_compare_higher_equal')
            ));
        $item->addSubItem($subitem);
        
        // number
        $subitem = new ilNumberInputGUI($this->lng->txt('exc_calc_status_compare_value'), 'status_compare_value');
        $subitem->setInfo($this->lng->txt('exc_calc_status_compare_info'));
        $subitem->setSize(5);
        $subitem->setRequired(true);
        $item->addSubItem($subitem);
            
        // default value
        $subitem = new ilSelectInputGUI($this->lng->txt('exc_calc_status_default'), 'status_default');
        $subitem->setRequired(true);
        $subitem->setInfo($this->lng->txt('exc_calc_status_default_info'));
        $subitem->setOptions(array(
                ilExCalculate::STATUS_NOTGRADED => $this->lng->txt('exc_notgraded'),
                ilExCalculate::STATUS_PASSED => $this->lng->txt('exc_passed'),
                ilExCalculate::STATUS_FAILED => $this->lng->txt('exc_failed')
            ));
        $item->addSubItem($subitem);
        
        $form->addItem($item);

        $form->addCommandButton("saveSettings", $this->lng->txt("save"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }
    
    
    /**
     * Process the form
     */
    protected function processForm()
    {
        $form = $this->initForm();
        $form->setDisableStandardMessage(true);

        // standard checks
        $ok = $form->checkInput();
    
        // check for mandatory assignments
        if ($form->getInput('mark_select') == ilExCalculate::SELECT_NUMBER and $form->getInput('mark_select_count') != '') {
            include_once "./Modules/Exercise/classes/class.ilExAssignment.php";
            $mandatory = ilExAssignment::countMandatory($this->exercise->getId());
            if ($form->getInput('mark_select_count') < $mandatory) {
                $ok = false;
                /** @var ilNumberInputGUI $item */
                $item = $form->getItemByPostVar('mark_select_count');
                $item->setAlert(sprintf($this->lng->txt('exc_calc_mark_select_count_message'), $mandatory));
            }
        }
        
        if ($ok) {
            $this->saveFormValues($form);
                ilUtil::sendInfo($this->lng->txt("settings_saved"), true);
                $this->ctrl->redirect($this, 'showForm');

        } else {
            ilUtil::sendFailure($this->lng->txt("form_input_not_valid"));
            $form->setValuesByPost();
            $this->tpl->setContent($form->getHTML());
        }
    }
        
    
    /**
     * Read the form values
     * @param ilPropertyFormGUI	$form
     */
    protected function readFormValues(ilPropertyFormGUI $form)
    {
        $values = array();
        $values['mark_function'] = $this->calc->mark_function;
        $values['mark_select'] = $this->calc->mark_select;
        $values['mark_select_order'] = $this->calc->mark_select_order;
        $values['mark_select_count'] = $this->calc->mark_select_count;
        $values['status_calculate'] = $this->calc->status_calculate;
        $values['status_compare'] = $this->calc->status_compare;
        $values['status_compare_value'] = $this->calc->status_compare_value;
        $values['status_default'] = $this->calc->status_default;
        $form->setValuesByArray($values);
    }
    
    
    /**
     * Save the form values
     * @param ilPropertyFormGUI	$form
     */
    protected function saveFormValues(ilPropertyFormGUI $form)
    {
        $this->calc->mark_function = (string) $form->getInput('mark_function');
        $this->calc->mark_select =  (string) $form->getInput('mark_select');
        $this->calc->mark_select_order =  (string) $form->getInput('mark_select_order');
        $this->calc->mark_select_count = (int) $form->getInput('mark_select_count');
        $this->calc->status_calculate = (bool) $form->getInput('status_calculate');
        $this->calc->status_compare =  (string) $form->getInput('status_compare');
        $this->calc->status_compare_value = (float) $form->getInput('status_compare_value');
        $this->calc->status_default =  (string) $form->getInput('status_default');
        $this->calc->writeOptions();
    }

    /**
     * Confirm the calculation
     */
    protected function confirmCalculateAll() {

        $gui = new ilConfirmationGUI();
        $gui->setFormAction($this->ctrl->getFormAction($this));
        $gui->setHeaderText($this->lng->txt('exc_calc_confirm_all'));
        $gui->setCancel($this->lng->txt('cancel'), 'cancel');
        $gui->setConfirm($this->lng->txt('exc_calc_calculate'), 'calculate');
        $this->tpl->setContent($gui->getHTML());
    }

    /**
     * Calculate the results for all participants
     */
    protected function calculateAll() {

        $this->calc->calculateResults();
        ilUtil::sendSuccess($this->lng->txt("exc_calc_all_calculated"), true);
        $this->ctrl->returnToParent($this);
    }
}
