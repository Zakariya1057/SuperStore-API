<?php
namespace App\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;

class RefineService {

    private $promotion_service, $paginate_service;

    public function __construct(PromotionService $promotion_service, PaginateService $paginate_service){
        $this->paginate_service = $paginate_service;
        $this->promotion_service = $promotion_service;
    }

    public function refine_results($base_query, $data, $item_ids = []){

        $text_search = $data['text_search'] ?? false;

        $sort = $data['sort'] ?? '';
        $order = $data['order'] ?? '';

        if(!$text_search || ($text_search && $sort != '' && $order != '')){
            $base_query = $this->search_sort($data, $base_query, $text_search);
        } else {
            $product_ids = join(',', $item_ids);
            $base_query = $base_query->orderByRaw("FIELD(products.id, $product_ids)");
        }

        $base_query = $this->search_dietary($data, $base_query);
        $base_query = $this->search_brand($data, $base_query);
        $base_query = $this->search_category($data, $base_query);
        $base_query = $this->search_promotion($data, $base_query);

        $pagination_data = $this->paginate_service->paginate_results($base_query);

        foreach($pagination_data['products'] as $product){
            $this->promotion_service->set_product_promotion($product);
        }

        return $pagination_data;
    }

    private function search_sort($data, Builder $base_query){

        $order_by_list = [];

        if(key_exists('sort', $data) && key_exists('order', $data) && !is_null($data['order'])){
            $sort = strtolower($data['sort']);
            $order = strtoupper($data['order']);

            if($order != 'ASC' && $order != 'DESC'){
                throw new Exception('Unknown order by option.', 422);
            }

            $sort_options = [
                'rating' => 'avg_rating',
                'price' => 'price',
            ];

            if(key_exists($sort,$sort_options)){
                $order_by_list[] = $sort_options[$sort] . ' '. $order;
            } else {
                throw new Exception('Unknown sort by option.', 422);
            }

        } else {
            $order_by_list[] = 'total_reviews_count / avg_rating desc';
        }
        
        $order_by_list[] = 'products.id asc';
        
        $base_query = $base_query->orderByRaw(join(',', $order_by_list));

        return $base_query;
    }

    private function search_dietary($data, Builder $base_query){
        if(key_exists('dietary', $data) && !is_null($data['dietary'])){
            $dietary_list = explode(',',$data['dietary']);

            foreach($dietary_list as $dietary){
                $base_query = $base_query->where('dietary_info','like', "%$dietary%");
            }
            
        }

        return $base_query;
    }

    private function search_brand($data, Builder $base_query){
        if(key_exists('brand', $data) && !is_null($data['brand'])){
            $brand = $data['brand'];
            $base_query = $base_query->where('brand',$brand);
        }

        return $base_query;
    }

    private function search_category($data, Builder $base_query){
        if(key_exists('child_category', $data) && !is_null($data['child_category'])){
            $category = $data['child_category'];
            $base_query = $base_query->where('child_categories.name',$category);
        }

        return $base_query;
    }

    private function search_promotion($data, Builder $base_query){
        if(key_exists('promotion', $data) && !is_null($data['promotion'])){
            $promotion = $data['promotion'];
            $base_query = $base_query->where('promotions.name',$promotion);
        }

        return $base_query;
    }

}

?>