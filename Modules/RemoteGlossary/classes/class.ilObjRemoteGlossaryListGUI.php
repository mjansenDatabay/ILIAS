<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once "Services/Object/classes/class.ilObjectListGUI.php";

/** 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ingroup ModulesRemoteGlossary
*/
class ilObjRemoteGlossaryListGUI extends ilObjectListGUI
{
	/**
	 * Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct()
	{
	 	parent::__construct();
	}
	
	/**
	 * init
	 *
	 * @access public
	 */
	public function init()
	{
		$this->copy_enabled = false;
		$this->static_link_enabled = true;
		$this->delete_enabled = true;
		$this->cut_enabled = true;
		$this->subscribe_enabled = true;
		$this->link_enabled = true;
		$this->info_screen_enabled = true;
		$this->type = 'rglo';
		$this->gui_class_name = 'ilobjremoteglossarygui';
		
		include_once('Services/AdvancedMetaData/classes/class.ilAdvancedMDSubstitution.php');
		$this->substitutions = ilAdvancedMDSubstitution::_getInstanceByObjectType($this->type);
		if($this->substitutions->isActive())
		{
			$this->substitutions_enabled = true;
		}
		
		// general commands array
		include_once('Modules/RemoteGlossary/classes/class.ilObjRemoteGlossaryAccess.php');
		$this->commands = ilObjRemoteGlossaryAccess::_getCommands();
		
	}



	/**
	 * get properties (offline)
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function getProperties()
	{
		global $lng;

		include_once('Modules/RemoteGlossary/classes/class.ilObjRemoteGlossary.php');

		if($org = ilObjRemoteGlossary::_lookupOrganization($this->obj_id))
		{
			$this->addCustomProperty($lng->txt('organization'),$org,false,true);
		}
		if(!ilObjRemoteGlossary::_lookupOnline($this->obj_id))
		{
			$this->addCustomProperty($lng->txt("status"),$lng->txt("offline"),true,true);
		}
	
		return array();
	}
	
	/**
	 * get command frame
	 *
	 * @access public
	 * @param
	 * @return
	 */
	public function getCommandFrame($a_cmd)
	{
		switch($a_cmd)
		{
			case 'show':
				include_once('./Services/WebServices/ECS/classes/class.ilECSExport.php');
				include_once('./Services/WebServices/ECS/classes/class.ilECSImport.php');				
				if(ilECSExport::_isRemote(
					ilECSImport::lookupServerId($this->obj_id),
					ilECSImport::_lookupEContentId($this->obj_id)))
				{
					return '_blank';
				}
				
			default:
				return parent::getCommandFrame($a_cmd);
		}
	}

} // END class.ilObjRemoteGlossaryListGUI
?>