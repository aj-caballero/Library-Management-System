<?php

declare(strict_types=1);

/**
 * Inactive Account Management Script
 * Handles archiving of inactive accounts and sending notifications
 * Run this via cron job (e.g., daily or weekly)
 */

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/otp.php';

$results = [
    'warnings_sent' => 0,
    'archived' => 0,
    'errors' => []
];

try {
    // Define inactivity thresholds
    $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
    $oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));

    // 1. Send warnings to students inactive for 6 months (but not yet warned)
    $stmt = $pdo->prepare("
        SELECT id, email, fullname, grade_level, last_login_at
        FROM users
        WHERE role = 'student'
        AND is_active = 1
        AND is_archived = 0
        AND last_login_at IS NOT NULL
        AND last_login_at < :six_months_ago
        AND inactivity_warning_sent_at IS NULL
    ");
    $stmt->execute([':six_months_ago' => $sixMonthsAgo]);
    $warningUsers = $stmt->fetchAll();

    foreach ($warningUsers as $user) {
        try {
            $subject = 'Library Account Inactivity Notice';
            $message = "
Dear {$user['fullname']},

We noticed that you haven't logged into your library account for 6 months. 

If you don't log in within the next 6 months, your account will be archived and you will need to contact your administrator to reactivate it.

Please log in to your account to keep it active:
" . basePath('/login.php') . "

Best regards,
Paliparan NHS Library Management System
            ";

            // Send email
            sendEmail($user['email'], $subject, $message);

            // Mark warning as sent
            $updateStmt = $pdo->prepare("UPDATE users SET inactivity_warning_sent_at = NOW() WHERE id = :id");
            $updateStmt->execute([':id' => (int) $user['id']]);

            $results['warnings_sent']++;
            logSystemActivity($pdo, null, "Inactivity warning sent to {$user['fullname']} ({$user['email']})");
        } catch (Exception $e) {
            $results['errors'][] = "Failed to send warning to {$user['email']}: " . $e->getMessage();
        }
    }

    // 2. Archive students inactive for 1 year
    $stmt = $pdo->prepare("
        SELECT id, email, fullname, grade_level, last_login_at
        FROM users
        WHERE role = 'student'
        AND is_active = 1
        AND is_archived = 0
        AND last_login_at IS NOT NULL
        AND last_login_at < :one_year_ago
    ");
    $stmt->execute([':one_year_ago' => $oneYearAgo]);
    $archiveUsers = $stmt->fetchAll();

    foreach ($archiveUsers as $user) {
        try {
            $isGrade10 = strpos($user['grade_level'], '10') !== false;
            $reason = $isGrade10 
                ? 'Grade 10 graduation - archived and cannot be reactivated' 
                : 'Inactive for more than 1 year - archived';

            // Archive the account
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET is_archived = 1, archived_reason = :reason
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':reason' => $reason,
                ':id' => (int) $user['id']
            ]);

            $results['archived']++;
            logSystemActivity($pdo, null, "Account archived: {$user['fullname']} ({$user['email']}) - {$reason}");

            // Send notification email
            $subject = 'Library Account Archived';
            if ($isGrade10) {
                $message = "
Dear {$user['fullname']},

Your library account has been archived as you have graduated from Grade 10. Thank you for using our library system!

If you believe this is an error, please contact your administrator.

Best regards,
Paliparan NHS Library Management System
                ";
            } else {
                $message = "
Dear {$user['fullname']},

Your library account has been archived due to inactivity. To reactivate your account, please contact your administrator.

Best regards,
Paliparan NHS Library Management System
                ";
            }

            sendEmail($user['email'], $subject, $message);

        } catch (Exception $e) {
            $results['errors'][] = "Failed to archive account {$user['email']}: " . $e->getMessage();
        }
    }

} catch (Exception $e) {
    $results['errors'][] = "General error: " . $e->getMessage();
}

// Log final results
$summary = "Inactivity Management Results: {$results['warnings_sent']} warnings sent, {$results['archived']} accounts archived.";
if (!empty($results['errors'])) {
    $summary .= " Errors: " . implode('; ', $results['errors']);
}
logSystemActivity($pdo, null, $summary);

// Return results (useful if called via HTTP)
header('Content-Type: application/json');
echo json_encode($results);
?>
