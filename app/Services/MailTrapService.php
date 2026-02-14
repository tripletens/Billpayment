<?php

namespace App\Services;

/**
 * Backwards-compatible alias for the namespaced MailTrapService wrapper.
 * Some code or IDEs may reference App\Services\MailTrapService; this class
 * simply extends the namespaced implementation so those references resolve.
 */
class MailTrapService extends \App\Services\MailTrap\MailTrapService
{
}
