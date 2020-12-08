<?php

namespace App\Traits;

use Elasticsearch\Client;

trait SearchTrait {

    public function search(Client $client, $index, $query, $limit=10): Array{

        $index = strtolower($index);
        $query = strtolower($query);

        if($index == 'products'){
            $fields = [
                'name',
                'description',
                'brand'
            ];
        } else {
            $fields = [
                'name'
            ];
        }

        $params = [
            'index' => $index,
            'body'  => [
                'size' => $limit,
                'query' => [
                    'bool' => [
                    'must' => [
                        [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => $fields,
                            'operator' => 'and',
                            'fuzziness' => 'auto'
                        ]
                        ]
                    ],
                    
                    'should' => [
                        [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => [
                                'name'
                            ],
                            'operator' => 'and'
                        ]
                        ]
                    ]
                    ]
                ]
            ]
        ];
        
        return $client->search($params);
        
    }

}

?>