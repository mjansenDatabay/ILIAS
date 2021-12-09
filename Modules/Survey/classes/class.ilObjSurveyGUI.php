<?php

/* Copyright (c) 1998-2021 ILIAS open source, GPLv3, see LICENSE */

use \ILIAS\Survey\Participants;

/**
 * Class ilObjSurveyGUI
 *
 * @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
 *
 * @ilCtrl_Calls ilObjSurveyGUI: ilSurveyEvaluationGUI, ilSurveyExecutionGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilObjectMetaDataGUI, ilPermissionGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilInfoScreenGUI, ilObjectCopyGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilSurveySkillDeterminationGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilCommonActionDispatcherGUI, ilSurveySkillGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilSurveyEditorGUI, ilSurveyConstraintsGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilSurveyParticipantsGUI, ilLearningProgressGUI
 * @ilCtrl_Calls ilObjSurveyGUI: ilExportGUI, ilLTIProviderObjectSettingGUI
 */
class ilObjSurveyGUI extends ilObjectGUI implements ilCtrlBaseClassInterface
{
    /**
     * @var ilNavigationHistory
     */
    protected $nav_history;

    /**
     * @var ilTabsGUI
     */
    protected $tabs;

    /**
     * @var ilHelpGUI
     */
    protected $help;

    /**
     * @var ilRbacSystem
     */
    protected $rbacsystem;

    /**
     * @var ilLogger
     */
    protected $log;

    /**
     * @var Participants\InvitationsManager
     */
    protected $invitation_manager;

    /**
     * @var \ILIAS\Survey\InternalService
     */
    protected $survey_service;

    /**
     * @var \ILIAS\Survey\Mode\FeatureConfig|null
     */
    protected $feature_config = null;

    /**
     * @var \ILIAS\Survey\Execution\SessionManager
     */
    protected $session_manager;

    protected $access_manager = null;

    /**
     * @var \ILIAS\Survey\Execution\RunManager
     */
    protected $run_manager;

    /**
     * @var \ILIAS\Survey\Participants\StatusManager
     */
    protected $status_manager;

    /**
     * @var \ilObjSurvey
     */
    protected $survey = null;

    public function __construct()
    {
        /** @var ILIAS\DI\Container $DIC */
        global $DIC;


        $this->user = $DIC->user();
        $this->survey_service = $DIC->survey()->internal();

        $this->lng = $DIC->language();
        $this->nav_history = $DIC["ilNavigationHistory"];
        $this->tabs = $DIC->tabs();
        $this->help = $DIC["ilHelp"];
        $this->rbacsystem = $DIC->rbac()->system();
        $this->tree = $DIC->repositoryTree();
        $this->tpl = $DIC["tpl"];
        $this->toolbar = $DIC->toolbar();
        $this->access = $DIC->access();
        $this->locator = $DIC["ilLocator"];
        $lng = $DIC->language();
        $ilCtrl = $DIC->ctrl();

        $this->type = "svy";
        $lng->loadLanguageModule("survey");
        $lng->loadLanguageModule("svy");
        $this->ctrl = $ilCtrl;
        $this->ctrl->saveParameter($this, "ref_id");

        $this->log = ilLoggerFactory::getLogger("svy");

        $this->invitation_manager = $this->survey_service->domain()->participants()->invitations();


        parent::__construct("", (int) $_GET["ref_id"], true, false);

        if ($this->object && $this->object->getType() == "svy") {
            /** @var $survey \ilObjSurvey */
            $survey = $this->object;
            $this->survey = $survey;

            $this->status_manager = $this->survey_service
                ->domain()
                ->participants()
                ->status($survey, $this->user->getId());
            $this->feature_config = $this->survey_service->domain()
                ->modeFeatureConfig($this->object->getMode());
            $this->session_manager = $this->survey_service->domain()
                ->execution()->session($survey, $this->user->getId());
            $this->run_manager = $this->survey_service->domain()
                ->execution()->run($survey, $this->user->getId());
            $this->access_manager = $this->survey_service
                ->domain()
                ->access((int) $_GET["ref_id"], $this->user->getId());
        }
    }
    
