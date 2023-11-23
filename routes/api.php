<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::group(['middleware' => []], function() {
Route::post('otp_test', 'Api\TaskController@smstest')->middleware('ConnectDbFromOrder');
Route::post('check-dispatcher-keys', 'Api\TaskController@checkDispatcherKeys')->middleware('ConnectDbFromOrder');
Route::post('get-delivery-fee', 'Api\TaskController@getDeliveryFee')->middleware('ConnectDbFromOrder');
Route::post('get-delivery-fee-rental', 'Api\TaskController@getDeliveryFeeRental')->middleware('ConnectDbFromOrder');
Route::post('task/create', 'Api\TaskController@CreateTask')->middleware('ConnectDbFromOrder');
Route::any('task/callNotification', 'Api\TaskController@callNotification')->middleware('ConnectDbFromOrder');
Route::post('task/update_order_prepration_time', 'Api\TaskController@addBufferTime')->middleware('ConnectDbFromOrder');

Route::post('return-to-warehouse-task', 'Api\TaskController@returnToWarehouseTask')->middleware('ConnectDbFromOrder');
Route::post('get/agents', 'Api\AgentController@getAgents')->middleware('ConnectDbFromOrder');
Route::get('get/agent_detail/{id?}', 'Api\AgentController@getAgentDetails')->middleware('ConnectDbFromOrder');
Route::post('agent/check_slot', 'Api\AgentSlotController@getAgentsSlotByTags')->middleware('ConnectDbFromOrder');
Route::post('task/lims/create', 'Api\TaskController@CreateLimsTask')->middleware('ConnectDbFromOrder');
Route::post('agent/create', 'Api\DriverRegistrationController@storeAgent')->middleware('ConnectDbFromOrder');
Route::post('send-documents','Api\DriverRegistrationController@sendDocuments')->middleware('ConnectDbFromOrder');
Route::get('get-agent-tags', 'Api\TaskController@getAgentTags')->middleware('ConnectDbFromOrder');
Route::get('get-all-teams', 'Api\TaskController@getAllTeams')->middleware('ConnectDbFromOrder');
Route::post('update-create-vendor-order', 'Api\AuthController@updateCreateVendorOrder')->middleware('ConnectDbFromOrder');
Route::post('task/update', 'Api\TaskController@UpdateTask')->middleware('ConnectDbFromOrder');


Route::post('task/addwaitingtime', 'Api\TaskController@addWaitingTime')->middleware('ConnectDbFromOrder');
Route::post('task/update_order_prepration_time', 'Api\TaskController@addBufferTime')->middleware('ConnectDbFromOrder');


Route::post('task/updateBidRide', 'Api\TaskController@updateBidRideOrder')->middleware('ConnectDbFromOrder');
//
Route::post('getProductPrice', 'Api\OrderPanelController@getProductPrice')->middleware('ConnectDbFromOrder');
Route::post('getProductPriceByAgent', 'Api\OrderPanelController@getProductPriceByAgent')->middleware('ConnectDbFromOrder');

Route::post('sync-category-product', 'Api\SyncCategoryProductController@SyncCategoryProduct')->middleware('ConnectDbFromOrder');


Route::post('get-dispatch-panel-keys', 'Api\TaskController@checkDispatchPanelKeys')->middleware('ConnectDbForDispatch');
Route::get('get-dispatch-panel-detail', 'Api\TaskController@getDispatchPanelDetails')->middleware('ConnectDbForDispatch');
Route::post('sync-inventory-category-product', 'Api\SyncInventoryCategoryProductController@SyncInventoryCategoryProduct')->middleware('ConnectDbForDispatch');

Route::post('chat/sendNotificationToAgent',      'Api\ChatControllerOrderNotification@sendNotificationToAgent')->middleware('ConnectDbFromOrder');


Route::post('update-order-feedback','Api\TaskController@SaveFeedbackOnOrder')->name('SaveFeedbackOnOrder')->middleware('ConnectDbFromOrder');

Route::post('upload-image-for-task','Api\TaskController@uploadImageForTask')->name('uploadImageForTask')->middleware('ConnectDbFromOrder');

Route::get('/notification/tracking/{order_id}', 'Api\TaskController@notificationTrackingDetail')->middleware('ConnectDbFromOrder');
Route::post('get/general_slot', 'Api\AgentSlotController@getGerenalSlot')->middleware('ConnectDbFromOrder');

Route::post('shortCode', 'Api\ShortcodeController@validateCompany');

Route::get('importCustomer', 'Api\ImportThirdPartyUserController@importCustomer');

// routes for edit order
Route::post('edit-order/driver/notify', 'Api\TaskController@editOrderNotification')->middleware('ConnectDbFromOrder');

// bid ride request notifications
Route::post('bidriderequest/notifications', 'Api\TaskController@bidRideRequestNotification')->middleware('ConnectDbFromOrder');
Route::post('bidRide/notification','Api\TaskController@sendBidAcceptRejectNotification')->middleware('ConnectDbFromOrder');                 // api to get bid requests placed from order side

//route for reschedule order
Route::post('order/reschedule', 'Api\OrderController@rescheduleOrder')->middleware('ConnectDbFromOrder');

// route for cancel order request status
Route::post('cancel-order-request-status/driver/notify', 'Api\TaskController@cancelOrderRequestStatusNotification')->middleware('ConnectDbFromOrder');

Route::group(['middleware' => ['dbCheck', 'apiLocalization']], function() {
    Route::get('client/preferences', 'Api\ActivityController@clientPreferences');
    Route::get('cmscontent','Api\ActivityController@cmsData');

});


Route::group(['prefix' => 'auth'], function () {

	Route::group(['middleware' => [
                    'dbCheck', 'AppAuth', 'apiLocalization']], function() {
        Route::get('logout', 'Api\AuthController@logout');
        Route::post('send/referralcode', 'Api\ActivityController@postSendReffralCode');

    });

    Route::group(['middleware' => ['dbCheck','apiLocalization']], function() {
        Route::post('new-send-documents','Api\DriverRegistrationController@sendDocuments');
    	Route::post('sendOtp', 'Api\AuthController@sendOtp');
        Route::post('login', 'Api\AuthController@login');
        Route::post('signup', 'Api\AuthController@signup');
        Route::post('signup/sendOtp', 'Api\DriverRegistrationController@sendOtp');
        Route::post('signup/verifyOtp', 'Api\DriverRegistrationController@verifyOtp');
        Route::post('get-driver-refferal', 'Api\AuthController@driverRefferal')->middleware('ConnectDbFromOrder');
        //Route::get('cmscontent','Api\ActivityController@cmsData');

        // driver with product pricing  agent/category_with_product
        Route::group(['prefix' => 'agent'], function () {
            Route::get('category_with_product', 'Api\SalerController@CategoryWithProduct');
            Route::post('save_product_variant_price', 'Api\SalerController@saveProductVariantPrice');
            Route::get('general_slot', 'Api\SalerController@getGerenalSlot');
            Route::post('saveSlot', 'Api\AgentSlotController@saveAgentSlot');

        });

    });

});

Route::group(['middleware' => ['dbCheck', 'AppAuth','apiLocalization']], function() {
    Route::post('filter_task_list',        'Api\AgentController@getTaskListWithDate');
    Route::post('create-razorpay-details', 'Api\RazorpayGatewayController@razorpay_create_contact')->name('razorpay_connect');
    Route::post('create-razorpay-add-funds', 'Api\RazorpayGatewayController@razorpay_add_funds_accounts')->name('razorpay_add_account');


    Route::get('user', 'Api\AuthController@user');
    Route::post('agent/delete', 'Api\AuthController@deleteAgent');
    Route::post('agent/add_delete_block_slot', 'Api\AgentSlotController@AddDeleteBlockSlot');
    Route::get('taskList', 'Api\ActivityController@tasks');                    // api for task list
    Route::get('updateStatus', 'Api\ActivityController@updateDriverStatus');   // api for chnage driver status active ,in-active
    Route::post('updateCabPoolingStatus', 'Api\ActivityController@updateDriverCabPoolingStatus');  // api for change status of drivers pooling availability
    Route::post('updateTaskStatus', 'Api\TaskController@updateTaskStatus');    // api for chnage task status like start,cpmplate, faild
    Route::post('checkOTPRequried', 'Api\TaskController@checkOTPRequried');    // api for chnage task status like start,cpmplate, faild
    Route::post('task/accecpt/reject', 'Api\TaskController@TaskUpdateReject'); // api for accecpt task reject task
    Route::get('refer_task', 'Api\ActivityController@getReferOrder');                    // api for task list

    Route::post('task/reject', 'Api\TaskController@RejectOrder');

    Route::get('get/profile','Api\ActivityController@profile');                // api for get agent profile
    Route::post('update/profile','Api\ActivityController@updateProfile');       // api for updateprofile
    Route::get('task/history','Api\ActivityController@taskHistory');            // api for get task history
    Route::post('agentWallet/credit', 'Api\WalletController@creditAgentWallet');      // api for credit money into agent wallet
    Route::get('payment/options/{page}','Api\PaymentOptionController@getPaymentOptions'); // api for payment options
    Route::post('agent/logs', 'Api\ActivityController@agentLog');              // api for save agent logs
    Route::get('agent/transaction/details/{id}', 'Api\DriverTransactionController@transactionDetails');   // api for agent transactions
    Route::get('agent/bank/details', 'Api\AgentPayoutController@agentBankDetails'); // api for getting agent bank details
    Route::get('agent/payout/details', 'Api\AgentPayoutController@agentPayoutDetails'); // api for agent payout details

    Route::post('agent/payout/request/create/{id}', 'Api\AgentPayoutController@agentPayoutRequestCreate'); // api for creating agent payout request
    Route::post('chat/startChat',      'Api\ChatController@startChat');
    Route::post('chat/userAgentChatRoom',      'Api\ChatController@userAgentChatRoom');
    Route::post('chat/sendNotification',      'Api\ChatController@sendNotificationToUser');

    Route::get('agent/poolingTaskSuggession', 'Api\ActivityController@poolingTasksSuggessions');                    // api for task list suggession for cab pooling
    
    // bid and ride api
    Route::get('bidRide/requests','Api\ActivityController@getBidRideRequests');                  // api to get bid requests placed from order side
    Route::post('accept/decline/bidRide/requests','Api\ActivityController@getAcceptDeclinedBidRideRequests');  // api to decline/accept bid requests placed from order side

    //Route::post('chat/userAgentChatRoom',      'Api\ChatController@startChat');
    //Route::get('agent/go-to-home-address', 'Api\AgentController@getAgentgotoHomeAddress'); // api for get status go to home address
    Route::post('agent/update-go-to-home-address-status', 'Api\AgentController@addAgentgotoHomeAddress'); // api for add go to home address enabled/disabled
    Route::post('agent/add-home-address', 'Api\AgentController@addagentAddress'); // api for add enable/disbale go to home address
    Route::get('agent/home-address', 'Api\AgentController@allHomeAddress');        // api for get go to home address
    Route::post('agent/home-address-status', 'Api\AgentController@HomeAddressStatus'); // api for change status address go to home

    // Order routes
    Route::post('order/cancel/request/create/{id}', 'Api\OrderController@createOrderCancelRequest'); // api for creating order cancel request by driver
    Route::get('order/cancel/reasons', 'Api\OrderController@getOrderCancelReasons'); // api for creating order cancel request by driver

    Route::post('agent/inAttendence', 'Api\AgentAttendenceController@create');// api for in attendence agent
    Route::post('agent/outAttendence', 'Api\AgentAttendenceController@update'); // api for out attendence agent
    Route::post('agent/getAttendence', 'Api\AgentAttendenceController@getTodayAttendance'); // api for out attendence agent
    Route::get('agent/getAttendenceHistory', 'Api\AgentAttendenceController@getAttendanceHistory'); // api for creating order cancel request by driver

    //Agent Out of plateform upload pop

    Route::post('agent/outofplatform/upload-pop', 'Api\AgentPayoutController@AgentUploadPop')->name('agent.outofplateform.upload');
    Route::get('agent/threshold-payments', 'Api\AgentPayoutController@AgentThresholdPayments')->name('agent.threshold.payments');


    //Roadside Pickup
    Route::post('task/road-side-pickup', 'Api\TaskController@roadsidePickup');

    // Driver subscription
    Route::group(['prefix' => 'driver/subscription'], function () {
        Route::get('plans', 'Api\DriverSubscriptionController@getSubscriptionPlans');
        Route::get('selectPlan/{slug}', 'Api\DriverSubscriptionController@selectSubscriptionPlan');
        Route::post('purchase/{slug}', 'Api\DriverSubscriptionController@purchaseSubscriptionPlan');
        Route::post('cancel/{slug}', 'Api\DriverSubscriptionController@cancelSubscriptionPlan');
        Route::get('checkActivePlan/{slug}', 'Api\DriverSubscriptionController@checkActiveSubscriptionPlan');
    });

    // All Payment gateways
    Route::get('payment/{gateway}', 'Api\PaymentOptionController@postPayment');
    Route::post('before-payment/obo','Api\OboPaymentController@beforePayment')->name('obo.pay');
    Route::get('after-payment/obo','Api\OboPaymentController@afterPayment')->name('after.obo.payment');

    // driver with product pricing  agent/category_with_product
    Route::group(['prefix' => 'agent'], function () {
        Route::get('category_with_product_with_price', 'Api\SalerController@CategoryWithProductWithPrice');
        Route::get('getslot', 'Api\AgentSlotController@getAgentSlot');
        Route::post('delete_slot', 'Api\AgentSlotController@deleteSlot');
    });

    Route::post('userRating', 'Api\ActivityController@userRating');
    Route::get('task/pending_payment_order','Api\ActivityController@pendingPaymentOrder');            // api for get task history
    Route::post('product_sku/bydb','Api\SalerController@getProductSkeParticulerDB');            // api for get task history
});
        
Route::group(['prefix' => 'v1', 'middleware' => ['apiLocalization']], function () {

    Route::post('check-order-keys', 'Api\BaseController@checkOrderPanelKeys')->middleware('ConnectDbFromDispatcher');
    Route::post('get-order-panel-detail', 'Api\BaseController@getPanelDetail')->middleware('ConnectDbFromDispatcher');

});
Route::group(['middleware' => 'dbCheck','prefix' => 'public'], function() {

    Route::post('task/create', 'Api\TaskController@CreateTask');
    Route::get('task/currentstatus', 'Api\TaskController@currentstatus');
});


});
