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

use ILIAS\Authentication\Session\SessionRotationPolicy;

class ilAuthSession
{
    private const string SESSION_AUTH_AUTHENTICATED = '_authsession_authenticated';
    private const string SESSION_AUTH_USER_ID = '_authsession_user_id';
    private const string SESSION_AUTH_EXPIRED = '_authsession_expired';

    private static ?ilAuthSession $instance = null;

    private ilLogger $logger;

    private string $id = '';
    private int $user_id = 0;
    private bool $expired = false;
    private bool $authenticated = false;

    private function __construct(ilLogger $logger)
    {
        $this->logger = $logger;
    }

    public static function getInstance(ilLogger $logger): ilAuthSession
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self($logger);
    }

    protected function getLogger(): ilLogger
    {
        return $this->logger;
    }

    /**
     * Start auth session
     */
    public function init(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->getLogger()->error(__METHOD__ . ' called with active session.');
            $this->getLogger()->logStack(ilLogLevel::ERROR);

            $this->rejectForeignSessionIdIfUnknown();

            return false;
        }

        session_start();

        $this->setId(session_id());

        $user_id = (int) (ilSession::get(self::SESSION_AUTH_USER_ID) ?? ANONYMOUS_USER_ID);

        if ($user_id) {
            $this->getLogger()->debug('Resuming old session for user: ' . $user_id);
            $this->setUserId($user_id);
            $this->expired = (bool) ilSession::get(self::SESSION_AUTH_EXPIRED);
            $this->authenticated = (bool) ilSession::get(self::SESSION_AUTH_AUTHENTICATED);

            $this->validateExpiration();
        } else {
            $this->getLogger()->debug('Started new session.');
            $this->setUserId(ANONYMOUS_USER_ID);
            $this->expired = false;
            $this->authenticated = false;
        }

        return true;
    }

    /**
     * Check if current session is valid (authenticated and not expired)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && $this->isAuthenticated();
    }

    public function regenerateId(): void
    {
        $this->applySessionRotation(SessionRotationPolicy::Rotate);
    }

    /**
     * End the current auth context and establish an anonymous session.
     */
    public function logout(SessionRotationPolicy $session_rotation = SessionRotationPolicy::Rotate): void
    {
        if ($session_rotation === SessionRotationPolicy::RejectForeign) {
            throw new InvalidArgumentException(
                SessionRotationPolicy::RejectForeign->name . ' is only applied during session bootstrap.'
            );
        }

        $this->getLogger()->debug(
            'Logout called for: ' . $this->getUserId() . ' rotation: ' . $session_rotation->name
        );

        if ($session_rotation === SessionRotationPolicy::Preserve) {
            $this->ensureAnonymousContext();
            return;
        }

        session_destroy();

        $this->init();
        $this->setAuthenticated(ANONYMOUS_USER_ID);
        $this->applySessionRotation(SessionRotationPolicy::Rotate);
    }

    /**
     * Prepare the login screen: anonymous auth state in the current session (no destroy, no rotate).
     */
    public function ensureAnonymousContext(): void
    {
        $this->getLogger()->debug(
            'Ensure anonymous context for: ' . $this->getUserId()
        );
        $this->establishAnonymousAuthState();
    }

    /**
     * Establish an authenticated session after successful login.
     */
    public function onLoginSuccess(int $user_id): void
    {
        $this->setAuthenticated($user_id);
        $this->applySessionRotation(SessionRotationPolicy::Rotate);
    }

    /**
     * Transition an expired privileged session to anonymous.
     */
    public function onSessionExpired(): void
    {
        $this->establishAnonymousAuthState();
        $this->applySessionRotation(SessionRotationPolicy::Rotate);
    }

    /**
     * Check if session is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated || $this->user_id === ANONYMOUS_USER_ID;
    }

    private function setAuthenticated(int $a_user_id): void
    {
        $this->authenticated = true;
        $this->user_id = $a_user_id;
        ilSession::set(self::SESSION_AUTH_AUTHENTICATED, true);
        ilSession::set(self::SESSION_AUTH_USER_ID, $a_user_id);
        $this->setExpired(false);
    }

    public function isFullyAuthenticated(): bool
    {
        return $this->isValid() && $this->user_id > 0 && $this->user_id !== ANONYMOUS_USER_ID;
    }

    public function isAnonymouslyAuthenticated(): bool
    {
        return $this->isValid() && $this->user_id === ANONYMOUS_USER_ID;
    }

    /**
     * Check if current is or was expired in last request.
     */
    public function isExpired(): bool
    {
        return $this->expired && $this->user_id !== ANONYMOUS_USER_ID;
    }

    /**
     * Set session expired
     */
    public function setExpired(bool $a_status): void
    {
        $this->expired = $a_status;
        ilSession::set(self::SESSION_AUTH_EXPIRED, (int) $a_status);
    }

    /**
     * Set authenticated user id
     */
    public function setUserId(int $a_id): void
    {
        $this->user_id = $a_id;
    }

    /**
     * Get authenticated user id
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * Check expired value of session
     */
    protected function validateExpiration(): bool
    {
        if ($this->isExpired()) {
            // keep status
            return false;
        }

        if (time() > ilSession::lookupExpireTime($this->getId())) {
            $this->setExpired(true);
            return false;
        }

        return true;
    }

    /**
     * Set id
     */
    protected function setId(string $a_id): void
    {
        $this->id = $a_id;
    }

    /**
     * get session id
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Persist anonymous auth flags (same state for preserve and post-expiry transitions).
     */
    private function establishAnonymousAuthState(): void
    {
        $this->setAuthenticated(ANONYMOUS_USER_ID);
    }

    private function rejectForeignSessionIdIfUnknown(): void
    {
        if (defined('IL_PHPUNIT_TEST')) {
            return;
        }

        if (!in_array(session_id(), ['', false], true) && !ilSession::_exists(session_id())) {
            $this->applySessionRotation(SessionRotationPolicy::RejectForeign);
        }
    }

    private function applySessionRotation(SessionRotationPolicy $policy): void
    {
        if ($policy === SessionRotationPolicy::Preserve) {
            return;
        }

        $old_session_id = session_id();

        session_regenerate_id(
            $policy === SessionRotationPolicy::Rotate
        );

        $this->setId(session_id());

        $label = $policy === SessionRotationPolicy::RejectForeign
            ? 'Session reject foreign id'
            : 'Session regenerate id';
        $this->getLogger()->info(
            $label . ': [' . substr($old_session_id, 0, 5) . '] -> [' . substr($this->getId(), 0, 5) . ']'
        );
    }
}