    public function executeCommand()
    {
        $ilTabs = $this->tabs;
        $access_manager = $this->access_manager;
        $survey = $this->survey;

        if ($survey && !$access_manager->canAccessInfoScreen()) {
            if (!$access_manager->canAccessInfoScreen()) {
                $this->noPermission();
            }
            $this->addToNavigationHistory();
        }
        
        $cmd = $this->ctrl->getCmd("properties");
        
        // workaround for bug #6288, needs better solution
        if ($cmd == "saveTags") {
            $this->ctrl->setCmdClass("ilinfoscreengui");
        }
        
        // deep link from repository - "redirect" to page view
        if (!$this->ctrl->getCmdClass() && $cmd == "questionsrepo") {
            $_REQUEST["pgov"] = 1;
            $this->ctrl->setCmd("questions");
            $this->ctrl->setCmdClass("ilsurveyeditorgui");
        }
        
        $next_class = $this->ctrl->getNextClass($this);
        $this->ctrl->setReturn($this, "properties");
        $this->tpl->addCss(ilUtil::getStyleSheetLocation("output", "survey.css", "Modules/Survey"), "screen");
        $this->prepareOutput();

        $this->log->debug("next_class= $next_class");
        switch ($next_class) {
            case 'illtiproviderobjectsettinggui':
                $this->addSubTabs('settings');
                $ilTabs->activateTab("settings");
                $ilTabs->activateSubTab('lti_provider');
                $lti_gui = new ilLTIProviderObjectSettingGUI($this->object->getRefId());
                $lti_gui->setCustomRolesForSelection($GLOBALS['DIC']->rbac()->review()->getLocalRoles($this->object->getRefId()));
                $lti_gui->offerLTIRolesForSelection(false);
                $this->ctrl->forwardCommand($lti_gui);
                break;
            
            
            case "ilinfoscreengui":
                if (!in_array(
                    $this->ctrl->getCmdClass(),
                    array('ilpublicuserprofilegui', 'ilobjportfoliogui')
                )) {
                    $this->addHeaderAction();
                    $this->infoScreen(); // forwards command
                } else {
                    // #16891
                    $ilTabs->clearTargets();
                    $info = new ilInfoScreenGUI($this);
                    $this->ctrl->forwardCommand($info);
                }
                break;
            
            case 'ilobjectmetadatagui':
                $this->checkPermission("write");
                $ilTabs->activateTab("meta_data");
                $this->addHeaderAction();
                $md_gui = new ilObjectMetaDataGUI($this->object);
                $this->ctrl->forwardCommand($md_gui);
                break;
            
            case "ilsurveyevaluationgui":

                if ($this->checkRbacOrPositionPermission('read_results', 'access_results') ||
                ilObjSurveyAccess::_hasEvaluationAccess($this->object->getId(), $this->user->getId())) {
                    $ilTabs->activateTab("svy_results");
                    $this->addHeaderAction();
                    $eval_gui = new ilSurveyEvaluationGUI($this->object);
                    $this->ctrl->forwardCommand($eval_gui);
                }
                break;

            case "ilsurveyexecutiongui":
                $ilTabs->clearTargets();
                $exec_gui = new ilSurveyExecutionGUI($this->object);
                $this->ctrl->forwardCommand($exec_gui);
                break;
                
            case 'ilpermissiongui':
                $ilTabs->activateTab("perm_settings");
                $this->addHeaderAction();
                $perm_gui = new ilPermissionGUI($this);
                $this->ctrl->forwardCommand($perm_gui);
                break;
                
            case 'ilobjectcopygui':
                $cp = new ilObjectCopyGUI($this);
                $cp->setType('svy');
                $this->ctrl->forwardCommand($cp);
                break;

            case "ilcommonactiondispatchergui":
                $gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
                $this->ctrl->forwardCommand($gui);
                break;
                
            // 360, skill service
            case 'ilsurveyskillgui':
                $ilTabs->activateTab("survey_competences");
                $gui = new ilSurveySkillGUI($this->object);
                $this->ctrl->forwardCommand($gui);
                break;

            case 'ilsurveyskilldeterminationgui':
                $ilTabs->activateTab("maintenance");
                $gui = new ilSurveySkillDeterminationGUI($this->object);
                $this->ctrl->forwardCommand($gui);
                break;
            
            case 'ilsurveyeditorgui':
                $this->checkPermission("write");
                $ilTabs->activateTab("survey_questions");
                $gui = new ilSurveyEditorGUI($this);
                $this->ctrl->forwardCommand($gui);
                break;
            
            case 'ilsurveyconstraintsgui':
                $this->checkPermission("write");
                $ilTabs->activateTab("constraints");
                $gui = new ilSurveyConstraintsGUI($this);
                $this->ctrl->forwardCommand($gui);
                break;
            
            case 'ilsurveyparticipantsgui':
                if (!$this->feature_config->usesAppraisees()) {
                    $ilTabs->activateTab("maintenance");
                } else {
                    $ilTabs->activateTab("survey_360_appraisees");
                }
                $gui = new ilSurveyParticipantsGUI($this, $this->checkRbacOrPositionPermission('read_results', 'access_results'));
                $this->ctrl->forwardCommand($gui);
                break;
                
            case "illearningprogressgui":
                $ilTabs->activateTab("learning_progress");
                $new_gui = new ilLearningProgressGUI(
                    ilLearningProgressBaseGUI::LP_CONTEXT_REPOSITORY,
                    $this->object->getRefId()
                );
                $this->ctrl->forwardCommand($new_gui);
                break;
            
            case 'ilexportgui':
                $ilTabs->activateTab("export");
                $exp_gui = new ilExportGUI($this);
                $exp_gui->addFormat("xml");
                $this->ctrl->forwardCommand($exp_gui);
                break;

            default:
                $this->addHeaderAction();
                $cmd .= "Object";

                $this->log->debug("Default cmd= $cmd");

                $this->$cmd();
                break;
        }

        if (strtolower($_GET["baseClass"]) != "iladministrationgui" &&
            $this->getCreationMode() != true) {
            $this->tpl->printToStdout();

            //cherry pick conflict with d97cf1c77b
            //$this->tpl->show();
            //$this->log->debug("after tpl show");
        }
    }

