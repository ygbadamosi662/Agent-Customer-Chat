<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearOldServiceConversationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old service conversations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::table('service_conversations')
        ->where('updated_at', '<', Carbon::now()->subMinutes(30)->toDateTimeString())
        ->where('status', 'active')
        ->update(['status' => 'inactive']);

        return 0;
    }
}
