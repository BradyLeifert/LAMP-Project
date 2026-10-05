<?php
// ============================================================
//  api/index.php — Contacts Manager RESTful API
//
//  GET    /api/index.php?ping=1     — status ping health check
//  POST   /api/index.php   — register a new user
//  POST   /api/index.php (login)    — authenticate user
//  GET    /api/index.php            — list contacts
//  GET    /api/index.php?q=term     — partial search contacts
//  POST   /api/index.php            — create new contact
//  PUT    /api/index.php?id=1       — update contact by ID
//  DELETE /api/index.php?id=1       — delete contact by ID
//
//  Admins only (everything below needs Role = 'Admin'):
//  GET    /api/index.php?users      — list all users
//  GET    /api/index.php?users&q=   — partial search users
//  POST   /api/index.php?users      — create a User or Admin account
//  PUT    /api/index.php?users&id=1 — change password / role / disabled
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
    if (isset($body['firstName']) && !isset($_GET['users'])) {

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
            'role' => 'User',
            'isAdmin' => false,
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

        $stmt = $db->prepare('SELECT ID, FirstName, LastName, Password, Role, IsDisabled FROM Users WHERE Username = :login LIMIT 1');
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

        //checked after the password so we don't tell people whether the account exists
        if ($user && $passwordIsCorrect && $user['IsDisabled'] == 1) {
            respond(403, [
                'id' => 0,
                'firstName' => '',
                'lastName' => '',
                'error' => 'This account has been disabled'
            ]);
        }

        if ($user && $passwordIsCorrect) {
            respond(200, [
                'id' => (int) $user['ID'],
                'firstName' => $user['FirstName'],
                'lastName' => $user['LastName'],
                'token' => (string) $user['ID'],
                'role' => $user['Role'],
                'isAdmin' => $user['Role'] == 'Admin',
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

    //respond(400, ['error' => 'Invalid request']);
}

// everything past this point needs to be signed in.
$me = currentUser($db);
$userId = (int) $me['ID'];
$isAdmin = $me['Role'] == 'Admin';

if (isset($_GET['users'])) {

    if (!$isAdmin) {
        respond(403, ['error' => 'Admin access required']);
    }

    // The user being managed comes from the URL:
    // /api/index.php?users&id=4
    $targetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    switch ($method) {

        case 'GET': //list or search users

            $search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['search']) ? trim($_GET['search']) : '');

            // Counting the contacts here so the admin page can show how many
            // entries each user has without asking for them one at a time
            $sql = 'SELECT u.ID AS id,
                           u.FirstName AS firstname,
                           u.LastName AS lastname,
                           u.Username AS username,
                           u.Role AS role,
                           u.IsDisabled AS isDisabled,
                           COUNT(c.ID) AS contactCount
                    FROM Users u
                    LEFT JOIN Contacts c ON c.UserID = u.ID';

            $params = [];

            if ($search !== '') {
                $sql = $sql . ' WHERE u.FirstName LIKE :q1 OR u.LastName LIKE :q2 OR u.Username LIKE :q3';
                $like = '%' . $search . '%';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
            }

            $sql = $sql . ' GROUP BY u.ID ORDER BY u.Username';

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();

            // MySQL hands these back as strings, so fix them up for the front end
            for ($i = 0; $i < count($users); $i++) {
                $users[$i]['id'] = (int) $users[$i]['id'];
                $users[$i]['isDisabled'] = ((int) $users[$i]['isDisabled'] == 1);
                $users[$i]['contactCount'] = (int) $users[$i]['contactCount'];
            }

            respond(200, ['users' => $users, 'error' => '']);

            break;

        case 'POST': //create a user

            $body = getRequestBody();

            $firstName = clean($body['firstname'] ?? '');
            $lastName = clean($body['lastname'] ?? '');
            $username = clean($body['username'] ?? '');
            $password = clean($body['password'] ?? '');

            $role = 'User';
            if (isset($body['role']) && $body['role'] == 'Admin') {
                $role = 'Admin';
            }

            if ($firstName == '' || $lastName == '' || $username == '' || $password == '') {
                respond(400, ['error' => 'Please fill in all fields']);
            }

            $checkStmt = $db->prepare('SELECT ID FROM Users WHERE Username = :u LIMIT 1');
            $checkStmt->execute([':u' => $username]);
            $existingUser = $checkStmt->fetch();

            if ($existingUser) {
                respond(409, ['error' => 'That username is already taken']);
            }

            $insertStmt = $db->prepare('INSERT INTO Users (FirstName, LastName, Username, Password, Role) VALUES (:fn, :ln, :u, :p, :r)');
            $insertStmt->execute([
                ':fn' => $firstName,
                ':ln' => $lastName,
                ':u' => $username,
                ':p' => md5($password),
                ':r' => $role
            ]);

            respond(201, [
                'id' => (int) $db->lastInsertId(),
                'username' => $username,
                'role' => $role,
                'error' => '',
                'message' => 'User created successfully'
            ]);

            break;

        case 'PUT': //change a user's password, role, or disabled flag

            if ($targetId <= 0) {
                respond(400, ['error' => 'A valid user ID is required']);
            }

            $body = getRequestBody();

            $updates = [];
            $params = [':id' => $targetId];

            if (isset($body['password']) && clean($body['password']) != '') {
                $updates[] = 'Password = :password';
                $params[':password'] = md5(clean($body['password']));
            }

            if (isset($body['role'])) {
                $updates[] = 'Role = :role';
                $params[':role'] = $body['role'] == 'Admin' ? 'Admin' : 'User';
            }

            if (isset($body['isDisabled'])) {
                $updates[] = 'IsDisabled = :isDisabled';
                $params[':isDisabled'] = $body['isDisabled'] ? 1 : 0;
            }

            if (count($updates) == 0) {
                respond(400, ['error' => 'Nothing to update, send a password, role, and/or isDisabled']);
            }

            $stmt = $db->prepare('UPDATE Users SET ' . implode(', ', $updates) . ' WHERE ID = :id');
            $stmt->execute($params);

            respond(200, [
                'error' => '',
                'message' => 'User updated successfully'
            ]);

            break;

        default:
            respond(405, ['error' => 'Users cannot be deleted, disable them instead']);
    }
}

$ownerId = $userId;
if ($isAdmin) {
    $ownerId = isset($_GET['owner']) ? (int) $_GET['owner'] : 0;
}

$ownerFilter = '';
$ownerParams = [];
if ($ownerId > 0) {
    $ownerFilter = ' AND UserID = :userId';
    $ownerParams[':userId'] = $ownerId;
}

switch($method){
    case 'GET': //list or search contacts

        $search = isset($_GET['q'])  ? trim($_GET['q'])  : (isset($_GET['search']) ? trim($_GET['search']) : '');

        // Joining Users so the admin list can show who owns each contact
        $sql = 'SELECT c.ID AS id,
                       c.UserID AS userId,
                       u.Username AS owner,
                       c.FirstName AS firstname,
                       c.LastName AS lastname,
                       c.Phone AS phone,
                       c.Email AS email,
                       c.IsFavorite AS isFavorite,
                       c.ProfilePicture AS pfp
                FROM Contacts c
                LEFT JOIN Users u ON u.ID = c.UserID
                WHERE 1 = 1';

        $params = [];

        if ($ownerId > 0) {
            $sql = $sql . ' AND c.UserID = :userId';
            $params[':userId'] = $ownerId;
        }

        if ($search !== '') {
            $sql = $sql . ' AND (c.FirstName LIKE :q1 OR c.LastName LIKE :q2 OR c.Phone LIKE :q3 OR c.Email LIKE :q4)';
            $like = '%' . $search . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
        }

        $sql = $sql . ' ORDER BY c.FirstName, c.LastName';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();

        respond(200, ['contacts' => $contacts, 'error' => '']);

        break;

    case 'POST': //create contact
        /*
        Create contact JSON:
        {
            "firstname" = "", //this is the requested firstname of the newly created contact, NOT the person making the request
            "lastname" = "",
            "phone" = "",
            "email" = ""
        }
        */

        $body = getRequestBody();

        $firstName = clean($body['firstname']);
        $lastName = clean($body['lastname']);
        $phone = clean($body['phone']);
        $email = clean($body['email']);
        $profilepicture = clean($body['profilepicture']);

        if($userId == '' || $firstName == '' || $lastName == ''|| $phone == ''|| $email == ''){
            respond(400, ['error' => 'Please fill in all fields']);
        }

        // An admin with ?owner=4 is adding the contact to that user's list,
        // otherwise the contact belongs to whoever is making the request
        $contactOwner = $ownerId > 0 ? $ownerId : $userId;

        // Insert contact into database
        $stmt = $db->prepare(
            'INSERT INTO Contacts (UserID, ProfilePicture, FirstName, LastName, Phone, Email)
            VALUES (:uid, :profilepicture, :firstName, :lastName, :phone, :email)'
        );

        $stmt->execute([
            ':uid' => $contactOwner,
            ':profilepicture' => $profilepicture,
            ':firstName' => $firstName,
            ':lastName' => $lastName,
            ':phone' => $phone,
            ':email' => $email
        ]);

        respond(201, [
            'error' => '',
            'message' => 'Contact created successfully'
        ]);

        break;
    
    case 'PUT': // edit contact

        $body = getRequestBody();

        // Contact ID comes from the URL:
        // /api/index.php?id=4
        $contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($contactId <= 0) {
            respond(400, ['error' => 'A valid contact ID is required']);
        }

        $firstName = clean($body['firstname'] ?? '');
        $profilepicture = clean($body['profilepicture'] ?? '');
        $lastName  = clean($body['lastname'] ?? '');
        $phone     = clean($body['phone'] ?? '');
        $email     = clean($body['email'] ?? '');
        $favorite  = clean($body['favorite'] ?? '');

        if ($favorite !== 0 && $favorite !== 1 && $favorite !== '0' && $favorite !== '1') {
            respond(400, ['error' => 'favorite must be 0 or 1']);
        }

        $favorite = (int)$favorite;

        if ($firstName === '' || $lastName === '' || $phone === '' || $email === '' || $favorite === '') {
            respond(400, ['error' => 'Please fill in all fields']);
        }

        $stmt;
        $params = $ownerParams;

        if ($profilepicture === '') {
            $stmt = $db->prepare(
                'UPDATE Contacts
                SET FirstName = :firstName,
                    LastName = :lastName,
                    Phone = :phone,
                    Email = :email,
                    IsFavorite = :favorite
                WHERE ID = :contactId' . $ownerFilter
            );

            $params[':firstName'] = $firstName;
            $params[':lastName'] = $lastName;
            $params[':phone'] = $phone;
            $params[':email'] = $email;
            $params[':favorite'] = $favorite;
            $params[':contactId'] = $contactId;
        } else {
            $stmt = $db->prepare(
                'UPDATE Contacts
                SET FirstName = :firstName,
                    ProfilePicture = :profilepicture,
                    LastName = :lastName,
                    Phone = :phone,
                    Email = :email,
                    IsFavorite = :favorite
                WHERE ID = :contactId' . $ownerFilter
            );

            $params[':firstName'] = $firstName;
            $params[':profilepicture'] = $profilepicture;
            $params[':lastName'] = $lastName;
            $params[':phone'] = $phone;
            $params[':email'] = $email;
            $params[':favorite'] = $favorite;
            $params[':contactId'] = $contactId;
        }

        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            respond(404, ['error' => 'Contact not found']);
        }

        respond(200, [
            'error' => '',
            'message' => 'Contact updated successfully'
        ]);

        break;

    case 'DELETE': // delete contact

        $contactId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($contactId <= 0) {
            respond(400, ['error' => 'A valid contact ID is required']);
        }

        $stmt = $db->prepare(
            'DELETE FROM Contacts
            WHERE ID = :contactId' . $ownerFilter
        );

        $params = $ownerParams;
        $params[':contactId'] = $contactId;

        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            respond(404, ['error' => 'Contact not found']);
        }

        respond(200, [
            'error' => '',
            'message' => 'Contact deleted successfully'
        ]);

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
