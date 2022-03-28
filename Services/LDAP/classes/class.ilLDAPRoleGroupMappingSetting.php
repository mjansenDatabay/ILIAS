<?php declare(strict_types=1);

/******************************************************************************
 *
 * This file is part of ILIAS, a powerful learning management system.
 *
 * ILIAS is licensed with the GPL-3.0, you should have received a copy
 * of said license along with the source code.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 *      https://www.ilias.de
 *      https://github.com/ILIAS-eLearning
 *
 *****************************************************************************/
/**
 * @author Fabian Wolf <wolf@leifos.de>
 */
class ilLDAPRoleGroupMappingSetting
{
    private ilDBInterface $db;
    private ilObjectDataCache $ilObjDataCache;
    private ilRbacReview $rbacreview;

    private int $mapping_id;

    public function __construct(int $a_mapping_id)
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->ilObjDataCache = $DIC['ilObjDataCache'];
        $this->rbacreview = $DIC['rbacreview'];
        $this->mapping_id = $a_mapping_id;
    }
    
    /**
     * read data from db
     */
    public function read() : void
    {
        $query = "SELECT * FROM ldap_rg_mapping "
                . "WHERE mapping_id = " . $this->db->quote($this->getMappingId(), 'integer');
        $set = $this->db->query($query);
        $rec = $this->db->fetchAssoc($set);
        // TODO PHP8-REVIEW You should case the values when populating the object
        $this->setMappingId($rec["mapping_id"]);
        $this->setServerId($rec["server_id"]);
        $this->setURL($rec["url"]);
        $this->setDN($rec["dn"]);
        $this->setMemberAttribute($rec["member_attribute"]);
        $this->setMemberISDN($rec["member_isdn"]);
        $this->setRole($rec["role"]);
        $this->setMappingInfo($rec["mapping_info"]);
        $this->setMappingInfoType($rec["mapping_info_type"]);
    }
    
    /**
     * delete mapping by id
     */
    public function delete() : void
    {
        $query = "DELETE FROM ldap_rg_mapping " .
            "WHERE mapping_id = " . $this->db->quote($this->getMappingId(), 'integer');
        $this->db->manipulate($query);
    }
    
    /**
     * update mapping by id
     */
    public function update() : void
    {
        $query = "UPDATE ldap_rg_mapping " .
                    "SET server_id = " . $this->db->quote($this->getServerId(), 'integer') . ", " .
                    "url = " . $this->db->quote($this->getURL(), 'text') . ", " .
                    "dn =" . $this->db->quote($this->getDN(), 'text') . ", " .
                    "member_attribute = " . $this->db->quote($this->getMemberAttribute(), 'text') . ", " .
                    "member_isdn = " . $this->db->quote($this->getMemberISDN(), 'integer') . ", " .
                    "role = " . $this->db->quote($this->getRole(), 'integer') . ", " .
                    "mapping_info = " . $this->db->quote($this->getMappingInfo(), 'text') . ", " .
                    "mapping_info_type = " . $this->db->quote($this->getMappingInfoType(), 'integer') . " " .
                    "WHERE mapping_id = " . $this->db->quote($this->getMappingId(), 'integer');
        $this->db->manipulate($query);
    }
    
    /**
     * create new mapping
     */
    public function save() : void
    {
        $this->setMappingId($this->db->nextId('ldap_rg_mapping'));
        $query = "INSERT INTO ldap_rg_mapping (mapping_id,server_id,url,dn,member_attribute,member_isdn,role,mapping_info,mapping_info_type) " .
                    "VALUES ( " .
                    $this->db->quote($this->getMappingId(), 'integer') . ", " .
                    $this->db->quote($this->getServerId(), 'integer') . ", " .
                    $this->db->quote($this->getURL(), 'text') . ", " .
                    $this->db->quote($this->getDN(), 'text') . ", " .
                    $this->db->quote($this->getMemberAttribute(), 'text') . ", " .
                    $this->db->quote($this->getMemberISDN(), 'integer') . ", " .
                    $this->db->quote($this->getRole(), 'integer') . ", " .
                    $this->db->quote($this->getMappingInfo(), 'text') . ", " .
                    $this->db->quote($this->getMappingInfoType(), 'integer') .
                    ")";
        $this->db->manipulate($query);
    }

    public function getMappingId() : int
    {
        return $this->mapping_id;
    }

    public function setMappingId(int $a_value) : void
    {
        $this->mapping_id = $a_value;
    }
    
    /**
     * get server id
     * @return int server id id
     */
    public function getServerId()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->server_id;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set server id
     * @param int $a_value server id
     */
    public function setServerId($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->server_id = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get url
     * @return string url
     */
    public function getURL()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->url;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set url
     * @param string $a_value url
     */
    public function setURL($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->url = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get group dn
     * @return string
     */
    public function getDN()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->dn;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set group dn
     * @param string $a_value
     */
    public function setDN($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->dn = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get Group Member Attribute
     * @return string
     */
    public function getMemberAttribute()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->member_attribute;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set Group Member Attribute
     * @param string $a_value
     */
    public function setMemberAttribute($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->member_attribute = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get Member Attribute Value is DN
     * @return bool
     */
    public function getMemberISDN()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->member_isdn;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set Member Attribute Value is DN
     * @param bool $a_value
     */
    public function setMemberISDN($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->member_isdn = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get ILIAS Role Name id
     * @return int
     */
    public function getRole()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->role;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set ILIAS Role Name id
     * @param int $a_value
     */
    public function setRole($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->role = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get ILIAS Role Name
     * @return string
     */
    public function getRoleName()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->ilObjDataCache->lookupTitle($this->role);// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set ILIAS Role Name
     * @param string $a_value
     */
    public function setRoleByName($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->role = $this->rbacreview->roleExists(ilUtil::stripSlashes($a_value));// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get Information Text
     * @return string
     */
    public function getMappingInfo()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->mapping_info;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set Information Text
     * @param string $a_value
     */
    public function setMappingInfo($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->mapping_info = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * get Show Information also in the Repository/Personal Desktop
     * @return bool
     */
    public function getMappingInfoType()// TODO PHP8-REVIEW Please add an explicit return type
    {
        return $this->mapping_info_type;// TODO PHP8-REVIEW The property is declared dynamically
    }
    
    /**
     * set Show Information also in the Repository/Personal Desktop
     * @param bool $a_value
     */
    public function setMappingInfoType($a_value) : void// TODO PHP8-REVIEW A type hint is missing here
    {
        $this->mapping_info_type = $a_value;// TODO PHP8-REVIEW The property is declared dynamically
    }
}
