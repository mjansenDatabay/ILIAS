<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Modules\Test;

use ILIAS\Data\Result;
use ILIAS\Data\Result\Ok;
use ILIAS\Data\Result\Error;
use ilDBConstants;
use ilAccessHandler;
use ilDBInterface;
use Closure;
use ilObject;
use ILIAS\components\Test\Incident;
use ILIAS\components\Test\SimpleAccess;

class AccessFileUploadPreview implements SimpleAccess
{
    private ilDBInterface $database;
    private ilAccessHandler $access;
    private Incident $incident;
    /** @var Closure(int): list<int> */
    private Closure $references_of;
    /** @var Closure(int, bool): string */
    private Closure $type_of;

    /**
     * @param Closure(int): list<int> $references_of
     * @param Closure(int, bool): string $type_of
     */
    public function __construct(
        ilDBInterface $database,
        ilAccessHandler $access,
        ?Incident $incident = null,
        $references_of = [ilObject::class, '_getAllReferences'],
        $type_of = [ilObject::class, '_lookupType']
    ) {
        $this->database = $database;
        $this->access = $access;
        $this->incident = $incident ?? new Incident();
        $this->references_of = Closure::fromCallable($references_of);
        $this->type_of = Closure::fromCallable($type_of);
    }

    public function isPermitted(string $path): Result
    {
        $question_id = $this->questionId($path);
        if (!$question_id) {
            return new Error('Not a question image path of test questions.');
        }

        $object_id = $this->objectId($question_id);
        if (!$object_id) {
            return new Ok(false);
        }

        $permitted = $this->incident->any([$this, 'refIdPermitted'], ($this->references_of)($object_id));

        return new Ok($permitted);
    }

    public function refIdPermitted(int $ref_id): bool
    {
        $ref_id = $ref_id;
        $type = ($this->type_of)($ref_id, true);

        switch ($type) {
            case 'qpl': return $this->access->checkAccess('read', '', $ref_id);
            case 'tst': return $this->access->checkAccess('write', '', $ref_id);
            default: return false;
        }
    }

    private function questionId(string $path): ?int
    {
        $results = [];
        if (!preg_match(':/assessment/qst_preview/\d+/(\d+)/fileuploads/([^/]+)$:', $path, $results)) {
            return null;
        }

        return (int) $results[1];
    }

    private function objectId(int $question_id): ?int
    {
        $object_id = $this->database->fetchAssoc($this->database->queryF(
            'SELECT obj_fi FROM qpl_questions WHERE question_id = %s',
            [ilDBConstants::T_INTEGER],
            [$question_id]
        ))['obj_fi'] ?? null;

        return $object_id ? (int) $object_id : null;
    }
}