    protected function noPermission() : void
    {
        throw new ilObjectException($this->lng->txt("permission_denied"));
    }

    protected function addToNavigationHistory() : void
    {
        $external_rater = $this->status_manager->isExternalRater();
        // add entry to navigation history
        if (!$external_rater && !$this->getCreationMode() &&
            $this->checkPermissionBool("read")) {
            $this->ctrl->setParameterByClass("ilobjsurveygui", "ref_id", $this->ref_id);
            $link = $this->ctrl->getLinkTargetByClass("ilobjsurveygui", "");
            $this->nav_history->addItem($this->ref_id, $link, "svy");
        }
    }

    /**
    * Redirects the evaluation object call to the ilSurveyEvaluationGUI class
    *
    * Coming from ListGUI...
    *
    * @access	private
    */
    public function evaluationObject()
    {
        $eval_gui = new ilSurveyEvaluationGUI($this->object);
        $this->ctrl->setCmdClass(get_class($eval_gui));
        $this->ctrl->redirect($eval_gui, "evaluation");
    }

    protected function addDidacticTemplateOptions(array &$a_options)
    {
        $templates = ilSettingsTemplate::getAllSettingsTemplates("svy");
        if ($templates) {
            foreach ($templates as $item) {
                $a_options["svytpl_" . $item["id"]] = array($item["title"],
                    nl2br(trim($item["description"])));
            }
        }
        
        // JF, 2013-06-10
        $a_options["svy360_1"] = array($this->lng->txt("survey_360_mode"),
            $this->lng->txt("survey_360_mode_info"));

        //Self evaluation only
        $a_options["svyselfeval_1"] = array($this->lng->txt("svy_self_ev_mode"),
            $this->lng->txt("svy_self_ev_info"));

        // individual feedback
        $a_options["individfeedb_1"] = array($this->lng->txt("svy_ind_feedb_mode"),
            $this->lng->txt("svy_ind_feedb_info"));
    }

    /**
    * save object
    * @access	public
    */
    public function afterSave(ilObject $a_new_object)
    {
        // #16446
        $a_new_object->loadFromDb();
        
        $tpl = $this->getDidacticTemplateVar("svytpl");
        if ($tpl) {
            $a_new_object->applySettingsTemplate($tpl);
        } else {
            //set the mode depending on didactic template
            if ($this->getDidacticTemplateVar("svy360")) {
                $a_new_object->setMode(ilObjSurvey::MODE_360);
            } elseif ($this->getDidacticTemplateVar("svyselfeval")) {
                $a_new_object->setMode(ilObjSurvey::MODE_SELF_EVAL);
            } elseif ($this->getDidacticTemplateVar("individfeedb")) {
                $a_new_object->setMode(ilObjSurvey::MODE_IND_FEEDB);
            }
        }

        $svy_mode = $a_new_object->getMode();
        if ($svy_mode == ilObjSurvey::MODE_360) {
            // this should rather be ilObjSurvey::ANONYMIZE_ON - see ilObjSurvey::getUserDataFromActiveId()
            $a_new_object->setAnonymize(ilObjSurvey::ANONYMIZE_CODE_ALL);
            $a_new_object->setEvaluationAccess(ilObjSurvey::EVALUATION_ACCESS_PARTICIPANTS);
        } elseif ($svy_mode == ilObjSurvey::MODE_SELF_EVAL) {
            $a_new_object->setEvaluationAccess(ilObjSurvey::EVALUATION_ACCESS_PARTICIPANTS);
        }
        $a_new_object->saveToDB();

        // always send a message
        ilUtil::sendSuccess($this->lng->txt("object_added"), true);
        ilUtil::redirect("ilias.php?baseClass=ilObjSurveyGUI&ref_id=" .
            $a_new_object->getRefId() . "&cmd=properties");
    }
    
