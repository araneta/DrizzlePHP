# DrizzlePHP

A type-safe query builder for PHP inspired by Drizzle ORM for TypeScript.

## Features

- Type-safe column validation
- Fluent query builder API
- Schema-based definitions using PHP 8.1+ attributes
- Built on top of PDO
- Runtime validation of column names

Requirements
------------

*   PHP 8.1 or higher
*   PDO extension

Quick Start
-----------

### 1\. Define Your Schema

    <?php
    use DrizzlePHP\Schema\Schema;
    use DrizzlePHP\Attributes\Table;
    use DrizzlePHP\Attributes\Column;
    
    #[Table('users')]
    class UsersSchema extends Schema
    {
     #[Column('id','int',primary:true,autoIncrement:true)]
     public int $id;
     
     #[Column('name','string')]
     public string $name;
     
     #[Column('email','string')]
     public string $email;
     
     #[Column('age','int',nullable:true)]
     public? int $age;
    
    }

### 2\. Build Type-Safe Queries

    <?php
    use DrizzlePHP\DrizzlePHP;
    
    $pdo = new PDO('mysql:host=localhost;dbname=test','user','pass');
    $db = new DrizzlePHP($pdo);
    
    // Select with validation
    $users = $db->select(UsersSchema::class)
     ->where('age','>',18)// ✅ Valid column
     ->where('invalid_col','=',1)// ❌ Throws InvalidColumnException
     ->orderBy('name')
     ->limit(10)
     ->get();
     
    // Insert with validation
    $db->insert(UsersSchema::class)
     ->values([
     'name'=>'John Doe',
     'email'=>'john@example.com',
     'age'=>25
     ])
     ->execute();
     
    // Update with validation
    $db->update(UsersSchema::class)
     ->set(['age'=>26])
     ->where('id','=',1)
     ->execute();
    
    // Delete with validation
    $db->delete(UsersSchema::class)
     ->where('id','=',1)
     ->execute();

     
## Installation

```bash
composer require drizzle-php/drizzle-php


