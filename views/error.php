<?php
// views/error.php (Self-Contained Error Page)

// This view should NOT include header.php or footer.php
// It receives $pageTitle and $error (only if ENVIRONMENT is 'development') from ErrorHandler::displayErrorPage

// Determine if we are in development mode
$isDevelopment = defined('ENVIRONMENT') && ENVIRONMENT === 'development';

// Set default title if not provided
$pageTitle = isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Application Error';

// Prepare error details for display (only if in development and error data exists)
$errorDetails = null;
if ($isDevelopment && isset($error) && is_array($error)) {
    $errorDetails = $error; // Use the passed error array
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Minimal Styles for Error Page -->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .error-container {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 30px 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
            text-align: center;
        }
        h1 {
            color: #dc3545; /* Red for error titles */
            font-size: 1.8em;
            margin-bottom: 15px;
            font-weight: 600;
        }
        p {
            color: #6c757d; /* Gray for text */
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .error-details {
            margin-top: 25px;
            padding: 20px;
            background-color: #f8d7da; /* Light red background */
            border: 1px solid #f5c6cb; /* Red border */
            color: #721c24; /* Dark red text */
            border-radius: 4px;
            text-align: left;
            font-size: 0.9em;
            overflow-x: auto; /* Allow horizontal scrolling for long lines */
        }
        .error-details h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1em;
            color: #58151c;
        }
        .error-details pre {
            white-space: pre-wrap; /* Wrap long lines in trace */
            word-wrap: break-word;
            margin-top: 10px;
            max-height: 300px; /* Limit trace height */
            overflow-y: auto; /* Scroll long traces */
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 3px;
        }
        .error-message, .error-location {
             margin-bottom: 10px;
             word-wrap: break-word;
        }
        .error-actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            font-weight: 500;
            color: #ffffff;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            background-color: #007bff; /* Primary button color */
            border: 1px solid #007bff;
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 0.3rem;
            text-decoration: none;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            margin: 5px;
        }
        .btn-primary {
            background-color: #1A4D5A; /* Theme primary */
            border-color: #1A4D5A;
        }
        .btn-primary:hover {
            background-color: #164249; /* Darker primary */
            border-color: #164249;
        }
        .btn-secondary {
            background-color: #6c757d; /* Gray */
            border-color: #6c757d;
            color: #ffffff;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if ($errorDetails): // Display detailed error in development ?>
            <h1><?= $errorDetails['type'] ?? 'Application Error' ?></h1>
            <div class="error-details">
                <p class="error-message"><strong>Message:</strong> <?= htmlspecialchars($errorDetails['message'] ?? 'N/A') ?></p>
                <p class="error-location">
                    <strong>Location:</strong> <?= htmlspecialchars($errorDetails['file'] ?? 'N/A') ?> on line <?= htmlspecialchars($errorDetails['line'] ?? 'N/A') ?>
                </p>
                <?php if (!empty($errorDetails['trace'])): ?>
                    <div class="error-trace">
                        <h3>Stack Trace:</h3>
                        <pre><?= htmlspecialchars($errorDetails['trace']) ?></pre>
                    </div>
                <?php endif; ?>
                 <?php if (!empty($errorDetails['context'])): ?>
                     <div class="error-context">
                         <h3>Context:</h3>
                         <pre><?= htmlspecialchars(print_r($errorDetails['context'], true)) ?></pre>
                     </div>
                 <?php endif; ?>
            </div>
            <div class="error-actions">
                 <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                 <a href="/" class="btn btn-primary">Return Home</a>
            </div>

        <?php else: // Display generic error in production ?>

            <h1>Oops! Something Went Wrong</h1>
            <p>We encountered an unexpected issue. Please try refreshing the page, or contact our support team if the problem persists.</p>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Return Home</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>

        <?php endif; ?>
    </div>
</body>
</html>
