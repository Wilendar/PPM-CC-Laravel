<?php

namespace App\Policies;

use App\Models\BugReport;
use App\Models\User;

/**
 * PPM Bug Report Policy
 *
 * Policy dla systemu zgloszen bledow i helpdesk.
 * Rozroznia uprawnienia dla uzytkownikow (view own) i adminow (manage all).
 *
 * Permissions per Role:
 * - Admin: Wszystko (via BasePolicy::before)
 * - Manager: viewAny, manage, assignReports, createInternalComment
 * - Zalogowany user: create, viewOwn, addPublicComment
 */
class BugReportPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any reports.
     * Admin/Manager can view all reports.
     */
    public function viewAny(User $user): bool
    {
        $canViewAny = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'viewAny', 'BugReport', $canViewAny);
        return $canViewAny;
    }

    /**
     * Determine whether the user can view the report.
     * Users can view their own reports, Managers can view all.
     */
    public function view(User $user, BugReport $report): bool
    {
        // Manager+ can view all
        if ($this->hasRoleOrHigher($user, 'Manager')) {
            $this->logAuthAttempt($user, 'view', "BugReport:{$report->id}", true);
            return true;
        }

        // Users can view their own reports
        $isOwner = $report->reporter_id === $user->id;
        $canView = $isOwner && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'view', "BugReport:{$report->id}", $canView);
        return $canView;
    }

    /**
     * Determine whether the user can create reports.
     * All active logged-in users can create bug reports.
     */
    public function create(User $user): bool
    {
        $canCreate = $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'create', 'BugReport', $canCreate);
        return $canCreate;
    }

    /**
     * Determine whether the user can update the report.
     * Only Managers can update reports.
     */
    public function update(User $user, BugReport $report): bool
    {
        $canUpdate = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'update', "BugReport:{$report->id}", $canUpdate);
        return $canUpdate;
    }

    /**
     * Determine whether the user can delete the report.
     * Only Managers can delete reports.
     */
    public function delete(User $user, BugReport $report): bool
    {
        $canDelete = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'delete', "BugReport:{$report->id}", $canDelete);
        return $canDelete;
    }

    /**
     * Determine whether the user can manage the report.
     * Manage includes: change status, assign, resolve, reject.
     */
    public function manage(User $user, BugReport $report): bool
    {
        $canManage = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'manage', "BugReport:{$report->id}", $canManage);
        return $canManage;
    }

    /**
     * Determine whether the user can view their own reports.
     * All active users can view their own reports.
     */
    public function viewOwn(User $user): bool
    {
        $canViewOwn = $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'viewOwn', 'BugReport', $canViewOwn);
        return $canViewOwn;
    }

    /**
     * Determine whether the user can assign reports to other users.
     * Only Managers can assign reports.
     */
    public function assign(User $user, BugReport $report): bool
    {
        $canAssign = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'assign', "BugReport:{$report->id}", $canAssign);
        return $canAssign;
    }

    /**
     * Determine whether the user can add a public comment.
     * Report owner and Managers can add public comments.
     */
    public function addPublicComment(User $user, BugReport $report): bool
    {
        // Manager can always add comments
        if ($this->hasRoleOrHigher($user, 'Manager')) {
            return true;
        }

        // Reporter can add comments to their own reports (if still open)
        $canComment = $report->reporter_id === $user->id
            && $report->isOpen()
            && $this->isActiveUser($user);

        $this->logAuthAttempt($user, 'addPublicComment', "BugReport:{$report->id}", $canComment);
        return $canComment;
    }

    /**
     * Determine whether the user can add an internal comment.
     * Only Managers can add internal (admin-only) comments.
     */
    public function addInternalComment(User $user, BugReport $report): bool
    {
        $canInternalComment = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'addInternalComment', "BugReport:{$report->id}", $canInternalComment);
        return $canInternalComment;
    }

    /**
     * Determine whether the user can view internal comments.
     * Only Managers can view internal comments.
     */
    public function viewInternalComments(User $user, BugReport $report): bool
    {
        return $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
    }

    /**
     * Determine whether the user can change report status.
     * Only Managers can change status.
     */
    public function changeStatus(User $user, BugReport $report): bool
    {
        $canChangeStatus = $this->hasRoleOrHigher($user, 'Manager') && $this->isActiveUser($user);
        $this->logAuthAttempt($user, 'changeStatus', "BugReport:{$report->id}", $canChangeStatus);
        return $canChangeStatus;
    }

    /**
     * Determine whether the user can resolve the report.
     * Only Managers can resolve reports.
     */
    public function resolve(User $user, BugReport $report): bool
    {
        $canResolve = $this->hasRoleOrHigher($user, 'Manager')
            && $report->isOpen()
            && $this->isActiveUser($user);

        $this->logAuthAttempt($user, 'resolve', "BugReport:{$report->id}", $canResolve);
        return $canResolve;
    }

    /**
     * Determine whether the user can reject the report.
     * Only Managers can reject reports.
     */
    public function reject(User $user, BugReport $report): bool
    {
        $canReject = $this->hasRoleOrHigher($user, 'Manager')
            && $report->isOpen()
            && $this->isActiveUser($user);

        $this->logAuthAttempt($user, 'reject', "BugReport:{$report->id}", $canReject);
        return $canReject;
    }
}
