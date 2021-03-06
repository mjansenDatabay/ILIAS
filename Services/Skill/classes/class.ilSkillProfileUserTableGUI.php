<?php

/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once("./Services/Table/classes/class.ilTable2GUI.php");

/**
 * TableGUI class for skill profile user assignment
 *
 * @author Alex Killing <alex.killing@gmx.de>
 * @version $Id$
 *
 * @ingroup Services
 */
class ilSkillProfileUserTableGUI extends ilTable2GUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilAccessHandler
	 */
	protected $access;

	/**
	 * Constructor
	 */
	function __construct($a_parent_obj, $a_parent_cmd, $a_profile, $a_write_permission = false)
	{
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->lng = $DIC->language();
		$this->access = $DIC->access();
		$ilCtrl = $DIC->ctrl();
		$lng = $DIC->language();
		$ilAccess = $DIC->access();
		$lng = $DIC->language();
		
		$this->profile = $a_profile;
		parent::__construct($a_parent_obj, $a_parent_cmd);
		$this->setData($this->profile->getAssignments());
		$this->setTitle($lng->txt("skmg_assigned_users"));
		
		$this->addColumn("", "", "1px", true);
		$this->addColumn($this->lng->txt("type"), "type");
		$this->addColumn($this->lng->txt("name"), "name");
//		$this->addColumn($this->lng->txt("actions"));
		
		$this->setFormAction($ilCtrl->getFormAction($a_parent_obj));
		$this->setRowTemplate("tpl.profile_user_row.html", "Services/Skill");
		$this->setSelectAllCheckbox("id[]");

		if ($a_write_permission)
		{
			$this->addMultiCommand("confirmUserRemoval", $lng->txt("remove"));
		}
		//$this->addCommandButton("", $lng->txt(""));
	}
	
	/**
	 * Fill table row
	 */
	protected function fillRow($a_set)
	{
		$lng = $this->lng;

		$this->tpl->setVariable("TYPE", $a_set["type"]);
		$this->tpl->setVariable("NAME", $a_set["name"]);
		$this->tpl->setVariable("ID", $a_set["id"]);
	}

}
?>
