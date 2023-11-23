<?php

use App\Model\AgentSmsTemplate;
use Carbon\Carbon;
use App\Model\ClientPreference;
use App\Model\OrderPanelDetail;
use App\Model\Client as ClientData;
use App\Model\Countries;
use Illuminate\Support\Facades\Auth;
use App\Model\PaymentOption;
use Illuminate\Support\Facades\Schema;
use App\Model\ClientPreferenceAdditional;
use App\Model\Order;
use GuzzleHttp\Client;
use Kawankoding\Fcm\Fcm;

if (!function_exists('setUserCode')) {
    function setUserCode()
    {
        $userCode = session()->has('userCode');
        if (!$userCode) {
            $user = ClientData::first();
            session()->put('userCode', $user->code);
        }
    }
}

// Returns the values of the additional preferences.
if (!function_exists('checkColumnExists')) {
    /** check if column exits in table
     * @param string $tableName
     * @param string @columnName
     * @return boolean true or false
     */
    function checkColumnExists($tableName, $columnName)
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('getAdditionalPreference')) {
    /**
     * getAdditionalPreference
     *
     * @param  mixed $key
     * @return void
     */
    function getAdditionalPreference($key = array())
    {
        setUserCode();
        $return = [];
        $dbreturn = [];
        if (sizeof($key)) {
            $result = (checkColumnExists('client_preference_additional', 'key_name')) ? ClientPreferenceAdditional::select('key_name', 'key_value')->whereIn('key_name', $key)->where(['client_code' => session()->get('userCode')])->get() : [];
            $return = array_column($result->toArray(), 'key_value', 'key_name');
            if (sizeof($result)) {
                $dbreturn = array_column($result->toArray(), 'key_value', 'key_name');
            }
            $emp = array_diff($key, array_keys($dbreturn));
            $emptyArr = array_fill_keys($emp, '');
            $return = array_merge($emptyArr, $dbreturn);
        }
        return $return;
    }
}

