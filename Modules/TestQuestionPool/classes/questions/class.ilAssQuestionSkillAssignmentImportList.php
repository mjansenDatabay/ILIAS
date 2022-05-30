<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Modules/TestQuestionPool/classes/questions/class.ilAssQuestionSkillAssignmentImport.php';

/**
 * @author        Björn Heyser <bheyser@databay.de>
 * @version        $Id$
 *
 * @package     Modules/TestQuestionPool
 */
class ilAssQuestionSkillAssignmentImportList implements Iterator
{
    /**
     * @var array[ilAssQuestionSkillAssignmentImport]
     */
    protected $assignments;
    
    /**
     * ilAssQuestionSkillAssignmentImportList constructor.
     */
    public function __construct()
    {
        $this->assignments = array();
    }
    
    /**
     * @param ilAssQuestionSkillAssignmentImport $assignment
     */
    public function addAssignment(ilAssQuestionSkillAssignmentImport $assignment) : void
    {
        $this->assignments[] = $assignment;
    }
    
    public function assignmentsExist() : bool
    {
        return count($this->assignments) > 0;
    }
    
    /**
     * @return ilAssQuestionSkillAssignmentImport
     */
    public function current() : ilAssQuestionSkillAssignmentImport
    {
        return current($this->assignments);
    }
    
    /**
     * @return ilAssQuestionSkillAssignmentImport
     */
    public function next() : ilAssQuestionSkillAssignmentImport
    {
        return next($this->assignments);
    }
    
    /**
     * @return integer|bool
     */
    public function key()
    {
        $res = key($this->assignments);
        return $res;
    }
    
    /**
     * @return bool
     */
    public function valid() : bool
    {
        $res = key($this->assignments);
        return $res !== null;
    }
    
    /**
     * @return ilAssQuestionSkillAssignmentImport|bool
     */
    public function rewind()
    {
        return reset($this->assignments);
    }
    
    public function sleep() : void
    {
        // TODO: Implement __sleep() method.
    }
    
    public function wakeup() : void
    {
        // TODO: Implement __wakeup() method.
    }
}
