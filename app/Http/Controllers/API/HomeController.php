<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\MonitoringTrait;
use App\Traits\PromotionTrait;
use App\Traits\StoreTrait;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use StoreTrait;
    use MonitoringTrait;
    use PromotionTrait;

    public function show(Request $request){

        $user = $request->user();
        // 1. Grocery List Price Changes
        // 2. Store Locations(Asda)
        // 3. Monitored Price Changes
        // 4. Offers, 2 for 3,
        // 5. Best Fruits, Vegetables, Meats

        $stores = $this->stores_by_type(1,false);
        $monitoring = $this->monitoring_products($user->id);
        $promotions = $this->store_promotions(1);

        $data = [

            // Add In Future, with Tesco
            // 'grocery_list' => [
            //    // Price changes in shopping list, based on sale or rollback. Only really works for sites like asda. 
            // ],

            'list_progress' => [], // Grocery List Items, Recently Used 4. Progress Bar. 10/5. In Progress. -> Click Show
            'stores' => $stores,
            'grocery_items_sale' => $monitoring, // Items from shopping list on sale, offers
            'monitoring' => $monitoring, // Items added to monitoring list
            'offers' => $promotions, // Top product promotions, top 3 groups

            'best_categories' => [
                // Top 20 Items From Categories
                'Fruits' => [], 
                'Vegetables' => [],
                'Drinks' => [],
                'Meats' => []
            ]
        ];


        return response()->json(['data' => $data]);
    }

}