if (!function_exists('pr')) {
    function pr($var)
    {
        echo '<pre>';
        print_r($var);
        echo '</pre>';
        exit();
    }
}
if (!function_exists('http_check')) {
    function http_check($url)
    {
        $return = $url;
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $return = 'http://' . $url;
        }
        return $return;
    }
}
if (!function_exists('getMonthNumber')) {
    function getMonthNumber($month_name)
    {
        if ($month_name == 'January') {
            return 1;
        } else if ($month_name == 'February') {
            return 2;
        } else if ($month_name == 'March') {
            return 3;
        } else if ($month_name == 'April') {
            return 4;
        } else if ($month_name == 'May') {
            return 5;
        } else if ($month_name == 'June') {
            return 6;
        } else if ($month_name == 'July') {
            return 7;
        } else if ($month_name == 'August') {
            return 8;
        } else if ($month_name == 'September') {
            return 9;
        } else if ($month_name == 'October') {
            return 10;
        } else if ($month_name == 'November') {
            return 11;
        } else if ($month_name == 'December') {
            return 12;
        }
    }
}
if (!function_exists('generateOrderNo')) {
    function generateOrderNo($length = 8)
    {
        $number = '';
        do {
            for ($i = $length; $i--; $i > 0) {
                $number .= mt_rand(0, 9);
            }
        } while (!empty(\DB::table('orders')->where('order_number', $number)->first(['order_number'])));
        return $number;
    }
}
if (!function_exists('generateUniqueTransactionID')) {
    function generateUniqueTransactionID()
    {
        $ref = 'txn_' . uniqid(time());
        return $ref;
    }
}
if (!function_exists('convertDateTimeInTimeZone')) {
    function convertDateTimeInTimeZone($date, $timezone, $format = 'Y-m-d H:i:s')
    {
        $date = Carbon::parse($date, 'UTC');
        $date->setTimezone($timezone);
        return $date->format($format);
    }
}
if (!function_exists('getClientPreferenceDetail')) {
    function getClientPreferenceDetail()
    {
        $client_preference_detail = ClientPreference::first();
        return $client_preference_detail;
    }
}
if (!function_exists('lumenDispatchToQueue')) {
    function lumenDispatchToQueue($geo_id, $order_detail)
    {
        try {
            $code = ClientData::select('id', 'code', 'lumen_access_token')->first();
            $preference = getClientPreferenceDetail();

            $url = $preference->lumen_domain_url . '/api/v1/autoAllocateNew';
            $postdata =  ['order_id' => $order_detail->id ?? null, 'geo_id' => $geo_id ?? null];
            $header = [
                'X-API-Key' => $code->lumen_access_token,
                'code' => $code->code
            ];
            $client = new Client(['content-type' => 'application/json', 'headers' => $header]);

            $res = $client->post(
                $url,
                ['form_params' => ($postdata)]
            );
            $response = json_decode($res->getBody(), true);
            return true;
        } catch (\Exception $e) {
            \Log::info('lumen error');
            \Log::info($e->getMessage());
            return false;
        }
    }
}
if (!function_exists('getClientDetail')) {
    function getClientDetail()
    {
        $clientData = ClientData::first();
        return $clientData;
    }
}
if (!function_exists('getRazorPayApiKey')) {
    function getRazorPayApiKey()
    {
        $razorpay_creds = PaymentOption::select('credentials', 'test_mode')->where('code', 'razorpay')->where('status', 1)->first();
        $api_key_razorpay = "";
        if ($razorpay_creds) {
            $creds_arr_razorpay = json_decode($razorpay_creds->credentials);
            $api_key_razorpay = (isset($creds_arr_razorpay->api_key)) ? $creds_arr_razorpay->api_key : '';
        }
        return $api_key_razorpay;
    }
}
if (!function_exists('dateTimeInUserTimeZone')) {
    function dateTimeInUserTimeZone($date, $timezone, $showDate = true, $showTime = true, $showSeconds = false)
    {
        $preferences = ClientPreference::select('date_format', 'time_format')->where('id', '>', 0)->first();
        $date_format = (!empty($preferences->date_format)) ? $preferences->date_format : 'YYYY-MM-DD';
        if ($date_format == 'DD/MM/YYYY') {
            $date_format = 'DD-MM-YYYY';
        }
        $time_format = (!empty($preferences->time_format)) ? $preferences->time_format : '24';
        $date = Carbon::parse($date, 'UTC');
        $date->setTimezone($timezone);
        $secondsKey = '';
        $timeFormat = '';
        $dateFormat = '';
        if ($showDate) {
            $dateFormat = $date_format;
        }
        if ($showTime) {
            if ($showSeconds) {
                $secondsKey = ':ss';
            }
            if ($time_format == '12') {
                $timeFormat = ' hh:mm' . $secondsKey . ' A';
            } else {
                $timeFormat = ' HH:mm' . $secondsKey;
            }
        }

        $format = $dateFormat . $timeFormat;
        return $date->isoFormat($format);
    }
}
if (!function_exists('helper_number_formet')) {
    function helper_number_formet($number)
    {
        return number_format($number, 2);
    }
}
if (!function_exists('getCountryCode')) {
    function getCountryCode($dial_code = '')
    {
        if ($dial_code == '') :
            $clientData = ClientData::select('country_id')->where('id', Auth::user()->country_id)->first();
            $getAdminCurrentCountry = Countries::where('id', '=', Auth::user()->country_id)->select('id', 'code')->first();
        else :
            $getAdminCurrentCountry = Countries::where('phonecode', '=', $dial_code)->select('id', 'code')->first();
        endif;

        if (!empty($getAdminCurrentCountry)) {
            $countryCode = $getAdminCurrentCountry->code;
        } else {
            $countryCode = '';
        }
        return $countryCode;
    }
}
if (!function_exists('getCountryPhoneCode')) {
    function getCountryPhoneCode()
    {
        $clientData = ClientData::select('country_id')->first();
        $getAdminCurrentCountry = Countries::where('id', '=', $clientData->country_id)->select('id', 'phonecode')->first();
        if (!empty($getAdminCurrentCountry)) {
            $countryCode = $getAdminCurrentCountry->phonecode;
        } else {
            $countryCode = '';
        }
        return $countryCode;
    }
}
if (!function_exists('getAgentNomenclature')) {
    function getAgentNomenclature()
    {
        $reference = ClientPreference::first();
        return (empty($reference->agent_name)) ? 'Agent' : $reference->agent_name;
    }
}

/**
 * function for created date into particular format
 * @return 06/07/2022
 */
if (!function_exists('formattedDate')) {
    function formattedDate($date)
    {
        if (!empty($date)) {
            return date("d/m/Y", strtotime($date));
        }
        return;
    }
}
if (!function_exists('connect_with_order_panel')) {
    function connect_with_order_panel()
    {
        $order_panel_details = OrderPanelDetail::first();

        $default = [
            'prefix' => '',
            'engine' => null,
            'strict' => false,
            'charset' => 'utf8mb4',
            'host' => $order_panel_details->db_host,
            'port' => $order_panel_details->db_port,
            'prefix_indexes' => true,
            'database' => $order_panel_details->db_name,
            'username' => $order_panel_details->db_username,
            'password' => $order_panel_details->db_password,
            'collation' => 'utf8mb4_unicode_ci',
            'driver' => env('DB_CONNECTION', 'mysql'),
        ];
        Config::set("database.connections.$order_panel_details->db_name", $default);
        return \DB::connection($order_panel_details->db_name);
    }
}


