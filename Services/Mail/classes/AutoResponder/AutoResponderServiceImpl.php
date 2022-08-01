<?php
declare(strict_types=1);

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

namespace ILIAS\Services\Mail\AutoResponder;

use ilObjUser;
use DateTimeImmutable;
use DateInterval;
use ilFormatMail;
use ilMailOptions;
use ILIAS\Data\Clock\ClockInterface;
use ILIAS\Data\Factory as DataFactory;

class AutoResponderServiceImpl implements AutoResponderService
{
    protected bool $auto_responder_status;
    /** @var ilMailOptions[] $auto_responder_data */
    protected array $auto_responder_data;
    protected int $global_idle_time_interval;
    /** @var callable  */
    protected $loginByUsrIdCallable;
    protected AutoResponderRepository $auto_responder_repository;
    protected ClockInterface $clock;

    public function __construct(
        callable $loginByUsrIdCallable = null,
        int $global_idle_time_interval = null,
        bool $auto_responder_status = false,
        array $auto_responder_data = [],
        AutoResponderRepository $auto_responder_repository = null,
        ClockInterface $clock = null

    ) {
        global $DIC;
        $this->global_idle_time_interval = $global_idle_time_interval ?? (int) $DIC->settings()->get('mail_auto_responder_idle_time');
        $this->loginByUsrIdCallable = $loginByUsrIdCallable ?? static function (int $usrId) : string {
            return ilObjUser::_lookupLogin($usrId);
        };
        $this->auto_responder_status = $auto_responder_status;
        $this->auto_responder_data = $auto_responder_data;
        $this->auto_responder_repository = $auto_responder_repository ?? new AutoResponderDatabaseRepository($DIC->database());
        $this->clock = (new DataFactory())->clock()->utc();
    }

    public function isAutoResponderEnabled() : bool
    {
        return $this->auto_responder_status;
    }

    public function enableAutoResponder() : void
    {
        $this->auto_responder_status = true;
    }

    public function disableAutoResponder() : void
    {
        $this->auto_responder_status = false;
    }

    public function handleAutoResponderMails(int $receiver_usr_id) : void
    {
        if ($this->auto_responder_data) {
            foreach ($this->auto_responder_data as $usr_id => $mail_options) {
                $auto_responder = $this->auto_responder_repository->findBySenderIdAndReceiverId(
                    $usr_id,
                    $receiver_usr_id
                );
                if (!$auto_responder) {
                    $auto_responder = new AutoResponder(
                        $usr_id,
                        $receiver_usr_id,
                        $this->clock->now()->sub(new DateInterval('P' . $this->global_idle_time_interval . 'D'))
                    );
                }
                if (!$auto_responder->hasAutoResponderSent(
                    $this->clock->now(),
                    $this->global_idle_time_interval
                )) {
                    $tmpmail = new ilFormatMail($usr_id);
                    $tmpmail->setSaveInSentbox(false);
                    $tmpmail->sendMail(
                        ($this->loginByUsrIdCallable)($receiver_usr_id),
                        '',
                        '',
                        $mail_options->getAbsenceAutoResponderSubject(),
                        $mail_options->getAbsenceAutoResponderBody() . chr(13) . chr(10) . $mail_options->getSignature(),
                        [],
                        false
                    );
                }
                $auto_responder->setSendtime($this->clock->now());
                $this->auto_responder_repository->store($auto_responder);
            }
        }
    }

    public function handleAutoResponderData(int $usr_id_key, ilMailOptions $mail_options) : void
    {
        if ($this->isAutoResponderEnabled() && $mail_options->isAbsent()) {
            $this->auto_responder_data[$usr_id_key] = $mail_options;
        }
    }

    public function getAutoResponderData(int $usr_id_key) : ?ilMailOptions
    {
        return $this->auto_responder_data[$usr_id_key] ?? null;
    }

    public function removeAutoResponderData(int $usr_id_key) : void
    {
        unset($this->auto_responder_data[$usr_id_key]);
    }

    public function emptyAutoResponderData() : void
    {
        $this->auto_responder_data = [];
    }
}
