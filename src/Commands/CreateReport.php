<?php

namespace Nosok\XeroReport\Commands;

use Illuminate\Console\Command;
use Nosok\XeroReport\Reports\WeeklyReport;

class CreateReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xero:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Xero Report';

    private $report;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(WeeklyReport $report)
    {
        $this->report = $report;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->report->generate();

        if (! empty($this->report->getErrors())) {
            foreach($this->report->getErrors() as $error) {
                echo "{$error}\n";
            }
        }
    }
}
