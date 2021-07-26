<?php

namespace App\Listeners;

use App\Events\RegionChangedEvent;
use App\Models\GroceryList;
use App\Services\GroceryList\GroceryListSharedService;

class RegionChangedListener
{
    // User Region Changed - Update All Shopping Lists
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
    public function handle(RegionChangedEvent $event)
    {   
        $user = $event->user;

        $lists = GroceryList::where('user_id', $user->id)->get();
        foreach($lists as $list){
            $this->list_service->update_list($list, $user->region_id, $user->supermarket_chain_id);
        }
    }
}
