<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DrizzlePHP\DrizzlePHP;
use App\Schemas\UsersSchema;

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=test', 'username', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db = new DrizzlePHP($pdo);

// Basic select
$users = $db->select(UsersSchema::class)
    ->where('age', '>', 18)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->get();

// Insert
$db->insert(UsersSchema::class)
    ->values([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'age' => 25,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ])
    ->execute();

// Update
$db->update(UsersSchema::class)
    ->set(['age' => 26])
    ->where('id', '=', 1)
    ->execute();

// Delete
$db->delete(UsersSchema::class)
    ->where('id', '=', 1)
    ->execute();
