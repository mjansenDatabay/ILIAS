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
* @author Stefan Meyer <meyer@leifos.com>
*/
class ilECSParticipantSetting
{
    public const AUTH_VERSION_4 = 1;
    public const AUTH_VERSION_5 = 2;
    
    public const PERSON_EPPN = 1;
    public const PERSON_LUID = 2;
    public const PERSON_LOGIN = 3;
    public const PERSON_UID = 4;

    protected static array $instances = [];


    // :TODO: what types are needed?
    public const IMPORT_UNCHANGED = 0;
    public const IMPORT_RCRS = 1;
    public const IMPORT_CRS = 2;
    public const IMPORT_CMS = 3;
    
    private int $server_id;
    private int $mid;
    private bool $export = false;
    private bool $import = false;
    private int $import_type = 1;
    private string $title = '';
    private string $cname = '';
    private bool $token = true;
    private bool $dtoken = true;
    
    private int $auth_version = self::AUTH_VERSION_4;
    private int $person_type = self::PERSON_UID;
    

    private array $export_types = array();
    private array $import_types = array();

    private bool $exists = false;

    private ilDBInterface $db;
    
    public function __construct(int $a_server_id, int $mid)
    {
        global $DIC;

        $this->db = $DIC->database();

        $this->server_id = $a_server_id;
        $this->mid = $mid;
        $this->read();
    }
    
    /**
     * Get instance by server id and mid
     * @param int $a_server_id
     * @param int $mid
     * @return ilECSParticipantSetting
     */
    public static function getInstance(int $a_server_id, int $mid) : ilECSParticipantSetting
    {
        if (self::$instances[$a_server_id . '_' . $mid]) {
            return self::$instances[$a_server_id . '_' . $mid];
        }
        return self::$instances[$a_server_id . '_' . $mid] = new self($a_server_id, $mid);
    }


    /**
     * Get server id
     */
    public function getServerId() : int
    {
        return $this->server_id;
    }

    public function setMid(int $a_mid) : void
    {
        $this->mid = $a_mid;
    }

    public function getMid() : int
    {
        return $this->mid;
    }

    public function enableExport(bool $a_status) : void
    {
        $this->export = $a_status;
    }

    public function isExportEnabled() : bool
    {
        return $this->export;
    }

    public function enableImport(bool $a_status) : void
    {
        $this->import = $a_status;
    }

    public function isImportEnabled() : bool
    {
        return $this->import;
    }

    public function setImportType(int $a_type) : void
    {
        if ($a_type !== self::IMPORT_UNCHANGED) {
            $this->import_type = $a_type;
        }
    }

    public function getImportType() : int
    {
        return $this->import_type;
    }

