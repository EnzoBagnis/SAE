<?php

namespace App\Model\UseCase\Ports;

/**
 * Port interface for the LoginUserUseCase.
 *
 * Extends {@see UserFinderPort} to provide the lookup operation
 * needed to authenticate a user by email address.
 */
interface UserAuthFinderPort extends UserFinderPort
{
    // Inherits findByEmail() from UserFinderPort
}
