<?php
// =====================================================================
// includes/security_log.php
//
// Security / Authentication Audit Logging
//
// Records:
//     - Successful login
//     - Failed login
//     - Logout
//     - Unauthorized access
//
// IMPORTANT:
//     Passwords are NEVER stored in this table.
// =====================================================================


/**
 * Get client IP address.
 *
 * REMOTE_ADDR is used instead of trusting forwarded headers.
 * This prevents users from spoofing their IP through headers.
 */
function getClientIP(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}


/**
 * Get browser/device information.
 */
function getClientUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
}


/**
 * Record a security event.
 *
 * @param PDO         $pdo
 * @param int|null    $userId
 * @param string|null $username
 * @param string      $eventType
 * @param string      $status
 *
 * @return bool
 */
function logSecurityEvent(
    PDO $pdo,
    ?int $userId,
    ?string $username,
    string $eventType,
    string $status
): bool {

    try {

        $sql = "
            INSERT INTO login_logs
            (
                user_id,
                username,
                event_type,
                status,
                ip_address,
                user_agent
            )
            VALUES
            (
                :user_id,
                :username,
                :event_type,
                :status,
                :ip_address,
                :user_agent
            )
        ";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':user_id'    => $userId,
            ':username'   => $username,
            ':event_type' => $eventType,
            ':status'     => $status,
            ':ip_address' => getClientIP(),
            ':user_agent' => getClientUserAgent()
        ]);

    } catch (PDOException $e) {

        /*
         * Logging failure should not crash the application.
         * The error is written to the PHP server error log.
         */
        error_log(
            'AIUB Portal Security Log Error: ' .
            $e->getMessage()
        );

        return false;
    }
}