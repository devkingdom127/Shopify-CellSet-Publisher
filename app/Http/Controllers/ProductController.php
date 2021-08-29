<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use Storage;

class ProductController extends Controller
{
    public function validateHandle(Request $request)
    {
        $shop = User::first();

        if(!$shop) {
            return response()->json(['message' => "App isn't installed to store."], 400);
            exit();
        }

        $request_data = $request->json()->all();
        
        if(!is_array($request_data)) {
            return response()->json(['message' => 'Wrong Input'], 400);
            exit();
        }

        $options = new Options();
        $options->setVersion('2020-07');

        // Create the client and session
        $api = new BasicShopifyAPI($options);
        $api->setSession(new Session(config('shopify-app.myshopify_domain'), $shop->password));

        $product_handles = array();
        $invalid_handles = array();
        $invalid_tags = array();
        $invalid_collections = array();
        $invalid_vendors = array();
        $invalid_producttypes = array();
        
        if(array_key_exists('handles', $request_data)) {
            foreach($request_data['handles'] as $product_handle) {
                $response = $this->checkHandleByName($api, $product_handle);
                if(is_array($response) && array_key_exists('errors', $response)) {
                    return response()->json($response, $response['status']);
                }
                
                if($this->checkHandleByName($api, $product_handle) == true) {
                    array_push($product_handles, $product_handle);
                } else {
                    array_push($invalid_handles, $product_handle);
                }
            }
        }

        if(array_key_exists('tags', $request_data)) {
            foreach($request_data['tags'] as $product_tag) {
                $handles = $this->getHandlesByTag($api, $product_tag);
                if(array_key_exists('errors', $handles)) {
                    return response()->json($handles, $handles['status']);
                }
                if(count($handles) > 0) {
                    $product_handles = array_unique(array_merge($product_handles, $handles));
                } else {
                    array_push($invalid_tags, $product_tag);
                }
            }
        }

        if(array_key_exists('collections', $request_data)) {
            foreach($request_data['collections'] as $product_collection) {
                $handles = $this->getHandlesByCollection($api, $product_collection);
                if(array_key_exists('errors', $handles)) {
                    return response()->json($handles, $handles['status']);
                }
                if(count($handles) > 0) {
                    $product_handles = array_unique(array_merge($product_handles, $handles));
                } else {
                    array_push($invalid_collections, $product_collection);
                }
            }
        }

        if(array_key_exists('vendors', $request_data)) {
            foreach($request_data['vendors'] as $product_vendor) {
                $handles = $this->getHandlesByVendor($api, $product_vendor);
                if(array_key_exists('errors', $handles)) {
                    return response()->json($handles, $handles['status']);
                }
                if(count($handles) > 0) {
                    $product_handles = array_unique(array_merge($product_handles, $handles));
                } else {
                    array_push($invalid_vendors, $product_vendor);
                }
            }
        }

        if(array_key_exists('producttypes', $request_data)) {
            foreach($request_data['producttypes'] as $producttype) {
                $handles = $this->getHandlesByProductType($api, $producttype);
                if(array_key_exists('errors', $handles)) {
                    return response()->json($handles, $handles['status']);
                }
                if(count($handles) > 0) {
                    $product_handles = array_unique(array_merge($product_handles, $handles));
                } else {
                    array_push($invalid_producttypes, $producttype);
                }
            }
        }

        $response = array(
            "handles" => array_values($product_handles),
            "invalid" => array(
                "handles" => $invalid_handles,
                "tags" => $invalid_tags,
                "collections" => $invalid_collections,
                "vendors" => $invalid_vendors,
                "producttypes" => $invalid_producttypes
            )
        );

        echo json_encode($response);
    }

