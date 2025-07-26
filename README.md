# DrizzlePHP ORM

A high-performance, type-safe PHP ORM inspired by Drizzle ORM.

## Features

- 🚀 **High Performance** - Faster than Laravel Eloquent
- 🛡️ **Type Safety** - Static typing with IDE autocompletion
- 📝 **Drizzle-like Syntax** - Familiar chainable query builder
- 🔗 **Full Join Support** - Inner, left, and complex joins
- 👁️ **Database Views** - First-class view support
- 💾 **Minimal Memory** - Lightweight with minimal overhead

## Installation

```bash
composer require drizzle-php/orm
```

## Quick Start

```php
<?php
use DrizzlePHP\Database;

// Define your table schema
class UsersTable extends Table {
    public readonly IntColumn $id;
    public readonly StringColumn $name;
    public readonly StringColumn $email;
    
    public function __construct() {
        parent::__construct('users');
        $this->id = new IntColumn('id', $this->tableName, true);
        $this->name = new StringColumn('name', $this->tableName);
        $this->email = new StringColumn('email', $this->tableName);
    }
}

// Use the ORM
$pdo = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$db = new Database($pdo);
$users = new UsersTable();

// Type-safe queries
$results = $db->select()
    ->from($users)
    ->where(eq($users->id, 5))
    ->fetchAll();
```

## Documentation

- [Installation Guide](docs/installation.md)
- [Quick Start](docs/quick-start.md)
- [Schema Definition](docs/schema-definition.md)
- [Querying Data](docs/querying.md)
- [Working with Views](docs/views.md)
- [Performance Guide](docs/performance.md)

## Performance

DrizzlePHP is designed for performance:
- ~10x faster than Eloquent for large datasets
- Minimal memory footprint
- Direct PDO integration
- No Active Record overhead

## License

MIT License - see [LICENSE](LICENSE) file.
