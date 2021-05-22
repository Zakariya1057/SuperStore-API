<?php
namespace App\Services\RefinePaginate;

use Illuminate\Database\Eloquent\Builder;

class PaginateService {

    public function paginate_results(Builder $base_query, $limit = 100){
        $results = [];

        $paginator = $base_query->paginate($limit);

        $results['products'] = $paginator->items();

        $results['paginate'] = [
            'from' => 0,
            'current' => $paginator->currentPage(),
            'to' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'next_page_url' => $paginator->url( $paginator->currentPage() + 1),
            'current_page_url' => $paginator->url( $paginator->currentPage() ),
            'prev_page_url' => $paginator->previousPageUrl(),
            'more_available' => $paginator->hasMorePages(),
        ];

        return $results;
    }

}

?>