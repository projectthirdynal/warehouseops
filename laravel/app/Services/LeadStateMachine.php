<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;

class LeadStateMachine
{
    /**
     * allowed transitions matrix
     * [Current Status] => [Allowed Next Statuses]
     */
    const ALLOWED_TRANSITIONS = [
        Lead::STATUS_NEW => [
            Lead::STATUS_CALLING,
            Lead::STATUS_NO_ANSWER,
            Lead::STATUS_REJECT,
            // Cannot go directly to SALE, CALLBACK
        ],
        Lead::STATUS_CALLING => [
            Lead::STATUS_NO_ANSWER,
            Lead::STATUS_REJECT,
            Lead::STATUS_CALLBACK,
            Lead::STATUS_SALE,
            Lead::STATUS_NEW, // Allowed for re-assignment
        ],
        Lead::STATUS_NO_ANSWER => [
            Lead::STATUS_CALLING,
            Lead::STATUS_REJECT,
            Lead::STATUS_CALLBACK,
            Lead::STATUS_SALE, // Can convert after a no-answer if they call back
        ],
        Lead::STATUS_CALLBACK => [
            Lead::STATUS_CALLING,
            Lead::STATUS_NO_ANSWER,
            Lead::STATUS_REJECT,
            Lead::STATUS_SALE,
        ],
        Lead::STATUS_REJECT => [
            Lead::STATUS_NEW, // Only system/admin can recycle to NEW
        ],
        Lead::STATUS_SALE => [
            Lead::STATUS_DELIVERED, // System/Waybill update
            Lead::STATUS_CANCELLED,
            Lead::STATUS_REJECT, // Post-sale rejection
        ],
        Lead::STATUS_DELIVERED => [
            Lead::STATUS_RETURNED, // System update
        ],
        Lead::STATUS_RETURNED => [
            Lead::STATUS_NEW, // System recycle
        ],
        Lead::STATUS_CANCELLED => [
            Lead::STATUS_NEW, // Reactivation
        ],
        Lead::STATUS_REORDER => [ // Special status
            Lead::STATUS_NEW,
            Lead::STATUS_CALLING,
        ]
    ];

    /**
     * Check if a transition is valid.
     * 
     * @return bool|string True if valid, error message string if invalid
     */
    public function canTransition(Lead $lead, string $newStatus, User $actor): bool|string
    {
        $currentStatus = $lead->status;

        // 1. Same status is always allowed (unless logic forbids updates, handled elsewhere)
        if ($currentStatus === $newStatus) {
            return true;
        }

        // 2. Admin override (admins can force transitions, but we should still warn or log)
        // For now, let's strictly enforce logic even for admins to maintain data integrity,
        // unless it's a specific correction scenario.
        // Uncomment below to allow admins to bypass:
        // if ($actor->role === User::ROLE_ADMIN || $actor->role === User::ROLE_SUPERADMIN) { return true; }

        // 3. Check matrix
        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        
        // Handle undefined current states (shouldn't happen, but safe fallback)
        if (empty($allowed) && !array_key_exists($currentStatus, self::ALLOWED_TRANSITIONS)) {
            // If current status isn't in matrix, allow moving OUT of it to a valid 'start' state like NEW
            if ($newStatus === Lead::STATUS_NEW) return true;
            return "Current status '{$currentStatus}' is not defined in state machine.";
        }

        if (!in_array($newStatus, $allowed)) {
            return "Invalid transition: Cannot move from '{$currentStatus}' to '{$newStatus}'.";
        }

        // 4. Role-based restrictions
        
        // Agents cannot move lead to NEW (only system/admin recycling)
        if ($newStatus === Lead::STATUS_NEW) {
            if ($actor->role === User::ROLE_AGENT) {
                return "Agents cannot reset leads to NEW manually.";
            }
        }

        // Agents cannot set DELIVERED (only system integration)
        if ($newStatus === Lead::STATUS_DELIVERED) {
            if ($actor->role === User::ROLE_AGENT) {
                return "Agents cannot mark leads as DELIVERED manually.";
            }
        }

        return true;
    }

    /**
     * Validate transition or throw exception
     * 
     * @throws \Exception
     */
    public function validateTransition(Lead $lead, string $newStatus, User $actor): void
    {
        $result = $this->canTransition($lead, $newStatus, $actor);
        
        if ($result !== true) {
            throw new \Exception($result);
        }
    }
}
