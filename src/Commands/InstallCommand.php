<?php

namespace Project\RestServer\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'project-rest-server:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Project REST Server package';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Foundation\Composer
     */
    protected $composer;

    /**
     * Seed Folder name.
     *
     * @var string
     */
    protected $seedFolder;

    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
        $this->composer->setWorkingPath(base_path());

        //$this->seedFolder = Seed::getFolderName();
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production', null],
        ];
    }

    public function fire(Filesystem $filesystem)
    {
        return $this->handle($filesystem);
    }

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     *
     * @return void
     */
    public function handle(Filesystem $filesystem)
    {
        $this->info('Publishing the Project REST database');

        $this->info('Migrating the database tables into your application');
        $this->call('migrate', ['--force' => $this->option('force'), '--path' => dirname(str_replace(base_path(), '', __DIR__), 2) . '/migrations/2024_09_18_547_install.php']);
        //$this->call('migrate', ['--force' => $this->option('force')]);

        $this->info('Successfully installed Project REST Serve! Enjoy');
    }
}
