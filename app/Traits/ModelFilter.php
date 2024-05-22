<?php

namespace App\Traits;
use Illuminate\Support\Facades\DB;

trait ModelFilter
{
    public static function filter($fields = [], $primary_field = 'id', $compulsoryWhere = [], $with = [], $search_other_fields = true)
    {
        $query          = self::query();
        $filters        = request('columnFilters', []);
        $sorts          = request('sort', []);
        $page           = request('page', 1);
        $perPage        = request('perPage', 50);
        $searchTerm     = request('searchTerm', null);

        if (!empty($compulsoryWhere)) {
            if (isset($compulsoryWhere[0])) {
                foreach ($compulsoryWhere as $row) {
                    if (count($row) == 3) {
                        $query->where($row[0], $row[1], $row[2]);
                    } else {
                        $query->where($row);
                    }
                }
            } else {
                $query->where($compulsoryWhere);
            }
        }


        if ($searchTerm) {
            $like = "%$searchTerm%";

            // $query->where($primary_field, 'like', $like);

            if ($search_other_fields) {
                $query->orWhere(function ($query) use ($like, $fields) {
                    collect($fields)->each(function ($field) use ($like, $query) {
                        $query->orWhere($field, $like);
                    });
                });
            }
        }

        foreach ($filters as $key => $value) {
            if ($key && $value) {
                $query->where($key, $value);
            }
        }

        $ordered = false;

        foreach ($sorts as $row) {
            $excluded = [
                'status_html',
                'merchant.merchant_name',
                'command.custom_command'
            ];

            if (is_array($row) && $row['type'] && $row['field']) {
                if ($row['field'] == 'status_html') {
                    $row['field'] = 'order_status';
                }

                if (collect($excluded)->contains($row['field'])) {
                    continue;
                }

                $query->orderBy($row['field'], $row['type']);
                $ordered = true;
            }
        }

        if (!$ordered) {
            $query->orderBy($primary_field, 'desc');
        }

        $query->skip(((int)$page - 1) * $perPage)->take($perPage);

        if (count($with) > 0) {
            $query->with($with);
        }

        return $query->get();
    }

    public static function filterN($fields = [], $primary_field = 'id', $compulsoryWhere = [], $with = [], $search_other_fields = true,  $query='' )
    {
        
        $filters        = request('columnFilters', []);
        $sorts          = request('sort', []);
        $page           = request('page', 1);
        $perPage        = request('perPage',50);
          $searchTerm     = trim(request('searchTerm', null));
        $order = '';
    

        if ($searchTerm) {
           
            $like ="%$searchTerm%";

            // $query->where($primary_field, 'LIKE', $like);

            if ($search_other_fields) {
                $query->where(function ($query) use ($like, $fields) {
                    collect($fields)->each(function ($field) use ($like, $query) {
                        $query->orWhere("$field",'LIKE', "$like");

                    });
                });
            }
           
        }



        if($filters){
     
        foreach ($filters as $key => $value) {
            if ($key && $value) {
                $query->where($key, $value);
            }
        }
    }

        $ordered = false;

        if($sorts){

        foreach ($sorts as $row) {
            $excluded = [
                'status_html',
                'merchant.merchant_name',
                'command.custom_command'
            ];

            if (is_array($row) && $row['type'] && $row['field']) {
                if ($row['field'] == 'status_html') {
                    $row['field'] = 'order_status';
                }

                if (collect($excluded)->contains($row['field'])) {
                    continue;
                }

                $query->orderBy($row['field'], $row['type']);
                $ordered = true;
            }
        }
    }

        if (!$order) {
            $query->orderBy($primary_field, 'desc');
        }

        if ($order) {
            $query->orderBy($order);
        }

        $Filtered = $query->count();
        $perPage = $perPage == 0 ? $Filtered: $perPage;

      
        // $query->offset(((int)$page - 1) * $perPage)->limit($perPage)->get(); 
        // return  (object)['query'=> $query->get(), 'total'=>$Filtered];
        return  (object)['query'=> $query->paginate($perPage), 'total'=>$Filtered];

    }

}