    public function getProductData(Request $request) {
        $shop = User::first();
        
        if(!$shop) {
            return response()->json(['message' => "App isn't installed to store."], 400);
            exit();
        }

        $request_data = $request->json()->all();
        
        if(!is_array($request_data) || empty($request_data)) {
            return response()->json(['message' => 'Wrong Input'], 400);
            exit();
        }

        $options = new Options();
        $options->setVersion('2020-07');

        // Create the client and session
        $api = new BasicShopifyAPI($options);
        $api->setSession(new Session(config('shopify-app.myshopify_domain'), $shop->password));

        $handles = array();
        $invalid = array();

        if(array_key_exists('handles', $request_data)) {
            foreach($request_data['handles'] as $handle) {
                $request = array();
                $handle_data = array();
                $request['images'] = array_key_exists('images', $request_data) ? $request_data['images'] : 0;
                $request['shopifyProduct'] = array_key_exists('shopifyProduct', $request_data) ? $request_data['shopifyProduct'] : [];
                $request['shopifyVariant'] = array_key_exists('shopifyVariant', $request_data) ? $request_data['shopifyVariant'] : [];
                
                $response = $this->getHandleData($api, $handle, $request);

                if($response['errors']) {
                    return response()->json($response, $response['status']);
                }

                $product_data = $response['body']['container']['data']['productByHandle'];

                if(empty($product_data)) {
                    array_push($invalid, $handle);
                } else {
                    $data = $response['body']['container']['data']['productByHandle'];
                    $handle_data['handle'] = $handle;
                    $handle_data['images'] = array_map(function ($image) {
                        return $image['node']['transformedSrc'];
                    }, $data['images']['edges']);

                    $handle_data['shopifyVariant'] = head(array_map(function ($variant) {
                        return $variant['node'];
                    }, $data['variants']['edges']));
                    
                    unset($data['images']);
                    unset($data['variants']);
                    //This is for image cache, first it will check for exiting images if found will send cache image else it will save and than it will send
                    if (isset($handle_data['images']) and count($handle_data['images'])) {
                        $images = [];

                        foreach ($handle_data['images'] as $key => $single_image) {
                            $single_image = head(explode('?', $single_image));
                            $image_name = last(explode('/',$single_image));
                            
                            $path = storage_path('app/public/products/'.$image_name);
                            
                            if (file_exists($path)) {
                                $images[] = asset('storage/products/'.$image_name);
                            }else{
                                $image = file_get_contents($single_image);
                                Storage::put('public/products/'.$image_name, $image);
                                $images[] = asset('storage/products/'.$image_name);
                            }
                        }

                        $handle_data['images'] = $images;
                    }

                    $handle_data['shopifyProduct'] = $data;

                    array_push($handles, $handle_data);
                }
            }

            return response()->json(['handles' => $handles, 'invalid' => $invalid]);
        }
    }

