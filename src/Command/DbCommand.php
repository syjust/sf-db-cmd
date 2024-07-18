<?php
namespace Syjust\SfDbCmd\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Class DbCommand
 * Something like the laravel DbCommand
 *
 * @package App\Command
 */
#[AsCommand(name: 'db', description: 'Start a new database CLI session')]
class DbCommand extends Command
{
    private SymfonyStyle $io;
    private EntityManagerInterface $em;
    private ManagerRegistry $manager;

    #[Required]
    public function setEm(EntityManagerInterface $em): void
    {
        $this->em = $em;
    }

    /**
     * Get the arguments for the database client command.
     *
     * @param array $connection
     *
     * @return array
     */
    public function commandArguments(array $connection): array
    {
        $driver = ucfirst(str_replace('pdo_', '', $connection['driver']));

        return $this->{"get{$driver}Arguments"}($connection);
    }

    /**
     * Get the environment variables for the database client command.
     *
     * @param array $connection
     *
     * @return array|null
     */
    public function commandEnvironment(array $connection): ?array
    {
        $driver = ucfirst($connection['driver']);

        if (method_exists($this, "get{$driver}Environment")) {
            return $this->{"get{$driver}Environment"}($connection);
        }

        return null;
    }

    /**
     * No connection argument nor read/write options here
     *
     * @return void
     */
    public function configure(): void
    {
//        $this
//            ->addArgument('connection', InputArgument::OPTIONAL, 'The database connection that should be used')
//            ->addOption( 'read', 'r', InputOption::VALUE_NONE, 'Connect to the read connection' )
//            ->addOption('write', 'w', InputOption::VALUE_NONE, 'Connect to the write connection')
//        ;
    }

    /**
     * Execute the console command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $connection = $this->getConnection();

        if (!isset($connection['host']) && $connection['driver'] !== 'sqlite') {
            $this->io->error('No host specified for this database connection.');
//            $this->io->note('  Use the <options=bold>[--read]</> and <options=bold>[--write]</> options to specify a read or write connection.');
            $this->io->newLine();

            return Command::FAILURE;
        }

        (new Process(
            array_merge([$this->getCommand($connection)], $this->commandArguments($connection)), null, $this->commandEnvironment($connection)
        ))->setTimeout(null)->setTty(true)->mustRun(function ($type, $buffer) {
                $this->io->write($buffer);
            })
        ;

        return 0;
    }

    /**
     * Get the database client command to run.
     *
     * @param array $connection
     *
     * @return string
     */
    public function getCommand(array $connection): string
    {
        return [
            'mysql'  => 'mysql',
            'pdo_pgsql'  => 'psql',
            'pdo_sqlite' => 'sqlite3',
            'sqlsrv' => 'sqlcmd',
        ][$connection['driver']];
    }

    /**
     * Get the database connection configuration.
     *
     * @return array
     */
    public function getConnection(): array
    {
        $connection = $this->em->getConnection()->getParams();
        if (isset($connection['path']) && !isset($connection['dbname'])) {
            $connection['dbname'] = $connection['path'];
        }

        return $connection;
    }

    /**
     * Get the arguments for the MySQL CLI.
     *
     * @param array $connection
     *
     * @return array
     */
    protected function getMysqlArguments(array $connection): array
    {
        return array_merge([
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--user=' . $connection['username'],
        ],
            $this->getOptionalArguments([
                'password'    => '--password=' . $connection['password'],
                'unix_socket' => '--socket=' . ($connection['unix_socket'] ?? ''),
                'charset'     => '--default-character-set=' . ($connection['charset'] ?? ''),
            ], $connection),
            [$connection['dbname']]);
    }

    /**
     * Get the optional arguments based on the connection configuration.
     *
     * @param array $args
     * @param array $connection
     *
     * @return array
     */
    protected function getOptionalArguments(array $args, array $connection): array
    {
        return array_values(array_filter($args, function ($key) use ($connection) {
            return !empty($connection[$key]);
        }, ARRAY_FILTER_USE_KEY));
    }

    /**
     * Get the arguments for the Postgres CLI.
     *
     * @param array $connection
     *
     * @return array
     */
    protected function getPgsqlArguments(array $connection): array
    {
        return [$connection['dbname']];
    }

    /**
     * Get the environment variables for the Postgres CLI.
     *
     * @param array $connection
     *
     * @return array|null
     */
    protected function getPgsqlEnvironment(array $connection): ?array
    {
        return array_merge(...$this->getOptionalArguments([
            'username' => ['PGUSER' => $connection['username']],
            'host'     => ['PGHOST' => $connection['host']],
            'port'     => ['PGPORT' => $connection['port']],
            'password' => ['PGPASSWORD' => $connection['password']],
        ], $connection));
    }

    /**
     * Get the arguments for the SQLite CLI.
     *
     * @param array $connection
     *
     * @return array
     */
    protected function getSqliteArguments(array $connection): array
    {
        return [$connection['dbname']];
    }

    /**
     * Get the arguments for the SQL Server CLI.
     *
     * @param array $connection
     *
     * @return array
     */
    protected function getSqlsrvArguments(array $connection): array
    {
        return array_merge(...$this->getOptionalArguments([
            'database'                 => ['-d', $connection['dbname']],
            'username'                 => ['-U', $connection['username']],
            'password'                 => ['-P', $connection['password']],
            'host'                     => [
                '-S',
                'tcp:' . $connection['host'] . ($connection['port'] ? ',' . $connection['port'] : ''),
            ],
            'trust_server_certificate' => ['-C'],
        ], $connection));
    }
}
