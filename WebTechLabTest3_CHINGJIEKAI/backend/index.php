<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require_once './config.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
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
        $response->getBody()->write(json_encode(["error" => "Error: " . $e->getMessage()]));
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
            $response->getBody()->write(json_encode(["error" => "User not found"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["error" => "Database error: " . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/users', function (Request $request, Response $response, $args) use ($db) {
    try {
        $data = $request->getParsedBody();
        error_log('Received data: ' . json_encode($data)); // Log received data
        
        $errors = validateUserData($data);
        if (!empty($errors)) {
            error_log('Validation errors: ' . json_encode($errors)); // Log validation errors
            $response->getBody()->write(json_encode(["errors" => $errors]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        $conn = $db->connect();
        $name = $data['name'];
        $email = $data['email'];
        $sql = "INSERT INTO users (name, email) VALUES (:name, :email)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $userId = $conn->lastInsertId();
        $newUser = ['id' => $userId, 'name' => $name, 'email' => $email];
        $response->getBody()->write(json_encode($newUser));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        error_log('Error creating user: ' . $e->getMessage()); // Log any exceptions
        $response->getBody()->write(json_encode(["error" => "Error creating user: " . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->put('/users/{id}', function (Request $request, Response $response, $args) use ($db) {
    try {
        $id = $args['id'];
        $data = $request->getParsedBody();
        $errors = validateUserData($data);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(["errors" => $errors]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $conn = $db->connect();
        $name = $data['name'];
        $email = $data['email'];
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        $updatedUser = ['id' => $id, 'name' => $name, 'email' => $email];
        $response->getBody()->write(json_encode($updatedUser));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["error" => "Error updating user: " . $e->getMessage()]));
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
        $response->getBody()->write(json_encode(["message" => "User deleted successfully"]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode(["error" => "Error deleting user: " . $e->getMessage()]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();