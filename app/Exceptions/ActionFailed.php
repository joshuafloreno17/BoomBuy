<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule said no. The message is written for the person who tried,
 * so a controller can show it as-is (web: flash error; API: 422 message).
 */
class ActionFailed extends RuntimeException
{
}
