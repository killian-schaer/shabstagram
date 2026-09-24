<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;

$currentUser = Session::requireAuth();
$db = Database::connection();

$hitId = (int) ($_GET['hit'] ?? 0);

$stmt = $db->prepare(
    'SELECT p.pdf_path, s.owner_user_id
     FROM search_hits sh
     INNER JOIN publications p ON p.id = sh.publication_id
     INNER JOIN searches s ON s.id = sh.search_id
     WHERE sh.id = :hit_id LIMIT 1'
);
$stmt->execute(['hit_id' => $hitId]);
$row = $stmt->fetch();

if ($row === false
    || empty($row['pdf_path'])
    || !is_file($row['pdf_path'])
    || ($currentUser['id'] !== null && (int) $row['owner_user_id'] !== (int) $currentUser['id'])
) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="publication.pdf"');
readfile($row['pdf_path']);
