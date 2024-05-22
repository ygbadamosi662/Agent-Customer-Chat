<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\BulkAirtime;
use App\BulkAirtimeTransaction;
use App\Jobs\ExecuteBulkAirtimeTransactionJob;
use App\Transaction;
use Carbon\Carbon;

class SendBulkAirtime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulkairtime:execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create bulk airtime transactions and execute them';
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
        $bulk_airtimes = $this->getBulkAirtimes();
        $this->info('Found '.$bulk_airtimes->count().' bulk airtime(s) to process');
        

        foreach ($bulk_airtimes as $row) {
            $row->update(['notes' => null]);
            
            if ($row->merchant->other_wallet < 50) {
                $row->update(['notes' => 'Insufficient Wallet Balance: '.$row->merchant->other_wallet ]);
                $this->info('['.$row->description.'] Merchant has zero balance');
                continue;
            }

            if ($this->shouldRun($row)) {
                $this->populateTransactions($row);
            }

            $this->info('['.$row->description.'] Processed successfully');
        }

        BulkAirtime::where('id', '>', 0)->update(['last_run_at' => date(self::NOW)]);
        return 0;
    }

    private function getBulkAirtimes()
    {
        $now = now()->format(self::NOW);
        return BulkAirtime::whereStatus('active')
        ->with('merchant')
        ->where('start_at', '<=', $now)
        ->where(function ($query) use ($now) {
            $query->where('end_at', '>', $now)
            ->orWhere('end_at', null);
        })
        ->get();
    }

    private function shouldRun(BulkAirtime $bulk_airtime)
    {
        $frequency = $bulk_airtime->frequency;
        $last_run_at = $bulk_airtime->last_run_at;
        $frequency_count = $bulk_airtime->frequency_count;

        if ($last_run_at == null) {
            return true;
        }

        $daily = $frequency == 'daily' && now()->diffInDays($last_run_at) >= $frequency_count;
        $weekly = $frequency == 'weekly' && Carbon::now()->startOfWeek() && now()->diffInWeeks($last_run_at) >= $frequency_count;
        $monthly = $frequency == 'monthly' && Carbon::now()->startOfMonth() && now()->diffInMonths($last_run_at) >= $frequency_count;

        if ($daily) {
            $bulk_airtime->update(['notes' => 'Qualifies for daily execution']);
            return true;
        }

        if ($weekly) {
            $bulk_airtime->update(['notes' => 'Qualifies for weekly execution']);
            return true;
        }

        if ($monthly) {
            $bulk_airtime->update(['notes' => 'Qualifies for monthly execution']);
            return true;
        }

        $bulk_airtime->update(['notes' => 'Completed']);
        return false;
    }

    private function populateTransactions(BulkAirtime $bulk_airtime)
    {
        $other_wallet = $bulk_airtime->merchant->other_wallet;

        foreach ($bulk_airtime->contacts as $row) {

            $cost_to_merchant = $bulk_airtime->amount - $bulk_airtime->discount;
            if ($other_wallet < $cost_to_merchant) {
                $notes = 'Insufficient balance while trying to create airtime transactions on '.date(self::NOW);
                $bulk_airtime->update(['notes' => $notes]);
                continue;
            }

            $transaction = BulkAirtimeTransaction::create([
                'reference' => $row->msisdn.'-'.date('ymdhis'),
                'bulk_airtime_id' => $bulk_airtime->id,
                'msisdn' => $row->msisdn,
                'telco' => $row->telco,
                'name' => $row->decription,
                'amount' => $bulk_airtime->amount,
                'discount' => $bulk_airtime->discount,
                'cost_to_merchant' => $cost_to_merchant,
                'status' => 'pending'
            ]);
            $transaction->merchant_name = $bulk_airtime->merchant->merchant_name;
            $transaction->other_wallet = $bulk_airtime->merchant->other_wallet;
            ExecuteBulkAirtimeTransactionJob::dispatch($transaction)->delay(now()->addMinutes(1));

            $bulk_airtime->update(['notes' => 'Charged merchant succcessfully']);
        }
    }

    private function chargeMerchantOtherWallet($transaction, $cost_to_merchant, $closing_balance)
    {
        Transaction::create([
            'member_id' => $transaction->bulk_airtime->merchant_id,
            'order_id' => 0,
            'amount' => $cost_to_merchant,
            'closing_balance' => $closing_balance,
            'transaction_type' => 'out',
            'description' => 'Bulk airtime recharge for '.$transaction->msisdn,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $transaction->bulk_airtime->merchant->update(['other_wallet' => $closing_balance]);
    }

    
}
