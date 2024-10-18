<?php

namespace App\Console\Commands;

use App\Repositories\SportRepository;
use App\Services\FootballApiService;
use Illuminate\Console\Command;

class SportApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sport:api {--sport= : The sport parameter} {--method= : The method parameter}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $method = $this->option('method');
        if(!in_array($method, ['leagues', 'teams', 'games'])) {
            return 0;
        }
        $sportRepository = new SportRepository();
        $sport = $sportRepository->createOrGetSportByUniqueName($this->option('sport'));
        if(!$sport) {
            return 0;
        }
        $sportrepository = new SportRepository();
        $service = new FootballApiService($sportrepository, $sport);


        try {
            if($method == 'leagues') {
                $service->getLeagues();
            } else if($method == 'teams') {
                $service->getTeams();
            } else if($method == 'games') {
                $service->getGames();
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

        return 0;
    }
}
