<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//switch language route
Route::get('hitevent', function (Request $request) {
	event(new \App\Events\agentLogFetch());
	dd("Event successfull");
});

Route::get('/switch/language', function (Request $request) {
	if ($request->lang) {
		session()->put("applocale", $request->lang);
	}
	return redirect()->back();
});

// payment sateway
Route::get('payment/gateway/returnResponse', 'PaymentOptionController@getGatewayReturnResponse')->name('payment.gateway.return.response');


Route::group(['middleware' => 'switchLanguage'], function () {
	$imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fill/90/90/sm/0/plain/';
	Route::get('dispatch-logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
	Route::get('show/{agent_doc}', function ($agent_doc) {
		$filename = $agent_doc->file_name;
		$path = storage_path($filename);

		return Response::make($imgproxyurl . Storage::disk('s3')->url($filename), 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'inline; filename="' . $filename . '"'
		]);
	});

	Route::get('/howto/signup', function () {
		return view('How-to-SignUp-in-Royo-Dispatcher');
	});
	Route::get('terms_n_condition', 'CMSScreenController@terms_n_condition');
	Route::get('privacy_policy', 'CMSScreenController@privacy_policy');

	Auth::routes();

	Route::get('check-redis-jobs', function () {
		$connection = null;
		$default = 'default';

		//For the delayed jobs
		print_r("For the delayed jobs");
		print_r("<pre>");
		print_r(\Queue::getRedis()->connection($connection)->zrange('queues:' . $default . ':delayed', 0, -1));
		print_r("</pre>");
		//For the reserved jobs
		print_r("For the reserved jobs");
		print_r("<pre>");
		var_dump(\Queue::getRedis()->connection($connection)->zrange('queues:' . $default . ':reserved', 0, -1));
		print_r("</pre>");
	});

	Route::group(['prefix' => '/godpanel', 'middleware' => 'CheckGodPanel'], function () {
		Route::get('/', function () {
			dd('werewr');
			return view('godpanel/login');
		});
		Route::get('/login', function () {
			return view('godpanel/login');
		})->name('get.god.login');
		Route::post('login', 'Godpanel\LoginController@Login')->name('god.login');

		Route::middleware('auth')->group(function () {

			Route::any('/logout', 'Godpanel\LoginController@logout')->name('god.logout');
			Route::get('dashboard', 'Godpanel\DashBoardController@index')->name('god.dashboard');
			Route::resource('client', 'Godpanel\ClientController');
			Route::resource('language', 'Godpanel\LanguageController');
			Route::resource('currency', 'Godpanel\CurrencyController');

			Route::post('exportDb/{dbname}', 'Godpanel\ClientController@exportDb')->name('client.exportdb');


			Route::post('socketUpdate/{id}', 'Godpanel\ClientController@socketUrl')->name('client.socketUpdate');
			Route::post('socketUpdateAction/{id}', 'Godpanel\ClientController@socketUpdateAction')->name('client.socketUpdateAction');


			Route::get('chatsocket', 'Godpanel\chatSocketController@chatsocket')->name('chatsocket');
			Route::post('chatsocket/save/{id?}', 'Godpanel\chatSocketController@chatsocketSave')->name('chatsocket.save');
			Route::get('chatsocket/edit/{id}', 'Godpanel\chatSocketController@editchatsocket')->name('chatsocket.edit');
			Route::post('chatsocket/upDateSocket/{id}', 'Godpanel\chatSocketController@upDateSocket')->name('chatsocket.upDateSocket');
			Route::post('chatsocket/upDateSocketStatus/{id}', 'Godpanel\chatSocketController@upDateSocketStatus')->name('chatsocket.upDateSocketStatus');
			Route::get('chatsocket/deleteSocketUrl/{id}', 'Godpanel\chatSocketController@deleteSocketUrl')->name('chatsocket.delete');
			Route::post('/enable-lumen-service','Godpanel\ClientController@enableLumenService')->name('enable-lumen-service');
			Route::post('/enable-notification-service','Godpanel\ClientController@enableNotificationService')->name('enable-notification-service');

		});
	});

	Route::domain('{domain}')->middleware(['subdomain'])->group(function () {

		Route::group(['middleware' => ['domain', 'database']], function () {

			Route::get('/signin', function () {
				return view('auth/login');
			})->name('client-login');
			Route::get('get-order-session', 'LoginController@getOrderSession')->name('setorders');
			Route::get('passxxy', 'LoginController@passxxy');
		});
	
		Route::get('/demo/page', function () {
			return view('demo');
		});

		Route::get('file-download/{filename}', 'DownloadFileController@index')->name('file.download.index');
		Route::get('file-uploaded-download/{filename}', 'DownloadFileController@downloadUploadedFile')->name('uploadeddownload');

		Route::post('/login/client', 'LoginController@clientLogin')->name('client.login');
		Route::get('/wrong/url', 'LoginController@wrongurl')->name('wrong.client');
		Route::group(['middleware' => 'database'], function () {
			Route::get('/order/tracking/{clientcode}/{order_id}', 'TrackingController@OrderTracking')->name('order.tracking');
			Route::get('/order-details/tracking/{clientcode}/{order_id}', 'TrackingController@OrderTrackingDetail')->name('order.tracking.detail');
			Route::get('/order-cancel/tracking/{clientcode}/{order_id}', 'TrackingController@orderCancelFromOrder')->name('order.cancel.from_order');
			Route::get('/order/driver-rating/{clientcode}/{order_id}', 'TrackingController@DriverRating')->name('order.driver.rating');
			Route::get('/order/form-attribute/{clientcode}/{order_id}', 'TrackingController@OrderFormAttribute')->name('order.tracking');
			Route::get('/order/driver_additional_rating/{clientcode}/{order_id}', 'TrackingController@OrderRatingform')->name('order.driverAdditional.rating');
			Route::post('/order/submit_driver_additional_rating/{clientcode}/{order_id}', 'TrackingController@OrderRatingSubmit')->name('submit.driverAdditional.rating');
			// Create agent connected account stripe
			Route::get('client/verify/oauth/token/stripe', 'StripeGatewayController@verifyOAuthToken')->name('verify.oauth.token.stripe');
			Route::get('order/invoice/{id?}', 'TrackingController@OrderInvoice')->name('oderInvoice');
			//Route::get('payment/gateway/connect/response', 'BaseController@getGatewayConnectResponse')->name('payment.gateway.connect.response');

			// Payment Gateway Routes
			Route::get('payment/gateway/returnResponse', 'PaymentOptionController@getGatewayReturnResponse')->name('payment.gateway.return.response');
			Route::post('payment/khalti/verification', 'KhaltiGatewayController@khaltiVerification')->name('payment.khaltiVerification');
			Route::post('payment/khalti/completePurchase/app', 'KhaltiGatewayController@khaltiCompletePurchaseApp')->name('payment.khaltiCompletePurchaseApp');
			Route::get('payment/webview/khalti', 'KhaltiGatewayController@webView')->name('payment.khalti.webView');
			Route::get('payment/paystack/completePurchase/app', 'PaystackGatewayController@paystackCompletePurchaseApp')->name('payment.paystackCompletePurchaseApp');
			Route::get('payment/paystack/cancelPurchase/app', 'PaystackGatewayController@paystackCancelPurchaseApp')->name('payment.paystackCancelPurchaseApp');
			Route::any('payment/livees/api', 'LiveePaymentController@payFormWeb')->name('livees.webview');
			Route::any('livee/success','LiveePaymentController@afterPayment')->name('livee.payment');

		});
		Route::any('payment/ccavenue/success', 'CcavenueController@successForm')->name('ccavenue.success');
		Route::get('ccavenue/pay', 'CcavenueController@viewForm');

		Route::any('payment/vnpay/notify', 'VnpayController@VnpayNotify')->name('payment.vnpay.VnpayNotify'); // webhook
		Route::any('payment/vnpay/api',    'VnpayController@vnpay_respontAPP')->name('vnpay_webview');
		Route::get('driver/wallet/refreshBalance/{id?}', 'AgentController@refreshWalletbalance')->name('driver.wallet.refreshBalance');
		Route::get('api_documentation', 'DashBoardController@api_documentation');
		Route::group(['middleware' => ['auth:client'], 'prefix' => '/'], function () {

			Route::post('rating_type/create', 'Rating\RatingTypeController@store')->name('rating_type.create');
			Route::get('rating_type/list', 'Rating\RatingTypeController@index')->name('rating_type.index');
			Route::get('rating_type/show/{id}', 'Rating\RatingTypeController@edit')->name('rating_type.show');
			Route::get('rating_type/delete/{id}', 'Rating\RatingTypeController@destroy')->name('rating_type.delete');
			Route::get('vnpay/test',   'VnpayController@order');
			Route::any('vnpay_respont', 'VnpayController@vnpay_respont')->name('vnpay_respont');

            Route::get('notifi', 'AgentController@test_notification');
			Route::get('agent/threshold','AgentThresholdController@index')->name('threshold.agent.list');
			Route::get('agent/threshold/filter', 'AgentThresholdController@ThresholdAgentFilter');
			Route::get('agent/threshold/export', 'AgentThresholdController@export')->name('agents.threshold.export');
            Route::get('agent/threshold/paymentstatus','AgentThresholdController@CheckPaymentStatus')->name('threshold.agent.payment.status');
            Route::post('agent/threshold/paymentaction','AgentThresholdController@UpdatePaymentStatus')->name('threshold.agent.payment.update');
			Route::get('vnpay/test',   'VnpayController@order');
			Route::get('agent/filter', 'AgentController@agentFilter');
			Route::get('agent/export', 'AgentController@export')->name('agents.export');
			Route::get('customer/filter', 'CustomerController@customerFilter');
			Route::get('customer/export', 'CustomerController@customersExport')->name('customer.export');
			Route::get('task/export', 'TaskController@tasksExport')->name('task.export');
			Route::get('task/filter', 'TaskController@taskFilter');
			Route::get('analytics', 'AccountingController@index')->name('accounting');
			Route::get('profileImg', 'ProfileController@displayImage');
			Route::get('', 'DashBoardController@index')->name('index');
			Route::post('dashboard/teamsdata', 'DashBoardController@dashboardTeamData')->name('dashboard.teamsdata');
			Route::get('customize', 'ClientController@ShowPreference')->name('preference.show');
			Route::post('dashboard/agentTeamsdata', 'AgentDashBoardController@dashboardTeamData')->name('dashboard.agent-teamsdata');
			Route::post('dashboard/agentOrdersdata', 'AgentDashBoardController@dashboardOrderData')->name('dashboard.agent-orderdata');
			// Route::post('update-order-panel-db-detail', 'ClientController@orderPanelDbDetail')->name('update.orderPanelDbDetail');
			Route::post('save/cms/{id}', 'ClientController@cmsSave')->name('cms.save');
			Route::post('client_preference/{id}', 'ClientController@storePreference')->name('preference');
			Route::post('route-create-configure/{id}', 'ClientController@routeCreateConfigure')->name('route.create.configure');
			Route::post('task/proof', 'ClientController@taskProof')->name('task.proof');
			Route::post('update-contact-us', 'ClientController@updateContactUs')->name('update.contact.us');
			Route::get('configure', 'ClientController@ShowConfiguration')->name('configure');
			Route::post('smtp/save', 'ClientController@saveSmtp')->name('smtp');
            Route::post('fivcon/save', 'ClientController@faviconUoload')->name('favicon');
			Route::get('options', 'ClientController@ShowOptions')->name('options');
			Route::resource('agent', 'AgentController');
			Route::post('fleet/get-order-detail', 'FleetController@orderFleetDetail');
			Route::post('fleet/get-car-detail', 'FleetController@carDetail');
			Route::get('fleet/filter', 'FleetController@fleetFilter');
			Route::get('fleet/{id}/driver', 'FleetController@assignDriver');
			Route::get('fleet/details/{id}', 'FleetController@fleetDetails');
			Route::POST('fleet/updateDriver', 'FleetController@updateDriver');
			Route::resource('fleet', 'FleetController');

			Route::get('agent/{id}/show', 'AgentController@show')->name('agent.show');
			Route::post('agent/search', 'AgentController@search')->name('agent.search');
			Route::post('pay/receive', 'AgentController@payreceive')->name('pay.receive');
			Route::get('agent/paydetails/{id}', 'AgentController@agentPayDetails')->name('agent.paydetails');
			Route::post('agent/approval_status', 'AgentController@approval_status')->name('agent/approval_status');
			Route::get('agent/payout/requests', 'AgentPayoutController@agentPayoutRequests')->name('agent.payout.requests');
			Route::get('agent/payout/requests/export', 'AgentPayoutController@export')->name('agents.payout.requests.export');
			Route::get('agent/payout/requests/filter', 'AgentPayoutController@agentPayoutRequestsFilter')->name('agent.payout.requests.filter');
			Route::get('general_slots','GeneralSlotController@index');
			Route::get('category/filter', 'CategoryController@categoryFilter');
            Route::get('services/filter', 'ServicesController@servicesFilter');

			Route::get('product-category/filter/{id}', 'CategoryController@productCategoryFilter')->name('category.product.filter');

        	Route::post('agent/payout/request/complete', 'AgentPayoutController@agentPayoutRequestComplete')->name('agent.payout.request.complete');
			Route::post('agent/payout/requests/complete/all', 'AgentPayoutController@agentPayoutRequestsCompleteAll')->name('agent.payout.requests.complete.all');
			Route::post('agent/payout/bank/details', 'AgentPayoutController@agentPayoutBankDetails')->name('agent.payout.bank.details');
			Route::post('agent/change_approval_status', 'AgentController@change_approval_status')->name('agent/change_approval_status');
			Route::resource('customer', 'CustomerController');

			Route::get('agent/driver-list', 'Accountancy\DriverAccountingController@driverList')->name('driver-list');
			// Driver Accountancy
			Route::group(['prefix' => 'driver-accounting'], function () {
				Route::any('/', 'Accountancy\DriverAccountingController@index')->name('driver-accountancy.index');
				Route::get('driver-list', 'Accountancy\DriverAccountingController@driverList')->name('driver-list');
				Route::get('driver-datatable', 'Accountancy\DriverAccountingController@driverDatatable')->name('driver-datatable');
				Route::post('pay-to-agent', 'Accountancy\DriverAccountingController@agentPayoutRequestComplete')->name('pay-to-agent');
				Route::post('/export', 'Accountancy\DriverAccountingController@export')->name('driver-accounting.export');
			});
			Route::get('changeStatus', 'CustomerController@changeStatus');
			Route::resource('tag', 'TagController');
			Route::get('tag/{id}/{type}/edit', 'TagController@edit')->name('tag.edit');
			Route::delete('tag/{id}/{type}', 'TagController@destroy')->name('tag.destroy');
			Route::resource('auto-allocation', 'AllocationController');
			Route::patch('auto-allocation-update/{id}', 'AllocationController@updateAllocation')->name('auto-update');
			Route::resource('profile', 'ProfileController');
			Route::resource('geo-fence', 'GeoFenceController');
			Route::get('geo-fence-all', 'GeoFenceController@allList')->name('geo.fence.list');
			Route::resource('team', 'TeamController');
			Route::get('team/agents/export/{team_id}', 'TeamController@exportAgents')->name('team.agents.export');
			Route::delete('team-agent/{team_id}/{agent_id}', 'TeamController@removeTeamAgent')->name('team.agent.destroy');
			Route::resource('notifications', 'ClientNotificationController');
			Route::resource('pricing-rules', 'PricingRulesController');
			Route::post('notification_update', 'ClientNotificationController@updateClientNotificationEvent')->name('notification.update.client');
			Route::post('set_webhook_url', 'ClientNotificationController@setWebhookUrl')->name('set.webhook.url');
			Route::post('set_message', 'ClientNotificationController@setmessage')->name('set.message');
			Route::resource('manager', 'ManagerController');
			Route::resource('plan-billing', 'PlanBillingController');
			Route::get('batchs/', 'TaskController@batchlist')->name('batch.list');
			Route::POST('batchDetails/', 'TaskController@batchDetails')->name('batchDetails');
			Route::resource('tasks', 'TaskController');
			Route::post('get-warehouse-products', 'TaskController@getWarehouseProducts')->name('getWarehouseProducts');
			Route::post('newtasks', 'TaskController@newtasks');
			Route::any('updatetasks/tasks/{id}', 'TaskController@update');
			Route::post('single_taskdelete', 'TaskController@deleteSingleTask')->name('tasks.single.destroy');

			Route::get('get-category-warehouse', 'TaskController@getCategoryWarehouse')->name('getCategoryWarehouse');

			Route::get('dispatcher-index', 'TaskController@dispatcherIndex')->name('dispatcher-index');
			Route::get('dispatcher-autoallocation', 'TaskController@dispatcherAutoAllocation')->name('dispatcher-autoallocation');
			Route::get('get-inventory-products', 'TaskController@getInventoryProducts')->name('getInventoryProducts');
			
			Route::get('get-product-detail', 'TaskController@getProductDetail')->name('get-product-detail');
			Route::post('get-route-detail', 'TaskController@getRouteDetail')->name('get-route-detail');
			
			

			Route::get('create-product-route', 'TaskController@createProductRoute')->name('create-product-route');
			
			Route::get('dispatcher-add-route', 'TaskController@dispatcherAddRoute')->name('dispatcher-add-route');
			
			Route::get('get-category-list', 'CategoryController@getCategoryList')->name('get-category-list');
			
			Route::get('inventory-update', 'TaskController@inventoryUpdate')->name('inventory-update');
			Route::get('get-warehouse-data', 'TaskController@getWarehouseData')->name('get-warehouse-data');
			Route::post('get-warehouse/{id}', 'TaskController@getWarehouse')->name('get-warehouse');
			Route::post('sort-products', 'TaskController@sortProducts')->name('sort-products');
			
			
		 	Route::get('get-product-name', 'TaskController@getProductName')->name('getProductName');
			Route::post('get-selected-warehouses', 'TaskController@getSelectedWarehouses')->name('getSelectedWarehouses');

			Route::post('create-subtask', 'TaskController@createSubtask')->name('createSubtask');
			
			Route::post('optimize-route', 'DashBoardController@optimizeRoute');
			Route::post('arrange-route', 'DashBoardController@arrangeRoute');
			Route::post('optimize-arrange-route', 'DashBoardController@optimizeArrangeRoute');
			Route::post('export-path', 'DashBoardController@ExportPdfPath');
			Route::post('generate-pdf', 'DashBoardController@generatePdf')->name('download.pdf');

			Route::post('tasks/list/{id}', 'TaskController@tasklist')->name('task.list');
			Route::post('search/customer', 'TaskController@search')->name('search');
			Route::post('remove-location', 'CustomerController@changeLocation');
			Route::post('get-tasks', 'DashBoardController@getTaskDetails');

			Route::post('task/importCSV', 'TaskController@importCsv')->name('tasks.importCSV');

			/* Store Client Information */
			Route::post('submit_client', 'UserProfile@SaveRecord')->name('store_client');
			Route::any('/logout', 'LoginController@logout')->name('client.logout');
			/* Client Profile update */
			Route::put('client/profile/{id}', 'ClientProfileController@update')->name('client.profile.update');
			Route::post('client/password/update', 'ClientProfileController@changePassword')->name('client.password.update');
			Route::get('/newdemo', function () {
				return view('extraremoved');
			});

			Route::resource('subclient', 'SubClientController');
			Route::post('assign/agent', 'TaskController@assignAgent')->name('assign.agent');
			Route::post('task_route', 'TaskController@getTaskRoute')->name('task.task_route');
			Route::post('assign/date', 'TaskController@assignDate')->name('assign.date');
			Route::get('/order/feedback/{clientcode}/{order_id}', 'TrackingController@OrderFeedback')->name('order.feedback');
			Route::post('/feedback/save', 'TrackingController@SaveFeedback')->name('feedbackSave');
			Route::resource('subadmins', 'SubAdminController');

			Route::resource('warehouse', 'WarehouseController');
			Route::resource('order-panel-db', 'orderPanelController');
			Route::get('inventory-panel-db', 'orderPanelController@inventoryIndex')->name('inventory-panel-db');
			Route::resource('amenities', 'AmenitiesController');
			Route::resource('category', 'CategoryController');
			Route::resource('services', 'ServicesController');
			Route::resource('product', 'ProductController');
			Route::POST('check-sync-status', 'orderPanelController@checkSyncStatus');

			Route::get('category/product/{id}', 'CategoryController@categoryProduct')->name('category.product');

			// Route::get('cat-product/{$id}', 'ProductController@showProduct')->name('showProduct');

			Route::post('/import-order-side-category', 'CategoryController@getOrderSideData')->name('category.importOrderSideCategory');
			
			Route::post('/import-dispatch-side-category', 'CategoryController@getDispatchSideData')->name('category.importDispatchSideCategory');

			Route::get('/order/feedback/{clientcode}/{order_id}', 'TrackingController@OrderFeedback')->name('order.feedback');

			Route::post('/feedback/save', 'TrackingController@SaveFeedback')->name('feedbackSave');


			Route::get('demo/page', 'GeoFenceController@newDemo')->name('new.demo');

			Route::resource('payoption', 'PaymentOptionController');
			Route::post('updateAll', 'PaymentOptionController@updateAll')->name('payoption.updateAll');
			Route::post('payoutUpdateAll', 'PaymentOptionController@payoutUpdateAll')->name('payoutOption.payoutUpdateAll');


			/**  */
			Route::get('cms/agent-sms/templates', 'CMS\DriverSMSTemplateController@index')->name('cms.agent-sms.templates');
			Route::get('cms/agent-sms/template/{id}', 'CMS\DriverSMSTemplateController@show')->name('cms.agent-sms.template.show');
			Route::post('cms/agent-sms/template/update', 'CMS\DriverSMSTemplateController@update')->name('cms.agent-sms.template.update');

			Route::get('cms/page/templates', 'CMS\PageTemplateController@index')->name('cms.page.templates');
			Route::get('cms/page/template/{id}', 'CMS\PageTemplateController@show')->name('cms.page.template.show');
			Route::post('cms/page/template/update', 'CMS\PageTemplateController@update')->name('cms.page.template.update');

			Route::get('cms/email/templates', 'CMS\EmailTemplateController@index')->name('cms.email.templates');
			Route::get('cms/email/template/{id}', 'CMS\EmailTemplateController@show')->name('cms.email.template.show');
			Route::post('cms/email/template/update', 'CMS\EmailTemplateController@update')->name('cms.email.template.update');

			// Subscription module
			Route::get('subscription/plans/driver', 'SubscriptionPlansDriverController@getSubscriptionPlans')->name('subscription.plans.driver');
			Route::post('subscription/plan/save/driver/{slug?}', 'SubscriptionPlansDriverController@saveSubscriptionPlan')->name('subscription.plan.save.driver');
			Route::get('subscription/plan/edit/driver/{slug}', 'SubscriptionPlansDriverController@editSubscriptionPlan')->name('subscription.plan.edit.driver');
			Route::get('subscription/plan/delete/driver/{slug}', 'SubscriptionPlansDriverController@deleteSubscriptionPlan')->name('subscription.plan.delete.driver');
			Route::post('subscription/plan/updateStatus/driver/{slug}', 'SubscriptionPlansDriverController@updateSubscriptionPlanStatus')->name('subscription.plan.updateStatus.driver');
			Route::post('show/subscription/plan/driver', 'SubscriptionPlansDriverController@showSubscriptionPlanDriver')->name('show.subscription.plan.driver');

			// agent slot
			Route::get('calender/data/{id}', 'AgentSlotController@returnJson')->name('agent.calender.data');
			Route::get('attendence-calender/data/{id}', 'AgentAttendenceController@returnJson')->name('agent.attendence.calender.data');
			Route::post('agent/add_slot', 'AgentSlotController@store')->name('agent.saveSlot');
			Route::post('general/add_slot', 'GeneralSlotController@store')->name('services.saveSlot');
			Route::post('agent/update_slot', 'AgentSlotController@update')->name('agent.slot.update');
			Route::get('agent/slot/create/{id}', 'AgentSlotController@create')->name('agent.slot.create');
			Route::post('agent/slot/delete', 'AgentSlotController@destroy')->name('agent.slot.destroy');
			Route::get('general/slot/get', 'AgentSlotController@getGeneralSlot')->name('general.slot.list');
			Route::post('general/slot/save', 'AgentSlotController@saveGeneralSlot')->name('general.slot.save');
			Route::get('general/slot/destroy/{id}', 'AgentSlotController@destroyGeneralSlot')->name('vendor_city.destroy');

			Route::prefix('attribute')->group(function () {
                Route::name('attribute.')->group(function () {
                    Route::get('create', 'FormAttributeController@create')->name('create');
                    Route::get('edit/{id}', 'FormAttributeController@edit')->name('edit');
                    Route::post('store', 'FormAttributeController@store')->name('store');
                    Route::put('update/{id}', 'FormAttributeController@update')->name('update');
                    Route::get('delete/{id}', 'FormAttributeController@delete')->name('delete');
                });
            });

		});
	});

	//feedback & tracking

	Route::group(['middleware' => 'auth', 'prefix' => '/'], function () {
		Route::get('{first}/{second}/{third}', 'RoutingController@thirdLevel')->name('third');
		Route::get('{first}/{second}', 'RoutingController@secondLevel')->name('second');
		Route::get('{any}', 'RoutingController@root')->name('any');
	});

	Route::get('driver/registration/document/edit', 'ClientController@show')->name('driver.registration.document.edit');
	Route::post('driverregistrationdocument/create', 'ClientController@store')->name('driver.registration.document.create');
	Route::post('driverregistrationdocument/update', 'ClientController@update')->name('driver.registration.document.update');

	Route::post('driver/registration/document/delete', 'ClientController@destroy')->name('driver.registration.document.delete');

	Route::post('agent/order/analytics', 'AccountingController@getAgentOrderAnalytics')->name('agent.complete.order');
	Route::post('agent/view/analytics', 'AccountingController@viewAgentOrderAnalytics')->name('agent.view.analytics');
	// ajax token refresh

	// threshold debit amount agent list



	

});

