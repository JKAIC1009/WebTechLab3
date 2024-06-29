<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require_once './config.php';

$app = AppFactory::create();

// Add parsing middleware
$app->addBodyParsingMiddleware();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Handle preflight requests
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Instantiate db class
$db = new db();

function validateUserData($data) {
    $errors = [];
    if (!isset($data['name']) || empty(trim($data['name']))) {
        $errors['name'] = 'Name is required';
    }
    if (!isset($data['email']) || empty(trim($data['email']))) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    return $errors;
}

// Routes for users
$app->get('/users', function (Request $request, Response $response, $args) use ($db) {
    try {
        $conn = $db->connect();
        $sql = "SELECT * FROM users";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["error" => "Database error: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/users/{id}', function (Request $request, Response $response, $args) use ($db) {
    try {
        $id = $args['id'];
        $conn = $db->connect();
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $response->getBody()->write(json_encode($user));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $error = ["error" => "User not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        $error = ["error" => "Database error: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/users', function (Request $request, Response $response, $args) use ($db) {
    $data = $request->getParsedBody();
    error_log('Received data: ' . json_encode($data));
    
    $errors = validateUserData($data);
    if (!empty($errors)) {
        error_log('Validation errors: ' . json_encode($errors));
        $response->getBody()->write(json_encode(["errors" => $errors]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        $conn = $db->connect();
        $sql = "INSERT INTO users (name, email) VALUES (:name, :email)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $userId = $conn->lastInsertId();
        $newUser = ['id' => $userId, 'name' => $data['name'], 'email' => $data['email']];
        $response->getBody()->write(json_encode($newUser));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        error_log('Error creating user: ' . $e->getMessage());
        $error = ["error" => "Error creating user: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->put('/users/{id}', function (Request $request, Response $response, $args) use ($db) {
    $id = $args['id'];
    $data = $request->getParsedBody();
    error_log('Received data for update: ' . json_encode($data));
    
    $errors = validateUserData($data);
    if (!empty($errors)) {
        error_log('Validation errors: ' . json_encode($errors));
        $response->getBody()->write(json_encode(["errors" => $errors]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        $conn = $db->connect();
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        // Check if the user exists
        $checkSql = "SELECT * FROM users WHERE id = :id";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindValue(':id', $id);
        $checkStmt->execute();
        $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $updatedUser = ['id' => $id, 'name' => $data['name'], 'email' => $data['email']];
            $response->getBody()->write(json_encode($updatedUser));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $error = ["error" => "User not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        error_log('Error updating user: ' . $e->getMessage());
        $error = ["error" => "Error updating user: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->delete('/users/{id}', function (Request $request, Response $response, $args) use ($db) {
    try {
        $id = $args['id'];
        $conn = $db->connect();
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode(["message" => "User deleted successfully"]));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $error = ["error" => "User not found"];
            $response->getBody()->write(json_encode($error));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        error_log('Error deleting user: ' . $e->getMessage());
        $error = ["error" => "Error deleting user: " . $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();