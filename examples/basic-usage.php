<?php
// examples/basic-usage.php
declare(strict_types=1);
namespace DrizzlePHP;
require_once '../vendor/autoload.php';

use DrizzlePHP\Database;
use DrizzlePHP\Column\{IntColumn, StringColumn, DateTimeColumn};
use DrizzlePHP\Schema\Table;

// Define your table schema
class UsersTable extends Table
{
    public readonly IntColumn $id;
    public readonly StringColumn $name;
    public readonly StringColumn $email;
    public readonly DateTimeColumn $createdAt;
    
    public function __construct()
    {
        parent::__construct('user');
        $this->id = new IntColumn('id', $this->tableName, true);
        $this->name = new StringColumn('name', $this->tableName);
        $this->email = new StringColumn('email', $this->tableName);
        $this->createdAt = new DateTimeColumn('created_at', $this->tableName);
    }
    
    public function getColumns(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt,
        ];
    }
}

// Database connection
$pdo = new \PDO('mysql:host=localhost;dbname=drizzle', 'root', 'willamette');
$db = new Database($pdo);
$users = new UsersTable();
$db->beginTransaction();
// Example 1: Simple select
echo "=== Example 1: Simple Select ===\n";
$results = $db->select()
    ->from($users)
    ->where(eq($users->id, 15))
    ->fetchAll();
print_r($results);

// Method 1: Use the set() method instead of values() array
echo "Using set() method:\n";
$success = $db->insert()
	->into($users)
	->set($users->name, 'John Doe')
	->set($users->email, 'john@example.com')
	->set($users->createdAt, date('Y-m-d H:i:s'))
	->execute();
echo $success ? "Insert successful!\n" : "Insert failed!\n";

// Method 2: Use string keys (backward compatible)
echo "Using string keys:\n";
$success2 = $db->insert()
	->into($users)
	->values([
		'name' => 'Jane Doe2',
		'email' => 'jane2@example.com',
		'created_at' => date('Y-m-d H:i:s')
	])
	->execute();
echo $success2 ? "Insert successful!\n" : "Insert failed!\n";

// Method 3: Manual column name extraction (if values() method isn't working)
echo "Manual column name method:\n";
$insertData = [];
$insertData[$users->name->name] = 'Bob Smith';
$insertData[$users->email->name] = 'bob@example.com';
$insertData[$users->createdAt->name] = date('Y-m-d H:i:s');

$success3 = $db->insert()
	->into($users)
	->values($insertData)
	->execute();
echo $success3 ? "Insert successful!\n" : "Insert failed!\n";

// Example 3: Complex query
echo "\n=== Example 3: Complex Query ===\n";
$results = $db->select($users->name, $users->email)
	->from($users)
	->where(and_(
		like($users->name, '%John%'),
		gt($users->id, 1)
	))
	->orderBy($users->name)
	->limit(10)
	->fetchAll();
print_r($results);

// Example 4: Update
echo "\n=== Example 4: Update ===\n";
$rowsAffected = $db->update()
	->table($users)
	->set($users->name, 'Jane Doe Updated')  // Use single set method
	->where(eq($users->email, 'john@example.com'))
	->execute();
echo "Rows affected: $rowsAffected\n";
$db->commit();
echo "\n=== All examples completed! ===\n";