    /**
    * adds tabs to tab gui object
    *
    * @param	object		$tabs_gui		ilTabsGUI object
    */
    public function getTabs()
    {
        $ilUser = $this->user;
        $ilHelp = $this->help;
        $feature_config = $this->feature_config;
        
        if ($this->object instanceof ilObjSurveyQuestionPool) {
            return true;
        }
        
        $ilHelp->setScreenIdComponent("svy");

        $hidden_tabs = array();
        $template = $this->object->getTemplate();
        if ($template) {
            $template = new ilSettingsTemplate($template);
            $hidden_tabs = $template->getHiddenTabs();
        }
        
        if ($this->checkPermissionBool("write")) {
            $this->tabs_gui->addTab(
                "survey_questions",
                $this->lng->txt("survey_questions"),
                $this->ctrl->getLinkTargetByClass(array("ilsurveyeditorgui", "ilsurveypagegui"), "renderPage")
            );
        }
        
        if ($this->checkPermissionBool("read")) {
            $this->tabs_gui->addTab(
                "info_short",
                $this->lng->txt("info_short"),
                $this->ctrl->getLinkTarget($this, 'infoScreen')
            );
        }
                            
        // properties
        if ($this->checkPermissionBool("write")) {
            $this->tabs_gui->addTab(
                "settings",
                $this->lng->txt("settings"),
                $this->ctrl->getLinkTarget($this, 'properties')
            );
        } elseif ($this->checkPermissionBool("read")) {
            if ($this->feature_config->usesAppraisees() &&
                $this->object->get360SelfRaters() &&
                $this->object->isAppraisee($ilUser->getId()) &&
                !$this->object->isAppraiseeClosed($ilUser->getId())) {
                $this->tabs_gui->addTab(
                    "survey_360_edit_raters",
                    $this->lng->txt("survey_360_edit_raters"),
                    $this->ctrl->getLinkTargetByClass('ilsurveyparticipantsgui', 'editRaters')
                );
                
                // :TODO: mail to raters
            }
        }

        // questions
        if ($this->checkPermissionBool("write") &&
            !in_array("constraints", $hidden_tabs) &&
            $this->object->getMode() == ilObjSurvey::MODE_STANDARD) {
            // constraints (tab called routing)
            $this->tabs_gui->addTab(
                "constraints",
                $this->lng->txt("constraints"),
                $this->ctrl->getLinkTargetByClass("ilsurveyconstraintsgui", "constraints")
            );
        }

        if ($this->checkPermissionBool("write")) {
            if ($feature_config && $feature_config->supportsCompetences()) {
                $skmg_set = new ilSkillManagementSettings();
                if ($this->object->getSkillService() && $skmg_set->isActivated()) {
                    $this->tabs_gui->addTab(
                        "survey_competences",
                        $this->lng->txt("survey_competences"),
                        $this->ctrl->getLinkTargetByClass("ilsurveyskillgui", "listQuestionAssignment")
                    );
                }
            }

            if ($feature_config && $feature_config->usesAppraisees()) {
                $this->tabs_gui->addTab(
                    "survey_360_appraisees",
                    $this->lng->txt("survey_360_appraisees"),
                    $this->ctrl->getLinkTargetByClass('ilsurveyparticipantsgui', 'listAppraisees')
                );
            } else {
                $this->tabs_gui->addTab(
                    "maintenance",
                    $this->lng->txt("maintenance"),
                    $this->ctrl->getLinkTargetByClass('ilsurveyparticipantsgui', 'maintenance')
                );
            }
        }

        if (
            $this->checkRbacOrPositionPermission('read_results', 'access_results') ||
            ilObjSurveyAccess::_hasEvaluationAccess($this->object->getId(), $ilUser->getId())) {
            // evaluation
            $this->tabs_gui->addTab(
                "svy_results",
                $this->lng->txt("svy_results"),
                $this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation")
            );
        }
        
        // learning progress
        if (ilLearningProgressAccess::checkAccess($this->object->getRefId())) {
            $this->tabs_gui->addTarget(
                "learning_progress",
                $this->ctrl->getLinkTargetByClass(array("ilobjsurveygui", "illearningprogressgui"), ""),
                "",
                array("illplistofobjectsgui", "illplistofsettingsgui", "illearningprogressgui", "illplistofprogressgui")
            );
        }

        if ($this->checkPermissionBool("write")) {
            if (!in_array("meta_data", $hidden_tabs)) {
                // meta data
                $mdgui = new ilObjectMetaDataGUI($this->object);
                $mdtab = $mdgui->getTab();
                if ($mdtab) {
                    $this->tabs_gui->addTab(
                        "meta_data",
                        $this->lng->txt("meta_data"),
                        $mdtab
                    );
                }
            }

            if (!in_array("export", $hidden_tabs)) {
                // export
                $this->tabs_gui->addTab(
                    "export",
                    $this->lng->txt("export"),
                    $this->ctrl->getLinkTargetByClass("ilexportgui", "")
                );
            }
        }

        if ($this->checkPermissionBool("edit_permission")) {
            // permissions
            $this->tabs_gui->addTab(
                "perm_settings",
                $this->lng->txt("perm_settings"),
                $this->ctrl->getLinkTargetByClass(array(get_class($this),'ilpermissiongui'), "perm")
            );
        }
    }
    
    
    //
    // SETTINGS
    //
            
    /**
    * Save the survey properties
    *
    * Save the survey properties
    *
    * @access private
    */
    public function savePropertiesObject()
    {
        $rbacsystem = $this->rbacsystem;
        $obj_service = $this->object_service;
        $settings_ui = $this->survey_service->ui()->surveySettings($this->object);

        $form = $settings_ui->form("ilObjSurveyGUI");
        if ($settings_ui->checkForm($form)) {
            $settings_ui->saveForm($form);

            if (strcmp($_SESSION["info"], "") != 0) {
                ilUtil::sendSuccess($_SESSION["info"] . "<br />" . $this->lng->txt("settings_saved"), true);
            } else {
                ilUtil::sendSuccess($this->lng->txt("settings_saved"), true);
            }
            $this->ctrl->redirect($this, "properties");
        }

        ilUtil::sendFailure($this->lng->txt("form_input_not_valid"));
        $form->setValuesByPost();
        $this->propertiesObject($form);
    }
    
