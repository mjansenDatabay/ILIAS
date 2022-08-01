<?php declare(strict_types = 1);

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

namespace ILIAS\Services\Mail\Service;

use ILIAS\DI\Container;
use ILIAS\Services\Mail\AutoResponder\ilAutoResponderServiceImpl;
use ILIAS\Services\Mail\AutoResponder\ilAutoResponderService;
use ilMailTemplateService;

class ilMailService
{
    protected Container $dic;

    public function __construct(Container $DIC)
    {
        $this->dic = $DIC;
    }

    public function mime() : ilMimeMailService
    {
        return new ilMimeMailService($this->dic);
    }

    public function autoresponder() : ilAutoResponderService
    {
        return new ilAutoResponderServiceImpl();
    }

    public function textTemplatesService() : ilMailTemplateService
    {
        return new ilMailTemplateService(new \ilMailTemplateRepository($this->dic->database()));
    }

}