// Returns the values of the additional preferences.
if (!function_exists('checkColumnExists')) {
    /** check if column exits in table
     * @param string $tableName
     * @param string @columnName
     * @return boolean true or false
     */
    function checkColumnExists($tableName, $columnName)
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            return true;
        } else {
            return false;
        }
    }
}

// Returns the values of the additional preferences.
if (!function_exists('checkTableExists')) {
    /** check if column exits in table
     * @param string $tableName
     * @return boolean true or false
     */
    function checkTableExists($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return true;
        } else {
            return false;
        }
    }
}


function checkImageExtension($image)
{
    $ch =  substr($image, strpos($image, ".") + 1);
    $ex = "@webp";
    if ($ch == 'svg') {
        $ex = "";
    }
    return $ex;
}

if (!function_exists('checkWarehouseMode')) {
    /** check if column exits in table
     * @param string $tableName
     */
    function checkWarehouseMode()
    {
        $preference = checkColumnExists('client_preferences', 'warehouse_mode') ? ClientPreference::select('id', 'warehouse_mode')->first() : '';
        $data = [
            'show_warehouse_module' => 0,
            'show_category_module' => 0,
            'show_inventory_module' => 0
        ];
        if ($preference) {
            $warehouseMode = isset($preference->warehouse_mode) ? json_decode($preference->warehouse_mode) : '';

            if (!empty($warehouseMode->show_warehouse_module) && $warehouseMode->show_warehouse_module == 1) {
                $data['show_warehouse_module'] = 1;
            }
            if (!empty($warehouseMode->show_category_module) && $warehouseMode->show_category_module == 1) {
                $data['show_category_module'] = 1;
            }
            if (!empty($warehouseMode->show_inventory_module) && $warehouseMode->show_inventory_module == 1) {
                $data['show_inventory_module'] = 1;
            }
        }
        return $data;
    }
}


if (!function_exists('checkDashboardMode')) {
    /** check if column exits in table
     * @param string $tableName
     */
    function checkDashboardMode()
    {
        $preference = checkColumnExists('client_preferences', 'dashboard_mode') ? ClientPreference::select('id', 'dashboard_mode')->first() : '';
        $data = [
            'show_dashboard_by_agent_wise' => 0
        ];
        if ($preference) {
            $dashboardMode = isset($preference->dashboard_mode) ? json_decode($preference->dashboard_mode) : '';

            if (!empty($dashboardMode->show_dashboard_by_agent_wise) && $dashboardMode->show_dashboard_by_agent_wise == 1) {
                $data['show_dashboard_by_agent_wise'] = 1;
            }
        }
        return $data;
    }
}

if (!function_exists('decimal_format')) {
    // Number Format according to Client preferences
    function decimal_format($number, $format = "")
    {
        $preference = session()->get('preferences');
        $digits = $preference['digit_after_decimal'] ?? 2;
        return number_format($number, $digits, '.', $format);
    }
}



/**
 * sendSmsTemplate dynamic selection and replace tags
 */

if (!function_exists('sendSmsTemplate')) {
    function sendSmsTemplate($slug, $data)
    {
        $smsTemp = AgentSmsTemplate::where('slug', $slug)->select('content', 'tags', 'template_id')->first();
        $smsBody = $smsTemp->content;
        if (isset($smsTemp->tags) && !empty($smsTemp->tags)) {
            $tages = explode(',', $smsTemp->tags);
            foreach ($tages as $tag) {
                $value = $data[$tag] ?? '';
                $smsBody = str_replace($tag, $value, $smsBody);
            }
        }
        $sms = array('body' => $smsBody, 'template_id' => $smsTemp->template_id ?? '');
        return $sms;
    }
}


if (!function_exists('totalKmTravel')) {
    // Number Format according to Client preferences
    function totalKmTravel($id)
    {
        $km = Order::where('fleet_id', $id)->sum('actual_distance');
        return $km;
    }
}

if (!function_exists('sendnotification')) {
    function sendNotification($data, $fcmKey)
    {
        if (isset($data)) {
            $fcmObj = new Fcm($fcmKey);

            return $fcmObj
                ->to($data['registration_ids'])
                ->priority('high')
                ->timeToLive(0)
                ->data([
                    'title' => $data['notification']['title'],
                    'body' => $data['notification']['body'],
                ])
                ->notification([
                    'title' => $data['notification']['title'],
                    'body' => $data['notification']['body'],
                ])
                ->send();
        }
    }
}