    /**
     * Init survey settings form
     *
     * @return ilPropertyFormGUI
     */
    public function initPropertiesForm()
    {
        $form = $this->survey_service
            ->ui()->surveySettings($this->object)->form("ilObjSurveyGUI");
        return $form;
    }
    
    /**
     * Add subtabs for tabs
     * @param type $a_section
     */
    public function addSubTabs($a_section)
    {
        if ($a_section == 'settings') {
            $this->tabs_gui->addSubTabTarget(
                "settings",
                $this->ctrl->getLinkTarget($this, 'properties')
            );
            
            $lti_settings = new ilLTIProviderObjectSettingGUI($this->object->getRefId());
            if ($lti_settings->hasSettingsAccess()) {
                $this->tabs_gui->addSubTabTarget(
                    'lti_provider',
                    $this->ctrl->getLinkTargetByClass(ilLTIProviderObjectSettingGUI::class)
                );
            }
        }
    }


    /**
    * Display and fill the properties form of the test
    *
    * @access	public
    */
    public function propertiesObject(ilPropertyFormGUI $a_form = null)
    {
        $ilTabs = $this->tabs;
        $ilHelp = $this->help;
        
        $this->checkPermission("write");
        
        $this->addSubTabs('settings');
        $ilTabs->activateTab("settings");
        $ilTabs->activateSubTab('settings');
        
        if ($this->object->get360Mode()) {
            $ilHelp->setScreenId("settings_360");
        }
        
        if (!$a_form) {
            $a_form = $this->initPropertiesForm();
        }
        
        // using template?
        $message = "";
        if ($this->object->getTemplate()) {
            $link = $this->ctrl->getLinkTarget($this, "confirmResetTemplate");
            $link = "<a href=\"" . $link . "\">" . $this->lng->txt("survey_using_template_link") . "</a>";
            $message = "<div style=\"margin-top:10px\">" .
                ilUtil::getSystemMessageHTML(sprintf(
                    $this->lng->txt("survey_using_template"),
                    ilSettingsTemplate::lookupTitle($this->object->getTemplate()),
                    $link
                ), "info") . // #10651
                "</div>";
        }
    
        $this->tpl->setContent($a_form->getHTML() . $message);
    }
            
    public function doAutoCompleteObject()
    {
        $fields = array('login','firstname','lastname','email');
                
        $auto = new ilUserAutoComplete();
        $auto->setSearchFields($fields);
        $auto->setResultField('login');
        $auto->enableFieldSearchableCheck(true);
        $auto->setMoreLinkAvailable(true);

        if (($_REQUEST['fetchall'])) {
            $auto->setLimit(ilUserAutoComplete::MAX_ENTRIES);
        }

        echo $auto->getList(ilUtil::stripSlashes($_REQUEST['term']));
        exit();
    }
    
    /**
     * Enable all settings - Confirmation
     */
    public function confirmResetTemplateObject()
    {
        ilUtil::sendQuestion($this->lng->txt("survey_confirm_template_reset"));
        $this->tpl->addBlockFile("ADM_CONTENT", "adm_content", "tpl.il_svy_svy_confirm_resettemplate.html", "Modules/Survey");
        $this->tpl->setCurrentBlock("adm_content");
        $this->tpl->setVariable("BTN_CONFIRM_REMOVE", $this->lng->txt("confirm"));
        $this->tpl->setVariable("BTN_CANCEL_REMOVE", $this->lng->txt("cancel"));
        $this->tpl->setVariable("FORM_ACTION", $this->ctrl->getFormAction($this, "resetTemplateObject"));
        $this->tpl->parseCurrentBlock();
    }

    /**
     * Enable all settings - remove template
     */
    public function resetTemplateObject()
    {
        $this->object->setTemplate(null);
        $this->object->saveToDB();

        ilUtil::sendSuccess($this->lng->txt("survey_template_reset"), true);
        $this->ctrl->redirect($this, "properties");
    }

    
    
    //
    // IMPORT/EXPORT
    //
    
    protected function initImportForm($a_new_type)
    {
        $form = new ilPropertyFormGUI();
        $form->setTarget("_top");
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->lng->txt("import_svy"));

        $fi = new ilFileInputGUI($this->lng->txt("import_file"), "importfile");
        $fi->setSuffixes(array("zip"));
        $fi->setRequired(true);
        $form->addItem($fi);

        $svy = new ilObjSurvey();
        $questionspools = $svy->getAvailableQuestionpools(true, true, true);

        $pools = new ilSelectInputGUI($this->lng->txt("select_questionpool_short"), "spl");
        $pools->setOptions(array("" => $this->lng->txt("dont_use_questionpool")) + $questionspools);
        $pools->setRequired(false);
        $form->addItem($pools);

        $form->addCommandButton("importSurvey", $this->lng->txt("import"));
        $form->addCommandButton("cancel", $this->lng->txt("cancel"));

