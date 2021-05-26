<?php

namespace App\Listeners;

use App\Events\GroceryListChanged;
use App\Events\GroceryListChangedEvent;
use App\Services\GroceryList\GroceryListSharedService;

class UpdateGroceryListListener
{
    private $list_service;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(GroceryListSharedService $list_service)
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
