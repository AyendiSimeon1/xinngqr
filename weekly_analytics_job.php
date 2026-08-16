<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config.php';

echo "Starting weekly analytics job...\n";
$sent = xinng_send_due_weekly_analytics_reports();
echo "Weekly analytics emails sent: {$sent}\n";
echo "Job completed.\n";