        return $form;
    }
    
    /**
    * form for new survey object import
    */
    public function importSurveyObject()
    {
        $tpl = $this->tpl;

        $parent_id = $_GET["ref_id"];
        $new_type = $_REQUEST["new_type"];

        // create permission is already checked in createObject. This check here is done to prevent hacking attempts
        $this->checkPermission("create", "", $new_type);

        $this->lng->loadLanguageModule($new_type);
        $this->ctrl->setParameter($this, "new_type", $new_type);

        $form = $this->initImportForm($new_type);
        if ($form->checkInput()) {
            $newObj = new ilObjSurvey();
            $newObj->setType($new_type);
            $newObj->setTitle("dummy");
            $newObj->create(true);
            $this->putObjectInTree($newObj);

            // copy uploaded file to import directory

            $this->log->debug("form->getInput(spl) = " . $form->getInput("spl"));

            $error = $newObj->importObject($_FILES["importfile"], $form->getInput("spl"));
            if (strlen($error)) {
                $newObj->delete();
                ilUtil::sendFailure($error);
                return;
            }

            ilUtil::sendSuccess($this->lng->txt("object_imported"), true);
            ilUtil::redirect("ilias.php?ref_id=" . $newObj->getRefId() .
                "&baseClass=ilObjSurveyGUI");

            // using template?
            $templates = ilSettingsTemplate::getAllSettingsTemplates("svy");
            if ($templates) {
                $tpl = $this->tpl;
                $tpl->addJavaScript("./Modules/Scorm2004/scripts/questions/jquery.js");
                // $tpl->addJavaScript("./Modules/Scorm2004/scripts/questions/jquery-ui-min.js");

                $this->tpl->setCurrentBlock("template_option");
                $this->tpl->setVariable("VAL_TEMPLATE_OPTION", "");
                $this->tpl->setVariable("TXT_TEMPLATE_OPTION", $this->lng->txt("none"));
                $this->tpl->parseCurrentBlock();

                foreach ($templates as $item) {
                    $this->tpl->setCurrentBlock("template_option");
                    $this->tpl->setVariable("VAL_TEMPLATE_OPTION", $item["id"]);
                    $this->tpl->setVariable("TXT_TEMPLATE_OPTION", $item["title"]);
                    $this->tpl->parseCurrentBlock();

                    $desc = str_replace("\n", "", nl2br($item["description"]));
                    $desc = str_replace("\r", "", $desc);

                    $this->tpl->setCurrentBlock("js_data");
                    $this->tpl->setVariable("JS_DATA_ID", $item["id"]);
                    $this->tpl->setVariable("JS_DATA_TEXT", $desc);
                    $this->tpl->parseCurrentBlock();
                }

                $this->tpl->setCurrentBlock("templates");
                $this->tpl->setVariable("TXT_TEMPLATE", $this->lng->txt("svy_settings_template"));
                $this->tpl->parseCurrentBlock();
            }
        }
        
        // display form to correct errors
        $form->setValuesByPost();
        $tpl->setContent($form->getHtml());
    }
    
    
    //
    // INFOSCREEN
    //

    /**
    * this one is called from the info button in the repository
    * not very nice to set cmdClass/Cmd manually, if everything
    * works through ilCtrl in the future this may be changed
    */
    public function infoScreenObject()
    {
        $this->ctrl->setCmd("showSummary");
        $this->ctrl->setCmdClass("ilinfoscreengui");
        $this->infoScreen();
    }

    /**
     * show information screen
     */
    public function infoScreen()
    {
        $ilTabs = $this->tabs;
        if (!$this->access_manager->canAccessInfoScreen()) {
            $this->noPermission();
        }
        $ilTabs->activateTab("info_short");

        $info = $this->survey_service->ui()->infoScreen($this, $this->toolbar);

        $this->ctrl->forwardCommand($info);
    }

    public function addLocatorItems()
    {
        $ilLocator = $this->locator;
        switch ($this->ctrl->getCmd()) {
            case "next":
            case "previous":
            case "start":
            case "resume":
            case "redirectQuestion":
                $ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "infoScreen"), "", $_GET["ref_id"]);
                break;
            case "evaluation":
            case "checkEvaluationAccess":
            case "evaluationdetails":
            case "evaluationuser":
                $ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTargetByClass("ilsurveyevaluationgui", "evaluation"), "", $_GET["ref_id"]);
                break;
            case "create":
            case "save":
            case "cancel":
            case "importSurvey":
            case "cloneAll":
                break;
            case "infoScreen":
                $ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "infoScreen"), "", $_GET["ref_id"]);
                break;
        default:
                $ilLocator->addItem($this->object->getTitle(), $this->ctrl->getLinkTarget($this, "infoScreen"), "", $_GET["ref_id"]);
                        
                // this has to be done here because ilSurveyEditorGUI is called after finalizing the locator
                if ((int) $_GET["q_id"] && !(int) $_REQUEST["new_for_survey"]) {
                    // not on create
                    // see ilObjSurveyQuestionPool::addLocatorItems
                    $q_id = (int) $_GET["q_id"];
                    $q_type = SurveyQuestion::_getQuestionType($q_id) . "GUI";
                    $this->ctrl->setParameterByClass($q_type, "q_id", $q_id);
                    $ilLocator->addItem(
                        SurveyQuestion::_getTitle($q_id),
                        $this->ctrl->getLinkTargetByClass(array("ilSurveyEditorGUI", $q_type), "editQuestion")
                    );
                }
                break;
        }
    }
    
   
   
    /**
    * redirect script
    *
    * @param	string		$a_target
    */
    public static function _goto($a_target, $a_access_code = "")
    {
        global $DIC;

        $ilAccess = $DIC->access();
        $lng = $DIC->language();
        
        // see ilObjSurveyAccess::_checkGoto()
        if (strlen($a_access_code)) {
            $_SESSION["anonymous_id"][ilObject::_lookupObjId($a_target)] = $a_access_code;
            $_GET["baseClass"] = "ilObjSurveyGUI";
            $_GET["cmd"] = "infoScreen";
            $_GET["ref_id"] = $a_target;
            include("ilias.php");
            exit;
        }
        
        if ($ilAccess->checkAccess("visible", "", $a_target) ||
            $ilAccess->checkAccess("read", "", $a_target)) {
            $_GET["baseClass"] = "ilObjSurveyGUI";
            $_GET["cmd"] = "infoScreen";
            $_GET["ref_id"] = $a_target;
            include("ilias.php");
            exit;
        } elseif ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID)) {
            ilUtil::sendFailure(sprintf(
                $lng->txt("msg_no_perm_read_item"),
                ilObject::_lookupTitle(ilObject::_lookupObjId($a_target))
            ), true);
            ilObjectGUI::_gotoRepositoryRoot();
        }
    }
    
    public function getUserResultsTable($a_active_id)
    {
        $rtpl = new ilTemplate("tpl.svy_view_user_results.html", true, true, "Modules/Survey");
        
        $show_titles = (bool) $this->object->getShowQuestionTitles();
        
        foreach ($this->object->getSurveyPages() as $page) {
            if (count($page) > 0) {
                // question block
                if (count($page) > 1) {
                    if ((bool) $page[0]["questionblock_show_blocktitle"]) {
                        $rtpl->setVariable("BLOCK_TITLE", trim($page[0]["questionblock_title"]));
                    }
                }
                
                // questions
                foreach ($page as $question) {
                    $question_gui = $this->object->getQuestionGUI($question["type_tag"], $question["question_id"]);
                    if (is_object($question_gui)) {
                        $rtpl->setCurrentBlock("question_bl");
                        
                        // heading
                        if (strlen($question["heading"])) {
                            $rtpl->setVariable("HEADING", trim($question["heading"]));
                        }
                        
                        $rtpl->setVariable(
                            "QUESTION_DATA",
                            $question_gui->getPrintView(
                                $show_titles,
                                (bool) $question["questionblock_show_questiontext"],
                                $this->object->getId(),
                                $this->object->loadWorkingData($question["question_id"], $a_active_id)
                            )
                        );
                        
                        $rtpl->parseCurrentBlock();
                    }
                }
                
                $rtpl->setCurrentBlock("block_bl");
                $rtpl->parseCurrentBlock();
            }
        }
        
        return $rtpl->get();
    }
    
    protected function viewUserResultsObject()
    {
        $ilUser = $this->user;
        $tpl = $this->tpl;
        $ilTabs = $this->tabs;
        
        $anonymous_code = $_SESSION["anonymous_id"][$this->object->getId()];
        $active_id = $this->object->getActiveID($ilUser->getId(), $anonymous_code, 0);

        if (!$this->run_manager->hasFinished($ilUser->getId(), $anonymous_code) ||
            !$active_id) {
            $this->ctrl->redirect($this, "infoScreen");
        }
        
        $ilTabs->clearTargets();
        $ilTabs->setBackTarget(
            $this->lng->txt("btn_back"),
            $this->ctrl->getLinkTarget($this, "infoScreen")
        );
        
        $html = $this->getUserResultsTable($active_id);
        $tpl->setContent($html);
    }
    
    protected function getUserResultsPlain($a_active_id)
    {
        $res = array();
        
        $show_titles = (bool) $this->object->getShowQuestionTitles();
        
        foreach ($this->object->getSurveyPages() as $page) {
            if (count($page) > 0) {
                $res[] = "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
                
                // question block
                if (count($page) > 1) {
                    if ((bool) $page[0]["questionblock_show_blocktitle"]) {
                        $res[$this->lng->txt("questionblock")] = trim($page[0]["questionblock_title"]) . "\n";
                    }
                }
                
                // questions
                
                $page_res = array();
                
                foreach ($page as $question) {
                    $question_gui = $this->object->getQuestionGUI($question["type_tag"], $question["question_id"]);
                    if (is_object($question_gui)) {
                        $question_parts = array();
                        
                        // heading
                        if (strlen($question["heading"])) {
                            $question_parts[$this->lng->txt("heading")] = trim($question["heading"]);
                        }
                        
                        if ($show_titles) {
                            $question_parts[$this->lng->txt("title")] = trim($question["title"]);
                        }
                        
                        if ((bool) $question["questionblock_show_questiontext"]) {
                            $question_parts[$this->lng->txt("question")] = trim(strip_tags($question_gui->object->getQuestionText()));
                        }
                        
                        $answers = $question_gui->getParsedAnswers(
                            $this->object->loadWorkingData($question["question_id"], $a_active_id),
                            true
                        );
                        
                        if (sizeof($answers)) {
                            $multiline = false;
                            if (sizeof($answers) > 1 ||
                                get_class($question_gui) == "SurveyTextQuestionGUI") {
                                $multiline = true;
                            }
                            
                            $parts = array();
                            foreach ($answers as $answer) {
                                $text = null;
                                if ($answer["textanswer"]) {
                                    $text = ' ("' . $answer["textanswer"] . '")';
                                }
                                if (!isset($answer["cols"])) {
                                    if (isset($answer["title"])) {
                                        $parts[] = $answer["title"] . $text;
                                    } elseif (isset($answer["value"])) {
                                        $parts[] = $answer["value"];
                                    } elseif ($text) {
                                        $parts[] = substr($text, 2, -1);
                                    }
                                }
                                // matrix
                                else {
                                    $tmp = array();
                                    foreach ($answer["cols"] as $col) {
                                        $tmp[] = $col["title"];
                                    }
                                    $parts[] = $answer["title"] . ": " . implode(", ", $tmp) . $text;
                                }
                            }
                            $question_parts[$this->lng->txt("answer")] =
                                ($multiline ? "\n" : "") . implode("\n", $parts);
                        }
                        
                        $tmp = array();
                        foreach ($question_parts as $type => $value) {
                            $tmp[] = $type . ": " . $value;
                        }
                        $page_res[] = implode("\n", $tmp);
                    }
                }
                
                $res[] = implode("\n\n-------------------------------\n\n", $page_res);
            }
        }
        
        $res[] = "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
        
        return implode("\n", $res);
    }
    
    public function sendUserResultsMail($a_active_id, $a_recipient)
    {
        $ilUser = $this->user;
        
        $finished = $this->object->getSurveyParticipants(array($a_active_id));
        $finished = array_pop($finished);
        $finished = ilDatePresentation::formatDate(new ilDateTime($finished["finished_tstamp"], IL_CAL_UNIX));
                
        $body = ilMail::getSalutation($ilUser->getId()) . "\n\n";
        $body .= $this->lng->txt("svy_mail_own_results_body") . "\n";
        $body .= "\n" . $this->lng->txt("obj_svy") . ": " . $this->object->getTitle() . "\n";
        $body .= ilLink::_getLink($this->object->getRefId(), "svy") . "\n";
        $body .= "\n" . $this->lng->txt("survey_results_finished") . ": " . $finished . "\n\n";
        
        if ($this->object->hasMailOwnResults()) {
            $subject = "svy_mail_own_results_subject";
            $body .= $this->getUserResultsPlain($a_active_id);
        } else {
            $subject = "svy_mail_confirmation_subject";
        }
        
        // $body .= ilMail::_getAutoGeneratedMessageString($this->lng);
        $body .= ilMail::_getInstallationSignature();

        /** @var ilMailMimeSenderFactory $senderFactory */
        $senderFactory = $GLOBALS["DIC"]["mail.mime.sender.factory"];

        $mmail = new ilMimeMail();
        $mmail->From($senderFactory->system());
        $mmail->To($a_recipient);
        $mmail->Subject(sprintf($this->lng->txt($subject), $this->object->getTitle()), true);
        $mmail->Body($body);
        $mmail->Send();
    }
        
    public function mailUserResultsObject()
    {
        $ilUser = $this->user;
        
        $anonymous_code = $_SESSION["anonymous_id"][$this->object->getId()];
        $active_id = $this->object->getActiveID($ilUser->getId(), $anonymous_code, 0);
        if (!$this->run_manager->hasFinished($ilUser->getId(), $anonymous_code) ||
            !$active_id) {
            $this->ctrl->redirect($this, "infoScreen");
        }
        
        $recipient = $_POST["mail"];
        if (!$recipient) {
            $recipient = $ilUser->getEmail();
        }
        if (!ilUtil::is_email($recipient)) {
            $this->ctrl->redirect($this, "infoScreen");
        }
        
        $this->sendUserResultsMail($active_id, $recipient);
        
        ilUtil::sendSuccess($this->lng->txt("mail_sent"), true);
        $this->ctrl->redirect($this, "infoScreen");
    }
    
    /**
     * Check rbac or position permission
     * @param string $a_rbac_permission
     * @param string $a_position_permission
     * @return bool
     */
    protected function checkRbacOrPositionPermission($a_rbac_permission, $a_position_permission)
    {
        $access = $GLOBALS['DIC']->access();
        return $access->checkRbacOrPositionPermissionAccess(
            $a_rbac_permission,
            $a_position_permission,
            $this->object->getRefId()
        );
    }
}
