<?php

namespace App\Listeners;

use App\Events\GroceryListChanged;
use App\Events\GroceryListChangedEvent;
use App\Services\ListSharedService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateGroceryListListener
{
    private $list_service;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ListSharedService $list_service)
    {
        $this->list_service = $list_service;
    }

    /**
     * Handle the event.
     *
     * @param  GroceryListChanged  $event
     * @return void
     */
    public function handle(GroceryListChangedEvent $event)
    {
        $this->list_service->update_list($event->list);
    }
}
