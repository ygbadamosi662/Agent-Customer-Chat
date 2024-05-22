<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\BulkAirtime;
use App\BulkAirtimeTransaction;
use App\Jobs\ExecuteBulkAirtimeTransactionJob;
use App\Transaction;
use App\Merchant;
use Carbon\Carbon;

class ReprocessBulkAirtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reprocessbulkairtime:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reproces Failed or Pending bulk airtime transactions and execute them';
    public const NOW = 'Y-m-d H:i:s';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->output = new \Symfony\Component\Console\Output\ConsoleOutput();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $bulk_airtimes_transaction = $this->getBulkAirtimesTransaction();
        
        foreach ($bulk_airtimes_transaction as $row) {
            $row->update(['notes' => null]);
            
            if ($row->other_wallet < 50) {
                $row->update(['notes' => 'Insufficient Wallet Balance']);
                continue;
            }

            if ($row->bulk_airtime->status == 'active') {
                $this->populateTransactions($row);
            }

        }

        return 0;

    }


    private function getBulkAirtimesTransaction()
    {
        return BulkAirtimeTransaction::select('bulk_airtime_transactions.*', 'merchant.other_wallet', 'merchant.merchant_name')->where('bulk_airtime_transactions.status', '=','reprocess')->with('bulk_airtime')
        ->leftjoin('bulk_airtimes', 'bulk_airtime_transactions.bulk_airtime_id',  '=', 'bulk_airtimes.id')
        ->leftjoin('merchant', 'bulk_airtimes.merchant_id', '=', 'merchant.id')
        ->get();

    }

 

    private function populateTransactions(BulkAirtimeTransaction $bulk_airtimes_transaction)
    {

        $other_wallet = $bulk_airtimes_transaction->other_wallet;
        $cost_to_merchant = $bulk_airtimes_transaction->bulk_airtime->amount - $bulk_airtimes_transaction->bulk_airtime->discount;
        

        if ($other_wallet > $cost_to_merchant) {
            $bulk_airtimes_transaction->update(['response'=> null]);
            $bulk_airtimes_transaction->cost_to_merchant = $cost_to_merchant;
            ExecuteBulkAirtimeTransactionJob::dispatch($bulk_airtimes_transaction);
        }
        $notes = 'Insufficient balance while trying to create airtime transactions on '.date(self::NOW);
        $bulk_airtimes_transaction->update(['bulk_airtime_transactions.notes' => $notes]);
        
    }

    
}
