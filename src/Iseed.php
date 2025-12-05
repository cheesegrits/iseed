<?php

namespace Cheesegrits\Iseed;

use Cheesegrits\Iseed\Exceptions\TableNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class Iseed
{
    /**
     * Name of the database upon which the seed will be executed.
     *
     * @var string
     */
    protected $databaseName;

    /**
     * New line character for seed files.
     * Double quotes are mandatory!
     *
     * @var string
     */
    private $newLineCharacter = PHP_EOL;

    /**
     * Desired indent for the code.
     * For tabulator use \t
     * Double quotes are mandatory!
     *
     * @var string
     */
    private $indentCharacter = "    ";

    /**
     * Composer variable
     *
     * @var object
     */
    private $composer;

    private $files;

    /**
     * Constructs a new instance.
     *
     * @param \Illuminate\Filesystem\Filesystem|bool $filesystem Filesystem
     * @param \Illuminate\Support\Composer|bool $composer Composer
     */
    public function __construct(Filesystem $filesystem = null, Composer $composer = null)
    {
        $this->files = $filesystem ?: new Filesystem;
        $this->composer = $composer ?: new Composer($this->files);
    }

    /**
     * Reads a stub file.
     *
     * @param string $file Path to stub file
     *
     * @return object
     */
    public function readStubFile($file)
    {
        $buffer = file($file, FILE_IGNORE_NEW_LINES);
        return implode(PHP_EOL, $buffer);
    }

    /**
     * Generates a seed file.
     *
     * @param string $table Table name
     * @param string $prefix Seeder class prefix
     * @param string $suffix Seeder class suffix
     * @param string $database Database connection name
     * @param int $max Maximum seeded entries
     * @param int $chunkSize Size of data chunks
     * @param string $exclude Columns to exclude
     * @param string $prerunEvent Prerun event name
     * @param string $postrunEvent Postrun event name
     * @param bool $dumpAuto Composer auto-dump
     * @param bool $indexed Indexed array
     * @param int $orderBy Column to order by
     * @param string $direction Default sort order
     *
     * @return void
     * @throws Cheesegrits\Iseed\TableNotFoundException
     *
     */
    public function generateSeed(
        $table,
        $prefix = null,
        $suffix = null,
        $database = null,
        $max = 0,
        $chunkSize = 0,
        $exclude = null,
        $prerunEvent = null,
        $postrunEvent = null,
        $dumpAuto = true,
        $indexed = true,
        $orderBy = null,
        $direction = 'ASC'
    )
    {
        if (!$database) {
            $database = config('database.default');
        }

        $this->databaseName = $database;

        // Check if table exists
        if (!$this->hasTable($table)) {
            throw new TableNotFoundException("Table $table was not found.");
        }

        // Get the data
        $data = $this->getData($table, $max, $exclude, $orderBy, $direction);

        // Repack the data
        $dataArray = $this->repackSeedData($data);

        // Generate class name
        $className = $this->generateClassName($table, $prefix, $suffix);

        // Get template for a seed file contents
        $stub = $this->readStubFile(
            \File::exists(
                config('iseed.stub_path')
            ) ?
                config('iseed.stub_path') :
                $this->getStubPath() . '/seed.stub'
        );

        // Get a seed folder path
        $seedPath = $this->getSeedPath();

        // Get a app/database/seeds path
        $seedsPath = $this->getPath($className, $seedPath);

        // Get a populated stub file
        $seedContent = $this->populateStub(
            $className,
            $stub,
            $table,
            $dataArray,
            $chunkSize,
            $prerunEvent,
            $postrunEvent,
            $indexed
        );

        // Save a populated stub
        $this->files->put($seedsPath, $seedContent);

        // Run composer dump-auto
        if ($dumpAuto) {
            $this->composer->dumpAutoloads();
        }

        // Update the DatabaseSeeder.php file
        return $this->updateDatabaseSeederRunMethod($className) !== false;
    }

    /**
     * Get a seed folder path.
     *
     * @return string
     */
    public function getSeedPath()
    {
        return base_path() . config('iseed.path');
    }

    /**
     * Get the Data
     *
     * @param string $table Table name
     * @param int $max Maximum seeded entries
     * @param string $exclude Columns to exclude
     * @param int $orderBy Column to order by
     * @param string $direction Default sort order
     *
     * @return array
     */
    public function getData($table, $max, $exclude = null, $orderBy = null, $direction = 'ASC')
    {
        $result = \DB::connection($this->databaseName)->table($table);

        if (!empty($exclude)) {
            $allColumns = \DB::connection($this->databaseName)
                ->getSchemaBuilder()
                ->getColumnListing($table);
            $result = $result->select(array_diff($allColumns, $exclude));
        }

        if ($orderBy) {
            $result = $result->orderBy($orderBy, $direction);
        }

        if ($max) {
            $result = $result->limit($max);
        }

        return $result->get();
    }

    /**
     * Repacks data read from the database.
     *
     * @param array|object $data Data from DB
     *
     * @return array
     */
    public function repackSeedData($data)
    {
        if (!is_array($data)) {
            $data = $data->toArray();
        }
        $dataArray = array();
        if (!empty($data)) {
            foreach ($data as $row) {
                $rowArray = array();
                foreach ($row as $columnName => $columnValue) {
                    $rowArray[$columnName] = $columnValue;
                }
                $dataArray[] = $rowArray;
            }
        }
        return $dataArray;
    }

    /**
     * Checks if a database table exists.
     *
     * @param string $table Table name
     *
     * @return bool
     */
    public function hasTable($table)
    {
        return \Schema::connection($this->databaseName)->hasTable($table);
    }

    /**
     * Generates a seed class name (also used as a filename)
     *
     * @param string $table Table name
     * @param string $prefix Seeder class prefix
     * @param string $suffix Seeder class suffix
     *
     * @return string
     */
    public function generateClassName($table, $prefix = null, $suffix = null)
    {
        $tableString = '';
        $tableName = explode('_', $table);
        foreach ($tableName as $tableNameExploded) {
            $tableString .= ucfirst($tableNameExploded);
        }
        return ($prefix ? $prefix : '') . ucfirst($tableString) . 'Table' . ($suffix ? $suffix : '') . 'Seeder';
    }

    /**
     * Get the path to the stub file.
     *
     * @return string
     */
    public function getStubPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Stubs';
    }

    /**
     * Populate the place-holders in the seed stub.
     *
     * @param string $class Class name
     * @param string $stub Stub name
     * @param string $table Table name
     * @param string $data Data object
     * @param int $chunkSize Size of data chunks
     * @param string $prerunEvent Prerun event name
     * @param string $postrunEvent Postrun event name
     * @param bool $indexed Indexed array
     *
     * @return string
     */
    public function populateStub(
        $class,
        $stub,
        $table,
        $data,
        $chunkSize = null,
        $prerunEvent = null,
        $postrunEvent = null,
        $indexed = true
    )
    {
        $chunkSize = $chunkSize ?: config('iseed.chunk_size');

        $inserts = '';
        $chunks = array_chunk($data, $chunkSize);
        foreach ($chunks as $chunk) {
            $this->addNewLines($inserts);
            $this->addIndent($inserts, 2);
            $inserts .= sprintf(
                config('iseed.insert_command', "\DB::table('%s')->insert(%s);"),
                $table,
                $this->prettifyArray($chunk, $indexed)
            );
        }

        $namespace = implode('\\', array_filter(array_map('ucfirst', explode('/', config('iseed.path')))));
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $class, $stub);

        $prerunEventInsert = '';
        if ($prerunEvent) {
            $prerunEventInsert .= "\$response = Event::until(new $prerunEvent());";
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 2);
            $prerunEventInsert .= 'if ($response === false) {';
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 3);
            $prerunEventInsert .= 'throw new Exception("Prerun event failed, seed wasn\'t executed!");';
            $this->addNewLines($prerunEventInsert);
            $this->addIndent($prerunEventInsert, 2);
            $prerunEventInsert .= '}';
        }

        $stub = str_replace(
            '{{ prerun_event }}', $prerunEventInsert, $stub
        );

        if (!is_null($table)) {
            $stub = str_replace('{{ table }}', $table, $stub);
        }

        $postrunEventInsert = '';
        if ($postrunEvent) {
            $postrunEventInsert .= "\$response = Event::until(new $postrunEvent());";
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 2);
            $postrunEventInsert .= 'if ($response === false) {';
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 3);
            $postrunEventInsert .= 'throw new Exception("Seed was executed but the postrun event failed!");';
            $this->addNewLines($postrunEventInsert);
            $this->addIndent($postrunEventInsert, 2);
            $postrunEventInsert .= '}';
        }

        $stub = str_replace(
            '{{ postrun_event }}', $postrunEventInsert, $stub
        );

        $stub = str_replace('{{ insert_statements }}', $inserts, $stub);

        return $stub;
    }

    /**
     * Create the full path name to the seed file.
     *
     * @param string $name Filename
     * @param string $path Full path
     *
     * @return string
     */
    public function getPath($name, $path)
    {
        return $path . '/' . $name . '.php';
    }

    /**
     * Prettify a var_export of an array.
     *
     * @param array $array Exported array
     *
     * @return string
     */
    protected function prettifyArray($array, $indexed = true)
    {
        $content = ($indexed)
            ? var_export($array, true)
            : preg_replace("/[0-9]+ \=\>/i", '', var_export($array, true));

        $lines = explode("\n", $content);

        $inString = false;
        $tabCount = 3;
        for ($i = 1; $i < count($lines); $i++) {
            $lines[$i] = ltrim($lines[$i]);

            //Check for closing bracket
            if (strpos($lines[$i], ')') !== false) {
                $tabCount--;
            }

            //Insert tab count
            if ($inString === false) {
                for ($j = 0; $j < $tabCount; $j++) {
                    $lines[$i] = substr_replace(
                        $lines[$i],
                        $this->indentCharacter, 0, 0
                    );
                }
            }

            for ($j = 0; $j < strlen($lines[$i]); $j++) {
                //skip character right after an escape \
                if ($lines[$i][$j] == '\\') {
                    $j++;
                } else if ($lines[$i][$j] == '\'') {
                    $inString = !$inString;
                }
            }

            //check for openning bracket
            if (strpos($lines[$i], '(') !== false) {
                $tabCount++;
            }
        }

        $content = implode("\n", $lines);

        return $content;
    }

    /**
     * Adds new lines to the passed content variable reference.
     *
     * @param string $content Passed content
     * @param int $numberOfLines Number of new lines
     *
     * @return void
     */
    private function addNewLines(&$content, $numberOfLines = 1)
    {
        while ($numberOfLines > 0) {
            $content .= $this->newLineCharacter;
            $numberOfLines--;
        }
    }

    /**
     * Adds indentation to the passed content reference.
     *
     * @param string $content Passed content
     * @param int $numberOfIndents Number of indents
     *
     * @return void
     */
    private function addIndent(&$content, $numberOfIndents = 1)
    {
        while ($numberOfIndents > 0) {
            $content .= $this->indentCharacter;
            $numberOfIndents--;
        }
    }

    /**
     * Cleans the Iseed section.
     *
     * @return bool
     */
    public function cleanSection()
    {
        $path = (config('iseed.seeder_path') ? config('iseed.seeder_path') : config('iseed.path') . '/DatabaseSeeder.php');
        $databaseSeederPath = base_path() . $path;

        $content = $this->files->get($databaseSeederPath);

        $content = preg_replace("/(\#iseed_start.+?)\#iseed_end/us", "#iseed_start\n\t\t#iseed_end", $content);

        return $this->files->put($databaseSeederPath, $content) !== false;
        return false;
    }

    /**
     * Updates the DatabaseSeeder file's run method.
     *
     * @param string $className Database seeder class name
     *
     * @return bool
     * @link https://github.com/JeffreyWay/Laravel-4-Generators Kudoz to this package
     *
     */
    public function updateDatabaseSeederRunMethod($className)
    {
        if (config('iseed.seeder_modification') === false) {
            return true;
        }

        $path = (config('iseed.seeder_path')
            ? config('iseed.seeder_path')
            : config('iseed.path') . '/DatabaseSeeder.php');
        $databaseSeederPath = base_path() . $path;

        $content = $this->files->get($databaseSeederPath);
        if (strpos($content, "\$this->call({$className}::class)") === false) {
            if (strpos($content, '#iseed_start')
                && strpos($content, '#iseed_end')
                && strpos($content, '#iseed_start') < strpos($content, '#iseed_end')
            ) {
                $content = preg_replace(
                    "/(\#iseed_start.+?)(\#iseed_end)/us",
                    "$1\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}{$this->indentCharacter}$2",
                    $content
                );
            } else {
                $content = preg_replace(
                    "/(run\(\).+?)}/us",
                    "$1{$this->indentCharacter}\$this->call({$className}::class);{$this->newLineCharacter}{$this->indentCharacter}}",
                    $content
                );
            }
        }

        return $this->files->put($databaseSeederPath, $content) !== false;
    }
}