    public function setTitle(string $a_title) : void
    {
        $this->title = $a_title;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function getCommunityName() : string
    {
        return $this->cname;
    }

    public function setCommunityName(string $a_name) : void
    {
        $this->cname = $a_name;
    }
    
    public function isTokenEnabled() : bool
    {
        return $this->token;
    }
    
    public function enableToken(bool $a_stat) : void
    {
        $this->token = $a_stat;
    }
    
    public function setExportTypes(array $a_types) : void
    {
        $this->export_types = $a_types;
    }
    
    public function getExportTypes() : array
    {
        return $this->export_types;
    }
    
    public function setImportTypes(array $a_types) : void
    {
        $this->import_types = $a_types;
    }
    
    public function isDeprecatedTokenEnabled() : bool
    {
        return $this->dtoken;
    }
    
    public function enableDeprecatedToken(bool $a_stat) : void
    {
        $this->dtoken = $a_stat;
    }
    
    public function getImportTypes() : array
    {
        return $this->import_types;
    }

    private function exists() : bool
    {
        return $this->exists;
    }
    
    /**
     * Update
     * Calls create automatically when no entry exists
     */
    public function update() : bool
    {
        if (!$this->exists()) {
            return $this->create();
        }
        $query = 'UPDATE ecs_part_settings ' .
            'SET ' .
            'sid = ' . $this->db->quote($this->getServerId(), 'integer') . ', ' .
            'mid = ' . $this->db->quote($this->getMid(), 'integer') . ', ' .
            'export = ' . $this->db->quote((int) $this->isExportEnabled(), 'integer') . ', ' .
            'import = ' . $this->db->quote((int) $this->isImportEnabled(), 'integer') . ', ' .
            'import_type = ' . $this->db->quote($this->getImportType(), 'integer') . ', ' .
            'title = ' . $this->db->quote($this->getTitle(), 'text') . ', ' .
            'cname = ' . $this->db->quote($this->getCommunityName(), 'text') . ', ' .
            'token = ' . $this->db->quote($this->isTokenEnabled(), 'integer') . ', ' .
            'dtoken = ' . $this->db->quote($this->isDeprecatedTokenEnabled(), 'integer') . ', ' .
            'export_types = ' . $this->db->quote(serialize($this->getExportTypes()), 'text') . ', ' .
            'import_types = ' . $this->db->quote(serialize($this->getImportTypes()), 'text') . ' ' .
            'WHERE sid = ' . $this->db->quote($this->getServerId(), 'integer') . ' ' .
            'AND mid  = ' . $this->db->quote($this->getMid(), 'integer');
        $this->db->manipulate($query);
        return true;
    }

    private function create() : bool
    {
        $query = 'INSERT INTO ecs_part_settings ' .
            '(sid,mid,export,import,import_type,title,cname,token,dtoken,export_types, import_types) ' .
            'VALUES( ' .
            $this->db->quote($this->getServerId(), 'integer') . ', ' .
            $this->db->quote($this->getMid(), 'integer') . ', ' .
            $this->db->quote((int) $this->isExportEnabled(), 'integer') . ', ' .
            $this->db->quote((int) $this->isImportEnabled(), 'integer') . ', ' .
            $this->db->quote($this->getImportType(), 'integer') . ', ' .
            $this->db->quote($this->getTitle(), 'text') . ', ' .
            $this->db->quote($this->getCommunityName(), 'text') . ', ' .
            $this->db->quote($this->isTokenEnabled(), 'integer') . ', ' .
            $this->db->quote($this->isDeprecatedTokenEnabled(), 'integer') . ', ' .
            $this->db->quote(serialize($this->getExportTypes()), 'text') . ', ' .
            $this->db->quote(serialize($this->getImportTypes()), 'text') . ' ' .
            ')';
        $this->db->manipulate($query);
        return true;
    }

    /**
     * Delete one participant entry
     */
    public function delete() : bool
    {
        $query = 'DELETE FROM ecs_part_settings ' .
            'WHERE sid = ' . $this->db->quote($this->getServerId(), 'integer') . ' ' .
            'AND mid = ' . $this->db->quote($this->getMid(), 'integer');
        $this->db->manipulate($query);
        return true;
    }

    /**
     * Read stored entry
     */
    private function read() : void
    {
        $query = 'SELECT * FROM ecs_part_settings ' .
            'WHERE sid = ' . $this->db->quote($this->getServerId(), 'integer') . ' ' .
            'AND mid = ' . $this->db->quote($this->getMid(), 'integer');

        $res = $this->db->query($query);

        $this->exists = ($res->numRows() ? true : false);

        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $this->enableExport((bool) $row->export);
            $this->enableImport((bool) $row->import);
            $this->setImportType((int) $row->import_type);
            $this->setTitle($row->title);
            $this->setCommunityName($row->cname);
            $this->enableToken((bool) $row->token);
            $this->enableDeprecatedToken((bool) $row->dtoken);
            
            $this->setExportTypes((array) unserialize($row->export_types, ['allowed_classes' => true]));
            $this->setImportTypes((array) unserialize($row->import_types, ['allowed_classes' => true]));
        }
    }
}
