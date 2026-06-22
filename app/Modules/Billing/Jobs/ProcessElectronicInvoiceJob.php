<?php

namespace App\Modules\Billing\Jobs;

use App\Modules\Billing\Services\ElectronicInvoicingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessElectronicInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected object $model
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ElectronicInvoicingService $service): void
    {
        try {
            $service->process($this->model);
        } catch (Exception $e) {
            Log::error("Failed to process electronic invoice asynchronously: " . $e->getMessage(), [
                'model_class' => get_class($this->model),
                'model_id'    => $this->model->id
            ]);
            throw $e;
        }
    }
}
