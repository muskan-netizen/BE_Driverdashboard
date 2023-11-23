<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\ {
    Category,
    CategoryTranslation,
    Product,
    ProductVariant,
    ProductCategories,
    ProductTranslation,
    ClientPreference,
    Client,
    OrderPanelDetail
};

use Illuminate\Support\Facades\Http;
use App\Model\Customer;
use DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(REQUEST $request)
    {
        $category = [];
        $product_category = [];
        $sku_url = '';
        $order_panel_id = $request->input('order_panel_id');
        $order_panel = [];
        if (@$order_panel_id && $order_panel_id != 'null') {
            $order_panel = OrderPanelDetail::find($order_panel_id);

            if ($order_panel->sync_status == 2) {
                $orderpanel = OrderPanelDetail::find($order_panel_id);
                $orderpanel->sync_status = 0;
                $orderpanel->save();
            }
        }

        if (checkTableExists('categories')) {
            $category = Category::with('products')->orderBy('id', 'DESC')->paginate(10);
            if (checkColumnExists('categories', 'order_panel_id')) {
                if ($order_panel_id != "all" && $order_panel_id != null) {
                    $category = Category::with('products')->where('order_panel_id', $order_panel_id)
                        ->orderBy('id', 'DESC')
                        ->paginate(10);
                }
            }
            $product_category = Category::orderBy('id', 'DESC')->get();

            $client = Client::orderBy('id', 'asc')->first();
            if (isset($client->custom_domain) && ! empty($client->custom_domain) && $client->custom_domain != $client->sub_domain)
                $sku_url = ($client->custom_domain);
            else
                $sku_url = ($client->sub_domain . env('SUBMAINDOMAIN'));

            $sku_url = array_reverse(explode('.', $sku_url));
            $sku_url = implode(".", $sku_url);
        }
        $orderDb_detail = OrderPanelDetail::all();

        return view('category.index')->with([
            'order_panel' => $order_panel,
            'category' => $category,
            'product_category' => $product_category,
            'sku_url' => $sku_url,
            'order_db_detail' => $orderDb_detail
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $check_category = [];
        if ($request->cat_id == '') {
            $check_category = [];
            if (checkTableExists('categories')) {
                $check_category = Category::where('slug', $request->name)->first();
            }
        }
        if (empty($check_category)) {
            if (checkTableExists('categories')) {
                Category::updateOrCreate([
                    'id' => $request->cat_id
                ], [
                    'slug' => $request->input('name'),
                    'type_id' => 1,
                    'is_visible' => 1,
                    'status' => $request->input('status')
                ]);
            }
            if (checkTableExists('category_translations')) {
                CategoryTranslation::updateOrCreate([
                    'category_id' => $request->cat_id
                ], [
                    'name' => $request->input('name'),
                    'status' => $request->input('status')
                ]);
            }
            if ($request->cat_id == '') {
                return redirect()->back()->with('success', 'Category Added Successfully.');
            } else {
                return redirect()->back()->with('success', 'Category Updated Successfully.');
            }
        } else {
            return redirect()->back()->with('error', 'Category Already Exist.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Category $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($port, Category $category)
    {
        if (checkTableExists('categories')) {
            $category->forceDelete();
        }
        return redirect()->back()->with('success', 'Category Deleted Successfully');
    }

    public function getOrderSideData(Request $request)
    {
        $order_panel_id = $request->order_panel_id;

        if ($order_panel_id != 'all') {
            $order_details = OrderPanelDetail::find($order_panel_id);

           
            $order_details->sync_status = 0;
            $order_details->save();
            $order_details = OrderPanelDetail::find($order_panel_id);
            $url = $order_details->url;
          

            
            // URL
            // $apiAuthCheckURL = $url.'/api/v1/dispatcher/check-order-keys';

            // // POST Data
            // $postInput = [

            // ];

            // // Headers
            $headers = [
                'shortcode' => $order_details->code,
//                 'code' => $order_details->code,
                'key' => $order_details->key
            ];

            // $response = Http::withHeaders($headers)->post($apiAuthCheckURL, $postInput);

            // // $statusCode = $response->status();
            // $checkAuth = json_decode($response->getBody(), true);

            // if( @$checkAuth['status'] == 200){
            $apiRequestURL = $url . '/api/v1/category-product-sync-dispatcher';

            // POST Data
            $Dispatcher_url = $_SERVER['HTTP_ORIGIN']; // $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
            $clients = Client::where('is_superadmin', 1)->select('id', 'code')->first();

            // POST Data
            $postInput = [
                'order_panel_id' => $order_panel_id,
                'dispatcher_url' => $Dispatcher_url,
                'dispatcher_code' => $clients->code
            ];
            // $headers['Authorization'] = $checkAuth['token'];
            
            
            $response = Http::withHeaders($headers)->post($apiRequestURL, $postInput);
        
            
            $responseBody = json_decode($response->getBody(), true);

         
            
            if (@$responseBody['status'] == 200) {
                $order_details = OrderPanelDetail::find($order_panel_id);
                $order_details->sync_status = 1;
                $order_details->save();
//                 dd($responseBody);
                // $this->importOrderSideCategory($responseBody['data'],$order_panel_id);
            } elseif (@$responseBody['error'] && ! empty($responseBody['error'])) {
                return redirect()->back()->with('error', $responseBody['error']);
            }
            // } elseif( @$checkAuth['status'] == 401){
            // return redirect()->back()->with('error', $checkAuth['message']);
            // }else{
            // return redirect()->back()->with('error', 'Invalid Order Panel Url.');
            // }
            return redirect()->back()->with('success', 'Category & Product Import Is Processing.');
        } else {
            return redirect()->back()->with('error', 'Please select order panel DB.');
        }
    }

    public function getCategoryList(Request $request)
    {
        if ($request->ajax()) {

            $category = Category::where('slug', 'like', '%' . $request->search . '%')->get();

            $options = view("modals.category-list-ajax", compact('category'))->render();
            return $options;
        }
    }

    public function getDispatchSideData(Request $request)
    {
        $order_panel_id = $request->order_panel_id;

        if ($order_panel_id != 'all') {
            $order_details = OrderPanelDetail::find($order_panel_id);
            $order_details->sync_status = 0;
            $order_details->save();
            $order_details = OrderPanelDetail::find($order_panel_id);
            $url = $order_details->url;

            // URL
            $apiAuthCheckURL = $url . '/api/v1/check-dispatch-keys';

            // POST Data
            $postInput = [];

            // Headers
            $headers = [
                'shortcode' => $order_details->code,
                'code' => $order_details->code,
                'key' => $order_details->key,
                'Authorization' => Auth::user()->getRememberToken()
            ];

            $response = Http::withHeaders($headers)->post($apiAuthCheckURL, $postInput);

            // $statusCode = $response->status();
            $checkAuth = json_decode($response->getBody(), true);

            if (@$checkAuth['status'] == 200) {
                $apiRequestURL = $url . '/api/v1/category-product-sync-dispatcher';

                // POST Data
                $postInput = [
                    'order_panel_id' => $order_panel_id
                ];

                $headers = [
                    'shortcode' => $order_details->code,
                    'Authorization' => $checkAuth['token']
                ];

                $response = Http::withHeaders($headers)->post($apiRequestURL, $postInput);
                $responseBody = json_decode($response->getBody(), true);
                if (@$responseBody['status'] == 200) {
                    $order_details = OrderPanelDetail::find($order_panel_id);
                    $order_details->sync_status = 1;
                    $order_details->save();

                    // $this->importOrderSideCategory($responseBody['data']);
                } elseif (@$responseBody['error'] && ! empty($responseBody['error'])) {
                    return redirect()->back()->with('error', $responseBody['error']);
                }
            } elseif (@$checkAuth['status'] == 401) {
                return redirect()->back()->with('error', $checkAuth['message']);
            } else {
                return redirect()->back()->with('error', 'Invalid Inventory Panel Url.');
            }
            return redirect()->back()->with('success', 'Category & Product Import Is Processing.');
        } else {
            return redirect()->back()->with('error', 'Please select order panel DB.');
        }
    }

    public function importOrderSideCategory($categories, $order_panel_id)
    {

        foreach ($categories as $cat) {
            $category_id = $this->syncSingleCategory($cat, $order_panel_id);
            if (! empty($cat['products']) && count($cat['products']) > 0) {
                foreach ($cat['products'] as $product) {
                    $product_id = $this->syncSingleProduct($category_id, $product, $order_panel_id);
                    $variantId = $this->syncProductVariant($product_id, $product);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    public function categoryFilter(Request $request)
    {
        $category = Category::withCount('products')->with([
            'translation'
        ]);
        if (checkColumnExists('categories', 'order_panel_id')) {
            $order_panel_id = $request->order_panel_id;
            if ($order_panel_id != "" && $order_panel_id != null) {
                $category = $category->where('order_panel_id', $order_panel_id);
            }
        }
        if (! empty($request->get('search'))) {
            $search = $request->get('search');
            $category = $category->where('slug', 'Like', '%' . $search . '%');
        }
        $category = $category->orderBy('id', 'DESC')->get();
        return Datatables::of($category)->addColumn('name', function ($category) use ($request) {
            $name = isset($category->translation) && isset($category->translation->name) ? $category->translation->name : $category->slug;
            return $name;
        })
            ->addColumn('status', function ($category) use ($request) {
            if ($category->status == 1) {
                return $status = 'Active';
            } else {
                return $status = 'InActive';
            }
        })
            ->addColumn('created_at', function ($category) use ($request) {
            $created_at = ! empty($category->created_at) ? $category->created_at : '';
            return formattedDate($created_at);
        })
            ->addColumn('total_products', function ($category) use ($request) {
            $total_products = $category->products_count; // !empty($category->products) ? count($category->products) : '0';
            return $total_products;
        })
            ->addColumn('action', function ($category) use ($request) {
            $action = '<div class="inner-div"> <a href="' . route('category.product', $category->id) . '" class="action-icon viewIconBtn" data-id="' . $category->id . '" style="margin-top: 5px;"> <i class="mdi mdi-eye" title="Products"></i></a></div>';

            $action .= '<div class="inner-div"> <a href="JavaScript:void(0);"  class="action-icon editIconBtn openEditCategoryModal" data-toggle="modal" data-target="" data-backdrop="static" data-keyboard="false" data-name="' . $category->slug . '" data-id="' . $category->id . '" data-status="' . $category->status . '" style="margin-top: 5px;"> <i class="mdi mdi-square-edit-outline"></i></a></div>';

            $action .= '<div class="inner-div">
            <form method="POST" action="' . route('category.destroy', $category->id) . '">
            <input type="hidden" name="_token" value="' . csrf_token() . '" />
            <input type="hidden" name="_method" value="DELETE">
            <div class="form-group">
            <button type="submit" class="btn btn-primary-outline action-icon"> <i class="mdi mdi-delete" title="Delete"></i></button>
            </div>
            </form>
            </div>';
            return $action;
        })
            ->filter(function ($instance) use ($request) {}, true)
            ->rawColumns([
            'action'
        ])
            ->make(true);
    }

    public function categoryProduct(Request $request, $domain = '', $catId)
    {
        $product_category = [];
        $product_category = Category::orderBy('id', 'DESC')->get();

        $client = Client::orderBy('id', 'asc')->first();
        if (isset($client->custom_domain) && ! empty($client->custom_domain) && $client->custom_domain != $client->sub_domain)
            $sku_url = ($client->custom_domain);
        else
            $sku_url = ($client->sub_domain . env('SUBMAINDOMAIN'));

        $sku_url = array_reverse(explode('.', $sku_url));
        $sku_url = implode(".", $sku_url);

        $products = Product::with([
            'category.cat',
            'primary',
            'variant' => function ($v) {
                $v->select('id', 'product_id', 'quantity', 'price', 'barcode', 'expiry_date')->groupBy('product_id');
            }
        ])->select('id', 'sku', 'vendor_id', 'is_live', 'is_new', 'is_featured', 'has_inventory', 'has_variant', 'sell_when_out_of_stock', 'Requires_last_mile', 'averageRating', 'brand_id', 'minimum_order_count', 'batch_count', 'title')
            ->where('category_id', $catId)
            ->get()
            ->sortBy('primary.title', SORT_REGULAR, false);

        return view('category.category-product')->with([
            'products' => $products,
            'catId' => $catId,
            'product_category' => $product_category,
            'sku_url' => $sku_url
        ]);
    }

    public function productCategoryFilter(Request $request, $domain = '', $catId)
    {
        $products = Product::with([
            'category.cat',
            'primary',
            'variant' => function ($v) {
                $v->select('id', 'product_id', 'quantity', 'price', 'barcode', 'expiry_date')->groupBy('product_id');
            }
        ])->select('id', 'sku', 'vendor_id', 'is_live', 'is_new', 'is_featured', 'has_inventory', 'has_variant', 'sell_when_out_of_stock', 'Requires_last_mile', 'averageRating', 'brand_id', 'minimum_order_count', 'batch_count', 'title')
            ->where('category_id', $catId)
            ->get()
            ->sortBy('primary.title', SORT_REGULAR, false);

        // if (!empty($request->get('search'))) {
        // $search = $request->get('search');
        // $products = $products->where('title', 'Like', '%'.$search.'%');
        // }

        return Datatables::of($products)->addColumn('name', function ($products) use ($request) {
            return !empty($products->primary->title) ? $products->primary->title: $products->title;
        })
            ->addColumn('category', function ($products) use ($request) {
            return $products->category && $products->category->cat && $products->category->cat->name ? $products->category->cat->name : 'N/A';
        })
            ->addColumn('quantity', function ($products) use ($request) {
            return $products->variant->first() ? $products->variant->first()->quantity : 0;
        })
            ->addColumn('price', function ($products) use ($request) {
            return $products->variant->first() ? decimal_format($products->variant->first()->price) : 0;
        })
            ->addColumn('bar_code', function ($products) use ($request) {
            return $products->variant->first() ? $products->variant->first()->barcode : '-';
        })
            ->addColumn('status', function ($products) use ($request) {
            if ($products->is_live == 0) {
                $status = __('Draft');
            } elseif ($products->is_live == 1) {
                $status = __('Published');
            } else {
                $status = __('Blocked');
            }
            return $status;
        })
            ->addColumn('expiry_date', function ($products) use ($request) {
            return $products->variant->first() ? $products->variant->first()->expiry_date : '-';
        })
            ->addColumn('is_new', function ($products) use ($request) {
            return $products->is_new == 0 ? __('No') : __('Yes');
        })
            ->addColumn('is_featured', function ($products) use ($request) {
            return $products->is_featured == 0 ? __('No') : __('Yes');
        })
            ->filter(function ($instance) use ($request) {}, true)
            ->rawColumns([
            'action'
        ])
            ->make(true);
    }

    public function syncSingleProduct($category_id, $product, $order_panel_id)
    {
        // dd($product['translation']);
        if (checkTableExists('products')) {
            $product_update_create = [
                "sku" => $product['sku'],
                "title" => $product['title'],
                "url_slug" => $product['url_slug'],
                "description" => $product['description'],
                "body_html" => $product['body_html'],
                "vendor_id" => $product['vendor_id'],
                "type_id" => $product['type_id'],
                "country_origin_id" => $product['country_origin_id'],
                "is_new" => $product['is_new'],
                "is_featured" => $product['is_featured'],
                "is_live" => $product['is_live'],
                "is_physical" => $product['is_physical'],
                "weight" => $product['weight'],
                "weight_unit" => $product['weight_unit'],
                "has_inventory" => $product['has_inventory'],
                "has_variant" => $product['has_variant'],
                "sell_when_out_of_stock" => $product['sell_when_out_of_stock'],
                "requires_shipping" => $product['requires_shipping'],
                "Requires_last_mile" => $product['Requires_last_mile'],
                "averageRating" => $product['averageRating'],
                "inquiry_only" => $product['inquiry_only'],
                "publish_at" => $product['publish_at'],
                "created_at" => $product['created_at'],
                "updated_at" => $product['updated_at'],
                // "brand_id" => $i_product['brand_id'],
                "tax_category_id" => $product['tax_category_id'] ?? null,
                "deleted_at" => $product['deleted_at'],
                "pharmacy_check" => $product['pharmacy_check'],
                "tags" => $product['tags'],
                "need_price_from_dispatcher" => $product['need_price_from_dispatcher'],
                "mode_of_service" => $product['mode_of_service'],
                "delay_order_hrs" => $product['delay_order_hrs'],
                "delay_order_min" => $product['delay_order_min'],
                "pickup_delay_order_hrs" => $product['pickup_delay_order_hrs'],
                "pickup_delay_order_min" => $product['pickup_delay_order_min'],
                "dropoff_delay_order_hrs" => $product['dropoff_delay_order_hrs'],
                "dropoff_delay_order_min" => $product['dropoff_delay_order_min'],
                "need_shipment" => $product['need_shipment'],
                "minimum_order_count" => $product['minimum_order_count'],
                "batch_count" => $product['batch_count'],
                "delay_order_hrs_for_dine_in" => $product['delay_order_hrs_for_dine_in'],
                "delay_order_min_for_dine_in" => $product['delay_order_min_for_dine_in'],
                "delay_order_hrs_for_takeway" => $product['delay_order_hrs_for_takeway'],
                "delay_order_min_for_takeway" => $product['delay_order_min_for_takeway'],
                "age_restriction" => $product['age_restriction'],
                // 'brand_id' => $product->deleted_at,
                "category_id" => $category_id,
                // "store_id" => $vid,
                'order_panel_id' => $order_panel_id
            ];
            $productSave = Product::updateOrCreate([
                'sku' => $product['sku'],
                'order_panel_id' => $order_panel_id
            ], $product_update_create);

            foreach ($product['translation'] as $translation) {

                $product_trans = [
                    'title' => $translation['title'],
                    'body_html' => $translation['title'],
                    'meta_title' => $translation['title'],
                    'meta_keyword' => $translation['title'],
                    'meta_description' => $translation['title'],
                    'product_id' => $productSave->id,
                    'language_id' => $translation['language_id']
                ];

                ProductTranslation::updateOrCreate([
                    'product_id' => $productSave->id
                ], $product_trans);
            }

            // Sync Product Categories
            $data = [
                'product_id' => $productSave->id,
                'category_id' => $category_id
            ];
            ProductCategories::updateOrCreate([
                'product_id' => $productSave->id
            ], $product_update_create);

            return $productSave->id;
        } else {
            return '';
        }
    }

    public function syncProductVariant($product_id, $product)
    {
        if (checkTableExists('product_variants')) {
            $variants = $product['variant'];
            // # Add product variant
            foreach ($variants as $variant) { # import product variant
                $product_variant = [
                    "sku" => $variant['sku'],
                    "title" => $variant['title'],
                    "quantity" => $variant['quantity'],
                    "price" => $variant['price'],
                    "position" => $variant['position'],
                    "compare_at_price" => $variant['compare_at_price'],
                    "barcode" => $variant['barcode'],
                    "expiry_date" => $variant['expiry_date'] ?? null,
                    "cost_price" => $variant['cost_price'],
                    "currency_id" => $variant['currency_id'],
                    "tax_category_id" => $variant['tax_category_id'],
                    "inventory_policy" => $variant['inventory_policy'] ?? null,
                    "fulfillment_service" => $variant['fulfillment_service'] ?? null,
                    "inventory_management" => $variant['inventory_management'] ?? null,
                    "status" => $variant['status'] ?? 1,
                    "container_charges" => $variant['container_charges'] ?? '0.0000',
                    "product_id" => $product_id
                ];
                $product_variant_import = ProductVariant::updateOrInsert([
                    'sku' => $variant['sku']
                ], $product_variant);
            }
            return true;
        } else {
            return false;
        }
    }

    public function syncSingleCategory($cat, $order_panel_id)
    {
        if (checkTableExists('categories')) {
            $data = [
                'icon' => $cat['icon']['icon'],
                'slug' => $cat['slug'],
                'type_id' => $cat['type_id'],
                'image' => $cat['image']['image'],
                'is_visible' => $cat['is_visible'],
                'status' => $cat['status'],
                'position' => $cat['position'],
                'is_core' => $cat['is_core'],
                'can_add_products' => $cat['can_add_products'],
                'parent_id' => $cat['parent_id'],
                'vendor_id' => $cat['vendor_id'],
                'client_code' => $cat['client_code'],
                'display_mode' => $cat['display_mode'],
                'show_wishlist' => $cat['show_wishlist'],
                'sub_cat_banners' => $cat['sub_cat_banners']['sub_cat_banners'] ?? null,
                'royo_order_category_id' => $cat['id'],
                'order_panel_id' => $order_panel_id
            ];

            $categorySave = Category::updateOrCreate([
                'slug' => $cat['slug'],
                'order_panel_id' => $order_panel_id
            ], $data);
            $transl_data = [
                'name' => $cat['translation']['name'] ?? $cat['slug'],
                'trans-slug' => $cat['translation']['trans_slug'] ?? '',
                'meta_title' => $cat['translation']['meta_title'] ?? '',
                'meta_description' => $cat['translation']['meta_description'] ?? '',
                'meta_keywords' => $cat['translation']['meta_keywords'] ?? '',
                'category_id' => $categorySave->id ?? '',
                'language_id' => 1
            ];
            $categoryTransSave = CategoryTranslation::updateOrCreate([
                'category_id' => $categorySave->id
            ], $transl_data);
            return $categorySave->id;
        } else {
            return '';
        }
    }
}
