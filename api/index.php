<?php
// ============================================================
//  api/index.php — Unified Colors Manager RESTful API
//
//  GET    /api/index.php?ping=1   — status ping health check
//  POST   /api/index.php (login)  — authenticate user
//  GET    /api/index.php          — list all colors for user
//  GET    /api/index.php?q=term   — partial search colors
//  GET    /api/index.php?id=1     — get single color by ID
//  POST   /api/index.php (color)  — create new color
//  PUT    /api/index.php?id=1     — update color by ID
//  DELETE /api/index.php?id=1     — delete color by ID
// ============================================================

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

setCORSHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ping
if ($method === 'GET' && (isset($_GET['ping']) || (isset($_GET['action']) && $_GET['action'] === 'ping'))) {
    respond(200, ['status' => 'OK', 'message' => 'Hello from contacts app API!', 'timestamp' => time()]);
}

// Login and Signup
if ($method === 'POST') {

    $body = getRequestBody();

    //Sign up section
    //Contains firstName, lastName, username, password in JSON format
    if (isset($body['firstName'])) {

        $firstName = clean($body['firstName']);
        $lastName = clean($body['lastName']);
        $username = clean($body['username']);
        $password = clean($body['password']);

        if ($firstName == '' || $lastName == '' || $username == '' || $password == '') {
            respond(400, ['error' => 'Please fill in all fields']);
        }

        $checkStmt = $db->prepare('SELECT ID FROM Users WHERE Username = :u LIMIT 1');
        $checkStmt->execute([':u' => $username]);
        $existingUser = $checkStmt->fetch();

        if ($existingUser) {
            respond(409, ['error' => 'That username is already taken']);
        }

        $hashedPassword = md5($password);

        $insertStmt = $db->prepare('INSERT INTO Users (FirstName, LastName, Username, Password) VALUES (:fn, :ln, :u, :p)');
        $insertStmt->execute([
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':u' => $username,
            ':p' => $hashedPassword
        ]);

        $newId = $db->lastInsertId();

        respond(201, [
            'id' => (int) $newId,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'token' => (string) $newId,
            'error' => ''
        ]);
    }

    //Login section contains login, password
    if (isset($body['login'])) {

        $login = clean($body['login']);
        $password = clean($body['password']);

        if ($login == '' || $password == '') {
            respond(400, ['error' => 'Please enter your username and password']);
        }

        $stmt = $db->prepare('SELECT ID, FirstName, LastName, Password FROM Users WHERE Username = :login LIMIT 1');
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        $passwordIsCorrect = false;

        if ($user) {
            if ($user['Password'] == $password) { //this is here because some of the passwords are not hashed in the DB I think
                $passwordIsCorrect = true;
            }
            if ($user['Password'] == md5($password)) {
                $passwordIsCorrect = true;
            }
        }

        if ($user && $passwordIsCorrect) {
            respond(200, [
                'id' => (int) $user['ID'],
                'firstName' => $user['FirstName'],
                'lastName' => $user['LastName'],
                'token' => (string) $user['ID'],
                'error' => '',
		'message' => 'hello'
            ]);
        } else {
            respond(401, [
                'id' => 0,
                'firstName' => '',
                'lastName' => '',
                'error' => 'No Records Found'
            ]);
        }
    }

    respond(400, ['error' => 'Invalid request']);
}

$userId = requireAuth();

switch($method){
    case 'GET': //search contacts
        $search = isset($_GET['q'])  ? trim($_GET['q'])  : (isset($_GET['search']) ? trim($_GET['search']) : null);
        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $stmt = $db->prepare('SELECT ID AS id, FirstName AS name, LastName AS lastname FROM Contacts WHERE UserID = :uid AND (FirstName LIKE :q OR LastName LIKE :l) ORDER BY FirstName');
            $stmt->execute([':uid' => $userId, ':q' => $like, ':l' => $like]);
            $rows = $stmt->fetchAll();
            respond(200, ['contacts' => $rows, 'error' => '']);
        }

        break;
}

