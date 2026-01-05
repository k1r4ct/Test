<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use App\Services\SystemLogService;

class JobEventsListener
{
    /**
     * Whether to log job processing start (can be noisy).
     * 
     * @var bool
     */
    protected bool $logJobStart;

    /**
     * Whether to log successful job completions.
     * 
     * @var bool
     */
    protected bool $logJobSuccess;

    /**
     * Create a new listener instance.
     */
    public function __construct()
    {
        // Can be configured via .env
        $this->logJobStart = env('LOG_JOB_START', false);
        $this->logJobSuccess = env('LOG_JOB_SUCCESS', true);
    }

    /**
     * Handle job processing event (before job runs).
     */
    public function handleJobProcessing(JobProcessing $event): void
    {
        if (!$this->logJobStart) {
            return;
        }

        $job = $event->job;

        SystemLogService::scheduler()->debug('Job processing started', [
            'job_name' => $job->resolveName(),
            'job_id' => $job->getJobId(),
            'queue' => $job->getQueue(),
            'connection' => $event->connectionName,
            'attempts' => $job->attempts(),
        ]);
    }

    /**
     * Handle job processed event (after job completes successfully).
     */
    public function handleJobProcessed(JobProcessed $event): void
    {
        if (!$this->logJobSuccess) {
            return;
        }

        $job = $event->job;

        SystemLogService::scheduler()->info('Job completed successfully', [
            'job_name' => $job->resolveName(),
            'job_id' => $job->getJobId(),
            'queue' => $job->getQueue(),
            'connection' => $event->connectionName,
            'attempts' => $job->attempts(),
        ]);
    }

    /**
     * Handle job failed event.
     */
    public function handleJobFailed(JobFailed $event): void
    {
        $job = $event->job;
        $exception = $event->exception;

        SystemLogService::scheduler()->error('Job failed', [
            'job_name' => $job->resolveName(),
            'job_id' => $job->getJobId(),
            'queue' => $job->getQueue(),
            'connection' => $event->connectionName,
            'attempts' => $job->attempts(),
            'max_tries' => $job->maxTries(),
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
        ], $exception);
    }

    /**
     * Handle job exception occurred (job threw exception but may retry).
     */
    public function handleJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        $job = $event->job;
        $exception = $event->exception;

        SystemLogService::scheduler()->warning('Job exception occurred (may retry)', [
            'job_name' => $job->resolveName(),
            'job_id' => $job->getJobId(),
            'queue' => $job->getQueue(),
            'connection' => $event->connectionName,
            'attempts' => $job->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Handle job queued event.
     */
    public function handleJobQueued(JobQueued $event): void
    {
        // Optional: Log when jobs are queued
        // Uncomment if needed, but can be noisy
        /*
        SystemLogService::scheduler()->debug('Job queued', [
            'job_class' => get_class($event->job),
            'job_id' => $event->id,
            'connection' => $event->connectionName,
        ]);
        */
    }

    /**
     * Handle scheduled task starting.
     */
    public function handleScheduledTaskStarting(ScheduledTaskStarting $event): void
    {
        if (!$this->logJobStart) {
            return;
        }

        $task = $event->task;

        SystemLogService::scheduler()->debug('Scheduled task starting', [
            'command' => $task->command ?? $task->description ?? 'Unknown',
            'expression' => $task->expression,
            'timezone' => $task->timezone,
        ]);
    }

    /**
     * Handle scheduled task finished.
     */
    public function handleScheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        if (!$this->logJobSuccess) {
            return;
        }

        $task = $event->task;

        SystemLogService::scheduler()->info('Scheduled task completed', [
            'command' => $task->command ?? $task->description ?? 'Unknown',
            'expression' => $task->expression,
            'runtime_seconds' => $task->runtime ?? null,
        ]);
    }

    /**
     * Handle scheduled task failed.
     */
    public function handleScheduledTaskFailed(ScheduledTaskFailed $event): void
    {
        $task = $event->task;
        $exception = $event->exception ?? null;

        $context = [
            'command' => $task->command ?? $task->description ?? 'Unknown',
            'expression' => $task->expression,
        ];

        if ($exception) {
            $context['error'] = $exception->getMessage();
            $context['exception_class'] = get_class($exception);
        }

        SystemLogService::scheduler()->error('Scheduled task failed', $context, $exception);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     * @return array<string, string>
     */
    public function subscribe($events): array
    {
        return [
            JobProcessing::class => 'handleJobProcessing',
            JobProcessed::class => 'handleJobProcessed',
            JobFailed::class => 'handleJobFailed',
            JobExceptionOccurred::class => 'handleJobExceptionOccurred',
            JobQueued::class => 'handleJobQueued',
            ScheduledTaskStarting::class => 'handleScheduledTaskStarting',
            ScheduledTaskFinished::class => 'handleScheduledTaskFinished',
            ScheduledTaskFailed::class => 'handleScheduledTaskFailed',
        ];
    }
}
