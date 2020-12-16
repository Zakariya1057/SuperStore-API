<?php

namespace App\Traits;

use Elasticsearch\Client;

trait SearchTrait {

    public function search(Client $client, $index, $query, $limit=10): Array{

        $index = strtolower($index);
        $query = strtolower($query);

        if($index == 'products'){
            $fields_match = ['name','description','brand','dietary_info'];
            $fields_should = ['name', 'weight','brand'];
        } else {
            $fields_match = ['name'];
            $fields_should = ['name', 'weight'];
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
                                    'fields' => $fields_match,
                                    'operator' => 'or',
                                    'fuzziness' => 'auto'
                                ]
                            ]
                        ],
                    
                        'should' => [
                            [
                                'multi_match' => [
                                    'query' => $query,
                                    'fields' => $fields_should,
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