// 2. Unauthenticated Login (POST with login & password in body)
/*
if ($method === 'POST') {
    $body = getRequestBody();
    if (isset($body['login']) && isset($body['password'])) {
        $login    = clean($body['login']);
        $password = clean($body['password']);

        if (!$login || !$password) {
            respond(400, ['error' => 'Login and password are required']);
        }

        $stmt = $db->prepare('SELECT ID, firstName, lastName FROM Users WHERE Login = :login AND Password = :pass LIMIT 1');
        $stmt->execute([':login' => $login, ':pass' => $password]);
        $user = $stmt->fetch();

        if ($user) {
            respond(200, [
                'id'        => (int) $user['ID'],
                'firstName' => $user['firstName'],
                'lastName'  => $user['lastName'],
                'token'     => (string) $user['ID'],
                'error'     => ''
            ]);
        } else {
            respond(401, [
                'id'        => 0,
                'firstName' => '',
                'lastName'  => '',
                'error'     => 'No Records Found'
            ]);
        }
    }
}

// 3. All other routes require an authenticated user
$userId = requireAuth();

switch ($method) {

    // ── GET: search, list, or single color ──────────────────
    case 'GET':
        $id     = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $search = isset($_GET['q'])  ? trim($_GET['q'])  : (isset($_GET['search']) ? trim($_GET['search']) : null);

        // Single color by ID
        if ($id) {
            $stmt = $db->prepare('SELECT ID as id, Name as name, UserID as user_id FROM Colors WHERE ID = :id AND UserID = :uid LIMIT 1');
            $stmt->execute([':id' => $id, ':uid' => $userId]);
            $color = $stmt->fetch();
            if (!$color) {
                respond(404, ['error' => 'Color not found']);
            }
            respond(200, $color);
        }

        // Search colors (partial match)
        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';
            $stmt = $db->prepare('SELECT ID as id, Name as name FROM Colors WHERE UserID = :uid AND Name LIKE :q ORDER BY Name');
            $stmt->execute([':uid' => $userId, ':q' => $like]);
            $rows = $stmt->fetchAll();
            $results = array_column($rows, 'name');
            if (empty($results)) {
                respond(200, ['results' => [], 'colors' => [], 'error' => 'No Records Found']);
            }
            respond(200, ['results' => $results, 'colors' => $rows, 'error' => '']);
        }

        // List all colors
        $stmt = $db->prepare('SELECT ID as id, Name as name FROM Colors WHERE UserID = :uid ORDER BY Name');
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll();
        $results = array_column($rows, 'name');
        if (empty($results)) {
            respond(200, ['results' => [], 'colors' => [], 'error' => 'No Records Found']);
        }
        respond(200, ['results' => $results, 'colors' => $rows, 'error' => '']);
        break;

    // ── POST: create color ───────────────────────────────────
    case 'POST':
        $body  = getRequestBody();
        $color = clean($body['color'] ?? $body['name'] ?? '');
        if (!$color) {
            respond(400, ['error' => 'Color name is required']);
        }

        $stmt = $db->prepare('INSERT INTO Colors (UserID, Name) VALUES (:uid, :name)');
        $stmt->execute([':uid' => $userId, ':name' => $color]);

        respond(201, [
            'message' => 'Color created',
            'id'      => (int) $db->lastInsertId(),
            'error'   => ''
        ]);
        break;

    // ── PUT: update color ─────────────────────────────────────
    case 'PUT':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            respond(400, ['error' => 'Color ID is required — use ?id=']);
        }

        $check = $db->prepare('SELECT ID FROM Colors WHERE ID = :id AND UserID = :uid LIMIT 1');
        $check->execute([':id' => $id, ':uid' => $userId]);
        if (!$check->fetch()) {
            respond(404, ['error' => 'Color not found']);
        }

        $body  = getRequestBody();
        $color = clean($body['color'] ?? $body['name'] ?? '');
        if (!$color) {
            respond(400, ['error' => 'Color name is required']);
        }

        $stmt = $db->prepare('UPDATE Colors SET Name = :name WHERE ID = :id AND UserID = :uid');
        $stmt->execute([':name' => $color, ':id' => $id, ':uid' => $userId]);

        respond(200, ['message' => 'Color updated', 'error' => '']);
        break;

    // ── DELETE: delete color ──────────────────────────────────
    case 'DELETE':
        $id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $name = isset($_GET['name']) ? clean($_GET['name']) : '';

        if ($id > 0) {
            $stmt = $db->prepare('DELETE FROM Colors WHERE ID = :id AND UserID = :uid');
            $stmt->execute([':id' => $id, ':uid' => $userId]);
        } elseif ($name !== '') {
            $stmt = $db->prepare('DELETE FROM Colors WHERE Name = :name AND UserID = :uid LIMIT 1');
            $stmt->execute([':name' => $name, ':uid' => $userId]);
        } else {
            respond(400, ['error' => 'Color ID or Name is required — use ?id= or ?name=']);
        }

        if ($stmt->rowCount() === 0) {
            respond(404, ['error' => 'Color not found']);
        }

        respond(200, ['message' => 'Color deleted', 'error' => '']);
        break;

    default:
        respond(405, ['error' => 'Method not allowed']);
}
*/
