<?php

/**
 * Cleanup Cron Job Script
 * Automatically deletes expired pending registrations (older than 15 minutes)
 * Should be run periodically via cron job
 */

require_once __DIR__ . '/../models/database.php';
require_once __DIR__ . '/../models/inscriptionEnAttente.php';

// Initialize pending registration model
$pendingRegistrationModel = new PendingRegistration();

// Delete all expired registrations
$pendingRegistrationModel->deleteExpired();

echo "Cleanup completed: expired registrations deleted.";
