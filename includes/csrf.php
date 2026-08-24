<?php
// =====================================================================
// includes/csrf.php
//
// CSRF protection helper functions.
// =====================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ---------------------------------------------------------------------
// Generate / return CSRF token
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


// ---------------------------------------------------------------------
// Verify submitted CSRF token
// ---------------------------------------------------------------------
function verify_csrf_token(?string $token): bool
{
    if (
        empty($token) ||
        empty($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $token
    );
}


// ---------------------------------------------------------------------
// Protect a POST request
// ---------------------------------------------------------------------
function require_csrf_token(): void
{
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token($token)) {

        http_response_code(403);

        die(
            '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">

                <title>Invalid Request</title>

                <link
                    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
                    rel="stylesheet"
                >

                <link
                    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css"
                    rel="stylesheet"
                >
            </head>

            <body class="bg-light">

                <div class="container">
                    <div
                        class="row justify-content-center align-items-center"
                        style="min-height: 100vh;"
                    >

                        <div class="col-md-6">

                            <div class="card border-0 shadow-sm">

                                <div class="card-body text-center p-5">

                                    <i
                                        class="bi bi-shield-exclamation text-danger"
                                        style="font-size: 4rem;"
                                    ></i>

                                    <h2 class="mt-3">
                                        Invalid Request
                                    </h2>

                                    <p class="text-muted">
                                        Your request could not be verified.
                                        Please go back and try again.
                                    </p>

                                    <a
                                        href="javascript:history.back()"
                                        class="btn btn-primary"
                                    >
                                        <i class="bi bi-arrow-left"></i>
                                        Go Back
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>
                </div>

            </body>
            </html>'
        );
    }
}