    public function checkHandleByName($api, $product_handle) {
        $response = $api->graph("
            {
                productByHandle(handle: \"${product_handle}\") {
                    handle,
                    id
                }
            }
        ");
        
        if($response['status'] != 200) {
            return $response;
        }
        $handle = $response['body']['container']['data']['productByHandle'];
        
        if(!isset($handle['handle']) and ($handle == null or $handle == '')) {
            return false;
        }

        if (isset($handle['handle']) and $handle['handle'] != '') {
            $id = $handle['id'];
            $response = $api->graph("
                {
                    product(id: \"${id}\") {
                        id,
                        publishedAt
                    }
                }
            ");

            if($response['status'] != 200) {
                return $response;
            }

            $product = $response['body']['container']['data']['product'];
        
            if(!isset($product['id'])) {
                return false;
            }

            if(isset($product['id']) and $product['publishedAt'] == null) {
                return false;
            }
        }

        return true;
    }

    public function getHandlesByTag($api, $product_tag)
    {
        $product_handles = array();
        $cursor = null;
        $has_next_page = false;

        do {
            $after = $cursor ? ',after:"'.$cursor.'"' : '';
            $response = $api->graph("
                {
                    products(first: 100, query: \"tag:${product_tag}\" ${after}) {
                        pageInfo {
                            hasNextPage
                        }
                        edges {
                            cursor
                            node {
                                handle
                            }
                        }
                    }
                }
            ");

            if($response['status'] != 200) {
                return $response;
            }

            $product_data = $response['body']['container']['data']['products'];
            $product_handles = array_merge($product_handles, array_map(function ($product_handle) {
                return $product_handle['node']['handle'];
            }, $product_data['edges']));
            $has_next_page = $product_data['pageInfo']['hasNextPage'];
            if($has_next_page == 1) {
                $cursor = $product_data['edges'][99]['cursor'];
            } else {
                $cursor = null;
            }
        } while($has_next_page == 1);

        return $product_handles;
    }

    public function getHandlesByCollection($api, $product_collection)
    {
        $product_handles = array();
        $cursor = null;
        $has_next_page = false;

        do {
            $after = $cursor ? ',after:"'.$cursor.'"' : '';
            $response = $api->graph("
                {
                    collectionByHandle(handle: \"${product_collection}\") {
                        products(first: 100 ${after}) {
                            pageInfo {
                                hasNextPage
                            }
                            edges {
                                cursor
                                node {
                                    handle
                                }
                            }
                        }
                    }
                }
            ");

            if($response['status'] != 200) {
                return $response;
            }

            $product_data = $response['body']['container']['data']['collectionByHandle'] != null ? $response['body']['container']['data']['collectionByHandle']['products'] : null;
            if($product_data != null) {
                $product_handles = array_merge($product_handles, array_map(function ($product_handle) {
                    return $product_handle['node']['handle'];
                }, $product_data['edges']));
                $has_next_page = $product_data['pageInfo']['hasNextPage'];
            }
            if($has_next_page == 1) {
                $cursor = $product_data['edges'][99]['cursor'];
            } else {
                $cursor = null;
            }
        } while($has_next_page == 1);

        return $product_handles;
    }

    public function getHandlesByVendor($api, $product_vendor)
    {
        $product_handles = array();
        $cursor = null;
        $has_next_page = false;

        do {
            $after = $cursor ? ',after:"'.$cursor.'"' : '';
            $response = $api->graph("
                {
                    products(first: 100, query: \"vendor:${product_vendor}\" ${after}) {
                        pageInfo {
                            hasNextPage
                        }
                        edges {
                            cursor
                            node {
                                handle
                            }
                        }
                    }
                }
            ");

            if($response['status'] != 200) {
                return $response;
            }

            $product_data = $response['body']['container']['data']['products'];
            $product_handles = array_merge($product_handles, array_map(function ($product_handle) {
                return $product_handle['node']['handle'];
            }, $product_data['edges']));
            $has_next_page = $product_data['pageInfo']['hasNextPage'];
            if($has_next_page == 1) {
                $cursor = $product_data['edges'][99]['cursor'];
            } else {
                $cursor = null;
            }
        } while($has_next_page == 1);

        return $product_handles;
    }

    public function getHandlesByProductType($api, $product_type)
    {
        $product_handles = array();
        $cursor = null;
        $has_next_page = false;

        do {
            $after = $cursor ? ',after:"'.$cursor.'"' : '';
            $response = $api->graph("
                {
                    products(first: 100, query: \"product_type:${product_type}\" ${after}) {
                        pageInfo {
                            hasNextPage
                        }
                        edges {
                            cursor
                            node {
                                handle
                            }
                        }
                    }
                }
            ");

            if($response['status'] != 200) {
                return $response;
            }

            $product_data = $response['body']['container']['data']['products'];
            $product_handles = array_merge($product_handles, array_map(function ($product_handle) {
                return $product_handle['node']['handle'];
            }, $product_data['edges']));
            $has_next_page = $product_data['pageInfo']['hasNextPage'];
            if($has_next_page == 1) {
                $cursor = $product_data['edges'][99]['cursor'];
            } else {
                $cursor = null;
            }
        } while($has_next_page == 1);

        return $product_handles;
    }

    public function getHandleData($api, $handle, $request)
    {
        $query = "
        {
            productByHandle(handle: \"${handle}\") {";
        
        foreach($request['shopifyProduct'] as $product_column) {
            $query .= "\n" . $product_column;
        }

        $query .= "
              images(first: " . $request['images'] . ") {
                edges {
                  node {
                    transformedSrc
                  }
                }
              }
              variants(first: 100) {
                edges {
                  node {";
        foreach($request['shopifyVariant'] as $variant_column) {
            $query .= "\n" . $variant_column;
        }

        $query .= "
                  }
                }
              }
            }
          }
          ";

        
        $response = $api->graph($query);
        
        return $response;
    }
}
