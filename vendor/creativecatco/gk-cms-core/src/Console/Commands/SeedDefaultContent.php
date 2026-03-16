<?php

namespace CreativeCatCo\GkCmsCore\Console\Commands;

use Illuminate\Console\Command;
use CreativeCatCo\GkCmsCore\Database\Seeders\DefaultContentSeeder;

class SeedDefaultContent extends Command
{
    protected $signature = 'gkeys:seed-content';
    protected $description = 'Seed default pages, menus, and settings for GKeys CMS';

    public function handle(): int
    {
        $this->info('Seeding default GKeys CMS content...');

        $seeder = new DefaultContentSeeder();
        $seeder->run();

        $this->info('Default content seeded successfully!');
        $this->info('Pages: Home, About, Services, Contact, Blog, Portfolio, Products');
        $this->info('Blog Post: 10 Tips to Grow Your Business Online');
        $this->info('Components: Header, Footer');
        $this->info('Menus: Main Navigation, Footer Navigation');

        return Command::SUCCESS;
    }
}
