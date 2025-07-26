<?php

declare(strict_types=1);

/**
 * DrizzlePHP Schema Generator
 * 
 * Automatically generates DrizzlePHP schema classes from existing database structure.
 * 
 * Usage:
 *   php tools/schema-generator.php --database=mydb --host=localhost --user=root --password=secret
 *   php tools/schema-generator.php --dsn="mysql:host=localhost;dbname=mydb" --user=root --password=secret
 *   php tools/schema-generator.php --config=database.json
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PDO;
use PDOException;

class SchemaGenerator
{
    private PDO $pdo;
    private string $outputDir;
    private string $namespace;
    private bool $verbose;

    public function __construct(PDO $pdo, string $outputDir = 'src/Schema', string $namespace = 'App\\Schema', bool $verbose = false)
    {
        $this->pdo = $pdo;
        $this->outputDir = rtrim($outputDir, '/');
        $this->namespace = rtrim($namespace, '\\');
        $this->verbose = $verbose;
    }

    /**
     * Generate all schema classes
     */
    public function generate(): void
    {
        $this->log("🚀 Starting schema generation...");

        // Create output directory
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
            $this->log("📁 Created directory: {$this->outputDir}");
        }

        // Generate tables
        $tables = $this->getTables();
        $this->log("📊 Found " . count($tables) . " tables");

        foreach ($tables as $tableName) {
            $this->generateTableClass($tableName);
        }

        // Generate views
        $views = $this->getViews();
        $this->log("👁️  Found " . count($views) . " views");

        foreach ($views as $viewName) {
            $this->generateViewClass($viewName);
        }

        $this->log("✅ Schema generation completed!");
        $this->log("📍 Files saved to: {$this->outputDir}");
    }

    /**
     * Get all tables from database
     */
    private function getTables(): array
    {
        $sql = "SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY TABLE_NAME";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get all views from database
     */
    private function getViews(): array
    {
        $sql = "SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_TYPE = 'VIEW'
                ORDER BY TABLE_NAME";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get columns for a specific table
     */
    private function getTableColumns(string $tableName): array
    {
        $sql = "SELECT 
                    COLUMN_NAME,
                    DATA_TYPE,
                    IS_NULLABLE,
                    COLUMN_DEFAULT,
                    CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION,
                    NUMERIC_SCALE,
                    EXTRA,
                    COLUMN_KEY
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                ORDER BY ORDINAL_POSITION";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$tableName]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate a table class
     */
    private function generateTableClass(string $tableName): void
    {
        $className = $this->tableNameToClassName($tableName);
        $columns = $this->getTableColumns($tableName);

        $this->log("🔨 Generating {$className} from table '{$tableName}'");

        $classContent = $this->generateTableClassContent($className, $tableName, $columns);
        $filename = "{$this->outputDir}/{$className}.php";

        file_put_contents($filename, $classContent);
        $this->log("  ✅ Saved: {$filename}");
    }

    /**
     * Generate a view class
     */
    private function generateViewClass(string $viewName): void
    {
        $className = $this->viewNameToClassName($viewName);
        $columns = $this->getTableColumns($viewName); // Views use same column query

        $this->log("🔨 Generating {$className} from view '{$viewName}'");

        $classContent = $this->generateViewClassContent($className, $viewName, $columns);
        $filename = "{$this->outputDir}/{$className}.php";

        file_put_contents($filename, $classContent);
        $this->log("  ✅ Saved: {$filename}");
    }

    /**
     * Generate table class content
     */
    private function generateTableClassContent(string $className, string $tableName, array $columns): string
    {
        $uses = $this->generateUseStatements($columns);
        $properties = $this->generateProperties($columns);
        $constructor = $this->generateConstructor($tableName, $columns);
        $getColumns = $this->generateGetColumnsMethod($columns);

        return "<?php

declare(strict_types=1);

namespace {$this->namespace};

use DrizzlePHP\\Schema\\Table;
{$uses}

/**
 * {$className} - Auto-generated from table '{$tableName}'
 * 
 * @generated by DrizzlePHP Schema Generator
 * @table {$tableName}
 */
class {$className} extends Table
{
{$properties}

    public function __construct()
    {
        parent::__construct('{$tableName}');
{$constructor}
    }

    public function getColumns(): array
    {
        return [
{$getColumns}
        ];
    }
}
";
    }

    /**
     * Generate view class content
     */
    private function generateViewClassContent(string $className, string $viewName, array $columns): string
    {
        $uses = $this->generateUseStatements($columns);
        $properties = $this->generateProperties($columns);
        $constructor = $this->generateConstructor($viewName, $columns);
        $getColumns = $this->generateGetColumnsMethod($columns);

        return "<?php

declare(strict_types=1);

namespace {$this->namespace};

use DrizzlePHP\\Schema\\View;
{$uses}

/**
 * {$className} - Auto-generated from view '{$viewName}'
 * 
 * @generated by DrizzlePHP Schema Generator
 * @view {$viewName}
 */
class {$className} extends View
{
{$properties}

    public function __construct()
    {
        parent::__construct('{$viewName}');
{$constructor}
    }

    public function getColumns(): array
    {
        return [
{$getColumns}
        ];
    }
}
";
    }

    /**
     * Generate use statements
     */
    private function generateUseStatements(array $columns): string
    {
        $types = [];
        foreach ($columns as $column) {
            $columnType = $this->mapColumnType($column);
            $types[$columnType] = true;
        }

        $uses = [];
        foreach (array_keys($types) as $type) {
            $uses[] = "use DrizzlePHP\\Column\\{$type};";
        }

        return implode("\n", $uses);
    }

    /**
     * Generate property declarations
     */
    private function generateProperties(array $columns): string
    {
        $properties = [];
        foreach ($columns as $column) {
            $propertyName = $this->columnNameToProperty($column['COLUMN_NAME']);
            $columnType = $this->mapColumnType($column);
            $properties[] = "    public readonly {$columnType} \${$propertyName};";
        }

        return implode("\n", $properties);
    }

    /**
     * Generate constructor body
     */
    private function generateConstructor(string $tableName, array $columns): string
    {
        $lines = [];
        foreach ($columns as $column) {
            $propertyName = $this->columnNameToProperty($column['COLUMN_NAME']);
            $columnType = $this->mapColumnType($column);
            $columnName = $column['COLUMN_NAME'];
            
            $params = $this->generateColumnParameters($column);
            $lines[] = "        \$this->{$propertyName} = new {$columnType}('{$columnName}', \$this->tableName{$params});";
        }

        return implode("\n", $lines);
    }

    /**
     * Generate getColumns method body
     */
    private function generateGetColumnsMethod(array $columns): string
    {
        $lines = [];
        foreach ($columns as $column) {
            $propertyName = $this->columnNameToProperty($column['COLUMN_NAME']);
            $columnName = $column['COLUMN_NAME'];
            $lines[] = "            '{$columnName}' => \$this->{$propertyName},";
        }

        return implode("\n", $lines);
    }

    /**
     * Map database column type to DrizzlePHP column type
     */
    private function mapColumnType(array $column): string
    {
        $dataType = strtolower($column['DATA_TYPE']);

        return match ($dataType) {
            'int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint' => 'IntColumn',
            'varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext' => 'StringColumn',
            'boolean', 'bool', 'bit' => 'BoolColumn',
            'datetime', 'timestamp', 'date', 'time' => 'DateTimeColumn',
            'decimal', 'numeric', 'float', 'double' => 'FloatColumn',
            'json' => 'JsonColumn',
            default => 'StringColumn' // Fallback
        };
    }

    /**
     * Generate column-specific parameters
     */
    private function generateColumnParameters(array $column): string
    {
        $params = [];

        // Auto increment
        if (strpos($column['EXTRA'], 'auto_increment') !== false) {
            $params[] = 'autoIncrement: true';
        }

        // Max length for string columns
        if ($column['CHARACTER_MAXIMUM_LENGTH'] && $this->mapColumnType($column) === 'StringColumn') {
            $params[] = "maxLength: {$column['CHARACTER_MAXIMUM_LENGTH']}";
        }

        return $params ? ', ' . implode(', ', $params) : '';
    }

    /**
     * Convert table name to class name
     */
    private function tableNameToClassName(string $tableName): string
    {
        return $this->toPascalCase($tableName) . 'Table';
    }

    /**
     * Convert view name to class name
     */
    private function viewNameToClassName(string $viewName): string
    {
        return $this->toPascalCase($viewName) . 'View';
    }

    /**
     * Convert column name to property name
     */
    private function columnNameToProperty(string $columnName): string
    {
        return $this->toCamelCase($columnName);
    }

    /**
     * Convert snake_case to PascalCase
     */
    private function toPascalCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * Convert snake_case to camelCase
     */
    private function toCamelCase(string $string): string
    {
        return lcfirst($this->toPascalCase($string));
    }

    /**
     * Log message if verbose mode is enabled
     */
    private function log(string $message): void
    {
        if ($this->verbose) {
            echo $message . "\n";
        }
    }
}

// Additional column types that might be needed
if (!class_exists('DrizzlePHP\\Column\\FloatColumn')) {
    echo "Note: FloatColumn and JsonColumn classes need to be created in your Column namespace.\n";
}

/**
 * Command line interface
 */
function main(): void
{
    $options = getopt('', [
        'host:',
        'database:',
        'user:',
        'password:',
        'dsn:',
        'output:',
        'namespace:',
        'config:',
        'verbose',
        'help'
    ]);

    if (isset($options['help'])) {
        showHelp();
        exit(0);
    }

    try {
        // Load configuration
        if (isset($options['config'])) {
            $config = loadConfig($options['config']);
            $options = array_merge($config, $options);
        }

        // Create PDO connection
        $pdo = createConnection($options);

        // Set up generator
        $outputDir = $options['output'] ?? 'src/Schema';
        $namespace = $options['namespace'] ?? 'App\\Schema';
        $verbose = isset($options['verbose']);

        $generator = new SchemaGenerator($pdo, $outputDir, $namespace, $verbose);
        $generator->generate();

    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function createConnection(array $options): PDO
{
    if (isset($options['dsn'])) {
        $dsn = $options['dsn'];
    } else {
        $host = $options['host'] ?? 'localhost';
        $database = $options['database'] ?? throw new InvalidArgumentException('Database name is required');
        $dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";
    }

    $user = $options['user'] ?? throw new InvalidArgumentException('Username is required');
    $password = $options['password'] ?? '';

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        echo "✅ Connected to database successfully\n";
        return $pdo;

    } catch (PDOException $e) {
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

function loadConfig(string $configFile): array
{
    if (!file_exists($configFile)) {
        throw new InvalidArgumentException("Config file not found: {$configFile}");
    }

    $config = json_decode(file_get_contents($configFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new InvalidArgumentException("Invalid JSON in config file: " . json_last_error_msg());
    }

    return $config;
}

function showHelp(): void
{
    echo <<<HELP
DrizzlePHP Schema Generator

USAGE:
    php tools/schema-generator.php [OPTIONS]

OPTIONS:
    --host=HOST              Database host (default: localhost)
    --database=DATABASE      Database name (required)
    --user=USER             Database username (required)
    --password=PASSWORD     Database password
    --dsn=DSN               Full DSN string (alternative to host/database)
    --output=DIR            Output directory (default: src/Schema)
    --namespace=NAMESPACE   PHP namespace (default: App\\Schema)
    --config=FILE           JSON config file
    --verbose               Enable verbose output
    --help                  Show this help

EXAMPLES:
    # Basic usage
    php tools/schema-generator.php --database=myapp --user=root --password=secret

    # Custom output directory and namespace
    php tools/schema-generator.php --database=myapp --user=root --output=app/Models --namespace=App\\Models

    # Using DSN
    php tools/schema-generator.php --dsn="mysql:host=localhost;dbname=myapp" --user=root --password=secret

    # Using config file
    php tools/schema-generator.php --config=database.json --verbose

CONFIG FILE FORMAT (database.json):
    {
        "host": "localhost",
        "database": "myapp",
        "user": "root",
        "password": "secret",
        "output": "src/Schema",
        "namespace": "App\\\\Schema"
    }

HELP;
}

// Run the CLI
if (php_sapi_name() === 'cli') {
    main();
} else {
    echo "This script must be run from the command line.\n";
    exit(1);
}
