<?php

namespace App\Http\Controllers;

use DB;
use Excel;
use Exception;
use DataTables;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GCLIENT;
use Twilio\Rest\Client as TwilioClient;
use App\Traits\ApiResponser;
use App\Exports\AgentsExport;
use Doctrine\DBAL\Driver\DrizzlePDOMySql\Driver;
use App\Model\{
    Agent,
    AgentDocs,
    AgentPayment,
    AgentLog,
    DriverGeo,
    Order,
    Otp,
    Team,
    TagsForAgent,
    TagsForTeam,
    Countries,
    Client,
    ClientPreferences,
    DriverRegistrationDocument,
    Geo,
    Timezone,
    AgentSmsTemplate,
    Warehouse
};
use App\Services\FirebaseService;
use Kawankoding\Fcm\Fcm;
use App\Traits\agentEarningManager;
use App\Traits\smsManager;
use Illuminate\Contracts\View\View;

class AgentController extends Controller
{
    use ApiResponser;
    use smsManager;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function test_notification(Request $request)
    {
        $item['device_token'] = $request->tokon ?? 'cf0nUd_GQaKSP1hsvElYJ1:APA91bEExs0HycqrGN-GjPb4uHXO9qUbkedQEjjYOj0655fBkRK0t6zVnuunXPNF_kuJ0vNFvxYz-uDcOBzzWLD5AcdzbVrablc0U7SuBXsBsLSS72gEHG7v3ZhyvuJdPJDoU0FP5VZY';
        // $client_preferences = getClientPreferenceDetail();
        // $fcm_server_key = ! empty($client_preferences->fcm_server_key) ? $client_preferences->fcm_server_key : config('laravel-fcm.server_key');
        // $item['title'] = 'Pickup Request';
        // $item['body'] = 'Check All Details For This Request In App';
        // $fcmObj = new Fcm($fcm_server_key);
        // $fcm_store = $fcmObj->to($new)
        //     ->
        // // $recipients must an array
        // priority('high')
        //     ->timeToLive(0)
        //     ->data($item)
        //     ->notification([
        //     'title' => 'Pickup Request',
        //     'body' => 'Check All Details For This Request In App',
        //     'sound' => 'notification',
        //     'android_channel_id' => 'Royo-Delivery',
        //     'soundPlay' => true,
        //     'show_in_foreground' => true
        // ])
        //     ->send();
        // echo ($new[0]);
        // pr($fcm_store);
        $data = [
            "registration_ids" => is_array($item['device_token']) ? $item['device_token'] : array($item['device_token']), //$item['device_token'],
            "notification" => [
                'title' => 'Pickup Request',
                'body' => 'Check All Details For This Request In App',
                'sound' => 'notification.mp3',
                "android_channel_id" => "Royo-Delivery",
            ],
            "data" => [
                'title' => 'Pickup Request',
                'body' => 'Check All Details For This Request In App',
                'data' => json_encode($item),
                'soundPlay' => true,
                'show_in_foreground' => true,
            ],
            "priority" => "high"
        ];
        $response = FirebaseService::sendNotification($data);

        pr($response);
    }

    // public function index(Request $request)
    // {
    //     $tz = new Timezone();

    //     $user = Auth::user();
    //     $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
    //     $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
    //     $agents = Agent::with('warehouseAgent')->orderBy('id', 'DESC');
    //     // if (!empty($request->date)) {
    //     // $agents->whereBetween('created_at', [$request->date . " 00:00:00", $request->date . " 23:59:59"]);
    //     // }

    //     $deletedAgents = Agent::onlyTrashed()->get();

    //     if ($user->is_superadmin == 0 && $user->all_team_access == 0 && $user->manager_type == 0) {
    //         $agents = $agents->whereHas('team.permissionToManager', function ($query) use ($user) {
    //             $query->where('sub_admin_id', $user->id);
    //         });
    //     } else if ($user->is_superadmin == 0 && $user->manager_type == 1) {
    //         $agents = $agents->whereHas('warehouseAgent', function ($query) use ($managerWarehousesIds) {
    //             $query->whereIn('warehouses.id', $managerWarehousesIds);
    //         });
    //     }
    //     $agents = $agents->get();

    //     $tags = TagsForAgent::all();
    //     $tag = [];
    //     foreach ($tags as $key => $value) {
    //         array_push($tag, $value->name);
    //     }
    //     $teams = Team::where('client_id', auth()->user()->code)->orderBy('name');
    //     if ($user->is_superadmin == 0 && $user->all_team_access == 0) {
    //         $teams = $teams->whereHas('permissionToManager', function ($query) use ($user) {
    //             $query->where('sub_admin_id', $user->id);
    //         });
    //     }

    //     $teams = $teams->get();
    //     $selectedDate = ! empty($request->date) ? $request->date : '';
    //     // $tags = TagsForTeam::all();

    //     $getAdminCurrentCountry = Countries::where('id', '=', $user->country_id)->get()->first();
    //     if (! empty($getAdminCurrentCountry)) {
    //         $countryCode = $getAdminCurrentCountry->code;
    //     } else {
    //         $countryCode = '';
    //     }

    //     // getting all geo fence list to filter agents
    //     $geos = Geo::where('client_id', $user->code)->orderBy('created_at', 'DESC')->get();

    //     $agentsCount = count($agents);
    //     $employeesCount = count($agents->where('type', 'Employee'));
    //     $freelancerCount = count($agents->where('type', 'Freelancer'));
    //     $agentActive = count($agents->where('is_activated', 1));
    //     $agentInActive = count($agents->where('is_activated', 0));
    //     $agentIsAvailable = count($agents->where('is_available', 1));
    //     $agentNotAvailable = count($agents->where('is_available', 0));
    //     $agentIsApproved = count($agents->where('is_approved', 1));
    //     $agentNotApproved = count($agents->where('is_approved', 0));
    //     $agentRejected = count($agents->where('is_approved', 2));
    //     $agentRejected += count($deletedAgents);
    //     $driver_registration_documents = DriverRegistrationDocument::with('driver_option')->get();

    //     $warehouses = Warehouse::get();
    //     $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
    //     $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
    //     if ($user->is_superadmin == 0 && $user->manager_type == 1) {
    //         $warehouses = Warehouse::whereIn('id', $managerWarehousesIds)->get();
    //     }
    //     $client = Client::where('code', $user->code)->with([
    //         'getTimezone',
    //         'getPreference',
    //         'warehouse'
    //     ])->first();
    //     $client_timezone = $client->getTimezone ? $client->getTimezone->timezone : 251;
    //     $timezone = $tz->timezone_name($client_timezone);

    //     $returnHTML = view ('agent/agent-index')->with(['agents' => $agents,'timezone' => $timezone])->render();



    //     return view('agent.index')->with([
    //         'agents' => $agents,
    //         'geos' => $geos,
    //         'driver_registration_documents' => $driver_registration_documents,
    //         'agentIsAvailable' => $agentIsAvailable,
    //         'agentNotAvailable' => $agentNotAvailable,
    //         'agentIsApproved' => $agentIsApproved,
    //         'agentNotApproved' => $agentNotApproved,
    //         'agentsCount' => $agentsCount,
    //         'employeesCount' => $employeesCount,
    //         'agentActive' => $agentActive,
    //         'agentInActive' => $agentInActive,
    //         'freelancerCount' => $freelancerCount,
    //         'teams' => $teams,
    //         'agentData' => $returnHTML,
    //         'tags' => $tags,
    //         'selectedCountryCode' => $countryCode,
    //         'calenderSelectedDate' => $selectedDate,
    //         'showTag' => implode(',', $tag),
    //         'agentRejected' => $agentRejected,
    //         'warehouses' => $warehouses,
    //         'client' => $managerWarehouses
    //     ]);
    // }

    // public function agentFilter(Request $request)
    // {



    //     try {
    //         $tz = new Timezone();
    //         $user = Auth::user();
    //         $client = Client::where('code', $user->code)->with([
    //             'getTimezone',
    //             'getPreference',
    //             'warehouse'
    //         ])->first();

    //         $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
    //         $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
    //         $getAdditionalPreference = getAdditionalPreference([
    //             'pickup_type',
    //             'drop_type',
    //             'is_attendence',
    //             'idle_time'
    //         ]);
    //         $isDriverSlotActive = $client->getPreference ? $client->getPreference->is_driver_slot : 0;
    //         $isAttendence = ($getAdditionalPreference['is_attendence'] == 1) ? $getAdditionalPreference['is_attendence'] : 0;

    //         $request->merge([
    //             'is_driver_slot' => $isDriverSlotActive
    //         ]);

    //         $request->merge([
    //             'is_attendence' => $isAttendence
    //         ]);
    //         $client_timezone = $client->getTimezone ? $client->getTimezone->timezone : 251;
    //         $timezone = $tz->timezone_name($client_timezone);
    //         $agents = Agent::with('warehouseAgent')->orderBy('id', 'DESC');

    //         if (! empty($request->get('date_filter'))) {
    //             $dateFilter = explode('to', $request->get('date_filter'));
    //             if (count($dateFilter) > 1) {
    //                 $agents->whereBetween('created_at', [
    //                     trim($dateFilter[0]) . " 00:00:00",
    //                     trim($dateFilter[1]) . " 23:59:59"
    //                 ]);
    //             } else {
    //                 $agents->whereBetween('created_at', [
    //                     trim($dateFilter[0]) . " 00:00:00",
    //                     trim($dateFilter[0]) . " 23:59:59"
    //                 ]);
    //             }
    //         }
    //         if (! empty($request->get('geo_filter'))) {
    //             $geo_id = $request->get('geo_filter');
    //             $agents->whereHas('geoFence', function ($q) use ($geo_id) {
    //                 $q->where('geo_id', $geo_id);
    //             });
    //         }
    //         if (! empty($request->get('tag_filter'))) {
    //             $tag_id = $request->get('tag_filter');
    //             $agents->whereHas('tags', function ($q) use ($tag_id) {
    //                 $q->where('tag_id', $tag_id);
    //             });
    //         }
    //         if ($user->is_superadmin == 0 && $user->all_team_access == 0 && $user->manager_type == 0) {
    //             $agents = $agents->whereHas('team.permissionToManager', function ($query) use ($user) {
    //                 $query->where('sub_admin_id', $user->id);
    //             });
    //         } else if ($user->is_superadmin == 0 && $user->manager_type == 1) {
    //             $agents = $agents->whereHas('warehouseAgent', function ($query) use ($managerWarehousesIds) {
    //                 $query->whereIn('warehouses.id', $managerWarehousesIds);
    //             });
    //         }

    //         if ($request->status == 2) {
    //             $agents = $agents->withTrashed()
    //                 ->where('is_approved', $request->status)
    //                 ->orWhere(function ($query) {
    //                 return $query->where('is_approved', 1)
    //                     ->where('deleted_at', '!=', NULL);
    //             })
    //                 ->orderBy('id', 'desc');
    //         } else {
    //             $agents = $agents->where('is_approved', $request->status)->orderBy('id', 'desc');
    //         }


    //         $returnHTML = View ('agent/agent-index')->with(['agents' => $agents->get(),'timezone' => $timezone])->render();

    //         return response()->json($returnHTML,200);


    //     } catch (Exception $e) {

    //     }
    // }


    public function index(Request $request)
    {
        $tz = new Timezone();

        $user = Auth::user();
        $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
        $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
        $agents = Agent::with('warehouseAgent')->orderBy('id', 'DESC');
        // if (!empty($request->date)) {
        // $agents->whereBetween('created_at', [$request->date . " 00:00:00", $request->date . " 23:59:59"]);
        // }

        $deletedAgents = Agent::onlyTrashed()->get();

        if ($user->is_superadmin == 0 && $user->all_team_access == 0 && $user->manager_type == 0) {
            $agents = $agents->whereHas('team.permissionToManager', function ($query) use ($user) {
                $query->where('sub_admin_id', $user->id);
            });
        } else if ($user->is_superadmin == 0 && $user->manager_type == 1) {
            $agents = $agents->whereHas('warehouseAgent', function ($query) use ($managerWarehousesIds) {
                $query->whereIn('warehouses.id', $managerWarehousesIds);
            });
        }



        $paginatedAgents = clone $agents;
        $agents = $agents->get();

        // Apply pagination to the cloned instance
        $perPage = 10;
        $paginatedAgents = $paginatedAgents->where('is_approved', 1)->paginate($perPage);

        $tags = TagsForAgent::all();
        $tag = [];
        foreach ($tags as $key => $value) {
            array_push($tag, $value->name);
        }
        $teams = Team::where('client_id', auth()->user()->code)->orderBy('name');
        if ($user->is_superadmin == 0 && $user->all_team_access == 0) {
            $teams = $teams->whereHas('permissionToManager', function ($query) use ($user) {
                $query->where('sub_admin_id', $user->id);
            });
        }


        $teams = $teams->get();
        $selectedDate = !empty($request->date) ? $request->date : '';
        // $tags = TagsForTeam::all();

        $getAdminCurrentCountry = Countries::where('id', '=', $user->country_id)->get()->first();
        if (!empty($getAdminCurrentCountry)) {
            $countryCode = $getAdminCurrentCountry->code;
        } else {
            $countryCode = '';
        }

        // getting all geo fence list to filter agents
        $geos = Geo::where('client_id', $user->code)->orderBy('created_at', 'DESC')->get();

        $agentsCount = count($agents);
        $employeesCount = count($agents->where('type', 'Employee'));
        $freelancerCount = count($agents->where('type', 'Freelancer'));
        $agentActive = count($agents->where('is_activated', 1));
        $agentInActive = count($agents->where('is_activated', 0));
        $agentIsAvailable = count($agents->where('is_available', 1));
        $agentNotAvailable = count($agents->where('is_available', 0));
        $agentIsApproved = count($agents->where('is_approved', 1));
        $agentNotApproved = count($agents->where('is_approved', 0));
        $agentRejected = count($agents->where('is_approved', 2));
        $agentRejected += count($deletedAgents);
        $driver_registration_documents = DriverRegistrationDocument::with('driver_option')->get();

        $warehouses = Warehouse::get();
        $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
        $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
        if ($user->is_superadmin == 0 && $user->manager_type == 1) {
            $warehouses = Warehouse::whereIn('id', $managerWarehousesIds)->get();
        }
        $client = Client::where('code', $user->code)->with([
            'getTimezone',
            'getPreference',
            'warehouse'
        ])->first();
        $client_timezone = $client->getTimezone ? $client->getTimezone->timezone : 251;
        $timezone = $tz->timezone_name($client_timezone);
        $getAdditionalPreference = getAdditionalPreference([
            'pickup_type',
            'drop_type',
            'is_attendence',
            'idle_time'
        ]);


        $paginationLinks = $paginatedAgents->links();

        $returnHTML['html'] = view('agent/agent-index')->with(['agents' => $paginatedAgents, 'timezone' => $timezone])->render();
        $returnHTML['pagination'] = $paginationLinks->toHtml();


        // $agents = Agent::orderBy('id', 'DESC');

        return view('agent.index')->with([
            'agents' => $agents,
            'geos' => $geos,
            'driver_registration_documents' => $driver_registration_documents,
            'agentIsAvailable' => $agentIsAvailable,
            'agentNotAvailable' => $agentNotAvailable,
            'agentIsApproved' => $agentIsApproved,
            'agentNotApproved' => $agentNotApproved,
            'agentsCount' => $agentsCount,
            'employeesCount' => $employeesCount,
            'agentActive' => $agentActive,
            'agentData' => $returnHTML,
            'agentInActive' => $agentInActive,
            'freelancerCount' => $freelancerCount,
            'teams' => $teams,
            'tags' => $tags,
            'selectedCountryCode' => $countryCode,
            'calenderSelectedDate' => $selectedDate,
            'showTag' => implode(',', $tag),
            'agentRejected' => $agentRejected,
            'warehouses' => $warehouses,
            'client' => $managerWarehouses
        ]);
    }

    public function agentFilter(Request $request)
    {
        try {
            $tz = new Timezone();
            $user = Auth::user();
            $client = Client::where('code', $user->code)->with([
                'getTimezone',
                'getPreference',
                'warehouse'
            ])->first();

            $managerWarehouses = Client::with('warehouse')->where('id', $user->id)->first();
            $managerWarehousesIds = $managerWarehouses->warehouse->pluck('id');
            $getAdditionalPreference = getAdditionalPreference([
                'pickup_type',
                'drop_type',
                'is_attendence',
                'idle_time'
            ]);
            $isDriverSlotActive = $client->getPreference ? $client->getPreference->is_driver_slot : 0;
            $isAttendence = ($getAdditionalPreference['is_attendence'] == 1) ? $getAdditionalPreference['is_attendence'] : 0;

            $request->merge([
                'is_driver_slot' => $isDriverSlotActive
            ]);

            $request->merge([
                'is_attendence' => $isAttendence
            ]);

            $client_timezone = $client->getTimezone ? $client->getTimezone->timezone : 251;
            $timezone = $tz->timezone_name($client_timezone);
            $agents = Agent::with('warehouseAgent')->orderBy('id', 'DESC');

            if (!empty($request->get('date_filter'))) {
                $dateFilter = explode('to', $request->get('date_filter'));
                if (count($dateFilter) > 1) {
                    $agents->whereBetween('created_at', [
                        trim($dateFilter[0]) . " 00:00:00",
                        trim($dateFilter[1]) . " 23:59:59"
                    ]);
                } else {
                    $agents->whereBetween('created_at', [
                        trim($dateFilter[0]) . " 00:00:00",
                        trim($dateFilter[0]) . " 23:59:59"
                    ]);
                }
            }
            if (!empty($request->get('geo_filter'))) {
                $geo_id = $request->get('geo_filter');
                $agents->whereHas('geoFence', function ($q) use ($geo_id) {
                    $q->where('geo_id', $geo_id);
                });
            }
            if (!empty($request->get('search'))) {
                $search = $request->get('search');
                $agents->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });
            }
            if (!empty($request->get('tag_filter'))) {
                $tag_id = $request->get('tag_filter');
                $agents->whereHas('tags', function ($q) use ($tag_id) {
                    $q->where('tag_id', $tag_id);
                });
            }
            if ($user->is_superadmin == 0 && $user->all_team_access == 0 && $user->manager_type == 0) {
                $agents = $agents->whereHas('team.permissionToManager', function ($query) use ($user) {
                    $query->where('sub_admin_id', $user->id);
                });
            } else if ($user->is_superadmin == 0 && $user->manager_type == 1) {
                $agents = $agents->whereHas('warehouseAgent', function ($query) use ($managerWarehousesIds) {
                    $query->whereIn('warehouses.id', $managerWarehousesIds);
                });
            }
            if ($request->status == 2) {
                $agents = $agents->withTrashed()
                    ->where('is_approved', $request->status)
                    ->orWhere(function ($query) {
                        return $query->where('is_approved', 1)
                            ->where('deleted_at', '!=', NULL);
                    })
                    ->orderBy('id', 'desc');
            } else {
                $agents = $agents->where('is_approved', $request->status)->orderBy('id', 'desc');
            }
            $page = request()->input('page', 1);
            $perPage = 10;
            $agents = $agents->paginate($perPage, ['*'], 'page', $page);

            // You can also pass the pagination links to the view
            $paginationLinks = $agents->links()->toHtml() ?? "";

            $returnHTML['html'] = view('agent/agent-index')->with(['agents' => $agents, 'timezone' => $timezone])->render();

            $returnHTML['pagination'] = $paginationLinks;


            return response()->json($returnHTML, 200);
        } catch (Exception $e) {

            \Log::info($e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new AgentsExport(), 'agents.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Validation method for agents data
     */
    protected function validator(array $data)
    {
        $full_number = '';
        if (isset($data['country_code']) && !empty($data['country_code']) && isset($data['phone_number']) && !empty($data['phone_number']))
            $full_number = '+' . $data['country_code'] . $data['phone_number'];

        $data['phone_number'] = '+' . $data['country_code'] . $data['phone_number'];
        return Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'type' => [
                'required'
            ],
            // 'vehicle_type_id' => ['required'],
            'team_id' => [
                'required'
            ],
            // 'make_model' => ['required'],
            // 'plate_number' => ['required'],
            'phone_number' => [
                'required',
                'min:6',
                'max:15',
                Rule::unique('agents')->where(function ($query) use ($full_number) {
                    return $query->where(['phone_number' => $full_number, 'deleted_at' => NULL]);
                })
            ],
            // 'color' => ['required'],
            'profile_picture' => [
                'mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $domain = '')
    {
        $validator = $this->validator($request->all())
            ->validate();
        $getFileName = null;

        $newtag = explode(",", $request->tags);
        $tag_id = [];
        foreach ($newtag as $key => $value) {
            if (!empty($value)) {
                $check = TagsForAgent::firstOrCreate([
                    'name' => $value
                ]);
                array_push($tag_id, $check->id);
            }
        }

        // Handle File Upload
        if ($request->hasFile('profile_picture')) {
            $folder = str_pad(Auth::user()->code, 8, '0', STR_PAD_LEFT);
            $folder = 'client_' . $folder;
            $file = $request->file('profile_picture');
            if (is_azureEnable()) {
                $getFileName = uploadAzureImage($file);
            } else {
                $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/agents' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $getFileName = $path;
            }
        }

        $data = [
            'name' => $request->name,
            'team_id' => $request->team_id == null ? $team_id = null : $request->team_id,
            'type' => $request->type,
            'vehicle_type_id' => $request->vehicle_type_id ?? null,
            'make_model' => $request->make_model,
            'type' => $request->type,
            'make_model' => $request->make_model ?? null,
            'plate_number' => $request->plate_number ?? null,
            'phone_number' => '+' . $request->country_code . $request->phone_number,
            'color' => $request->color ?? null,
            'profile_picture' => $getFileName != null ? $getFileName : 'assets/client_00000051/agents5fedb209f1eea.jpeg/Ec9WxFN1qAgIGdU2lCcatJN5F8UuFMyQvvb4Byar.jpg',
            'uid' => $request->uid ?? null,
            'is_approved' => 1
        ];

        $agent = Agent::create($data);
        $agent->tags()->sync($tag_id);
        if (checkTableExists('agent_warehouse')) {
            $warehouse_ids = $request->warehouse_id;
            if (!empty($warehouse_ids)) {
                $agent->warehouseAgent()->sync($warehouse_ids);
            }
        }

        $driver_registration_documents = DriverRegistrationDocument::get();

        foreach ($driver_registration_documents as $driver_registration_document) {
            $agent_docs = new AgentDocs();
            $name = str_replace(" ", "_", $driver_registration_document->name);
            if ($driver_registration_document->file_type != "Text" && $driver_registration_document->file_type != "selector" && $driver_registration_document->file_type != "Date") {
                if ($request->hasFile($name)) {
                    $folder = str_pad(Auth::user()->code, 8, '0', STR_PAD_LEFT);
                    $folder = 'client_' . $folder;
                    $file = $request->file($name);
                    if (is_azureEnable()) {
                        $getFileName = uploadAzureImage($file);
                    } else {
                        $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                        $s3filePath = '/assets/' . $folder . '/agents' . $file_name;
                        $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                        $getFileName = $path;
                    }
                }
                $agent_docs->file_name = $getFileName;
            } else {
                $agent_docs->file_name = $request->$name;
            }
            $agent_docs->agent_id = $agent->id;
            $agent_docs->file_type = $driver_registration_document->file_type;
            $agent_docs->document_id = $driver_registration_document->id;
            $agent_docs->label_name = $driver_registration_document->name;
            $agent_docs->save();
        }

        if ($agent->wasRecentlyCreated) {
            return response()->json([
                'status' => 'success',
                'message' => 'Thanks for signing up. We will get back to you shortly!',
                'data' => $agent
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($domain = '', $id)
    {
        //
        $agent = Agent::with([
            'tags'
        ])->where('id', $id)->first();

        $teams = Team::where('client_id', auth()->user()->code);
        if (Auth::user()->is_superadmin == 0 && Auth::user()->all_team_access == 0) {
            $teams = $teams->whereHas('permissionToManager', function ($query) {
                $query->where('sub_admin_id', Auth::user()->id);
            });
        }

        $teams = $teams->get();

        $tags = TagsForAgent::all();
        $uptag = [];
        foreach ($tags as $key => $value) {
            array_push($uptag, $value->name);
        }

        $tagIds = [];
        $returnHTML = '';
        if (!empty($agent)) {
            foreach ($agent->tags as $tag) {
                $tagIds[] = $tag->name;
            }
            $date = Date('Y-m-d H:i:s');

            $otp = Otp::where('phone', $agent->phone_number)->where('valid_till', '>=', $date)->first();
            if (isset($otp)) {
                $send_otp = $otp->opt;
            } else {
                $send_otp = __('View OTP after Logging in the ' . getAgentNomenclature() . ' App');
            }
            $agents_docs = AgentDocs::where('agent_id', $id)->get();
            $driver_registration_documents = DriverRegistrationDocument::get();

            $returnHTML = view('agent.form-show')->with([
                'agent' => $agent,
                'driver_registration_documents' => $driver_registration_documents,
                'teams' => $teams,
                'tags' => $uptag,
                'agent_docs' => $agents_docs,
                'tagIds' => $tagIds,
                'otp' => $send_otp
            ])->render();
        }
        return response()->json(array(
            'success' => true,
            'html' => $returnHTML
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($domain = '', $id)
    {
        $agent = Agent::with([
            'tags',
            'warehouseAgent'
        ])->where('id', $id)->first();

        $teams = Team::where('client_id', auth()->user()->code);
        if (Auth::user()->is_superadmin == 0 && Auth::user()->all_team_access == 0) {
            $teams = $teams->whereHas('permissionToManager', function ($query) {
                $query->where('sub_admin_id', Auth::user()->id);
            });
        }
        $teams = $teams->get();

        $tags = TagsForAgent::all();

        $uptag = [];
        foreach ($tags as $key => $value) {
            array_push($uptag, $value->name);
        }

        $tagIds = [];

        foreach ($agent->tags as $tag) {
            $tagIds[] = $tag->name;
        }
        $date = Date('Y-m-d H:i:s');

        $otp = Otp::where('phone', $agent->phone_number)->where('valid_till', '>=', $date)->first();
        if (isset($otp)) {
            $send_otp = $otp->opt;
        } else {
            $send_otp = __('View OTP after Logging in the ' . getAgentNomenclature() . ' App');
        }



        $agents_docs = AgentDocs::where('agent_id', $id)->get();
        $driver_registration_documents = DriverRegistrationDocument::with('driver_option')->get();

        $warehouses = Warehouse::all();

        $returnHTML = view('agent.form')->with([
            'agent' => $agent,
            'teams' => $teams,
            'tags' => $uptag,
            'tagIds' => $tagIds,
            'otp' => $send_otp,
            'driver_registration_documents' => $driver_registration_documents,
            'agent_docs' => $agents_docs,
            'warehouses' => $warehouses
        ])->render();

        return response()->json(array(
            'success' => true,
            'html' => $returnHTML
        ));
    }

    /**
     * Validation method for agent Update
     */
    protected function updateValidator(array $data, $id)
    {
        $full_number = '';
        $full_number = $data['phone_number'];

        return Validator::make($data, [
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'type' => [
                'required'
            ],
            // 'vehicle_type_id' => ['required'],
            'team_id' => [
                'required'
            ],
            // 'make_model' => ['required'],
            // 'plate_number' => ['required'],
            'phone_number' => [
                'required',
                'min:6',
                'max:15',
                Rule::unique('agents')->where(function ($query) use ($full_number, $id) {
                    return $query->where(['phone_number' => $full_number, 'deleted_at' => NULL])
                        ->where('id', '!=', $id);
                })
            ],
            // 'color' => ['required'],
            'profile_picture' => [
                'mimes:jpeg,png,jpg,gif,svg|max:2048'
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $domain = '', $id)
    {
        $validator = $this->updateValidator($request->all(), $id)
            ->validate();

        $agent = Agent::findOrFail($id);
        // if(isset($request->phone_number) && !empty($request->phone_number)){
        // $already = Agent::where('phone_number',$request->phone_number)->where('id','!=',$id)->count();
        // if($already > 0){
        // return response()->json([
        // 'status'=>'error',
        // 'message' => 'The Phone number is already exist!',
        // 'data' => []
        // ]);
        // }

        // }
        $getFileName = $agent->profile_picture;

        $newtag = explode(",", $request->tags);

        $tag_id = [];

        foreach ($newtag as $key => $value) {
            if (!empty($value)) {
                $check = TagsForAgent::firstOrCreate([
                    'name' => $value
                ]);
                array_push($tag_id, $check->id);
            }
        }

        // handal image upload
        if ($request->hasFile('profile_picture')) {
            $folder = str_pad(Auth::user()->id, 8, '0', STR_PAD_LEFT);
            $folder = 'client_' . $folder;
            $file = $request->file('profile_picture');
            if (is_azureEnable()) {
                $getFileName = uploadAzureImage($file);
            } else {

                $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                $s3filePath = '/assets/' . $folder . '/agents' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $getFileName = $path;
            }
        }

        foreach ($request->only('name', 'type', 'vehicle_type_id', 'make_model', 'plate_number', 'phone_number', 'color', 'uid') as $key => $value) {
            $agent->{$key} = $value;
        }
        $agent->team_id = $request->team_id;
        $agent->profile_picture = $getFileName;
        $agent->save();

        $agent->tags()->sync($tag_id);

        if (checkTableExists('agent_warehouse')) {
            $warehouse_ids = $request->warehouse_id;
            if (!empty($warehouse_ids)) {
                $agent->warehouseAgent()->sync($warehouse_ids);
            }
        }

        $driver_registration_documents = DriverRegistrationDocument::get();
        foreach ($driver_registration_documents as $driver_registration_document) {
            $name = str_replace(" ", "_", $driver_registration_document->name);
            if ($driver_registration_document->file_type != "Text" && $driver_registration_document->file_type != "selector" && $driver_registration_document->file_type != "Date") {
                if ($request->hasFile($name)) {
                    $folder = str_pad(Auth::user()->code, 8, '0', STR_PAD_LEFT);
                    $folder = 'client_' . $folder;
                    $file = $request->file($name);
                    if (is_azureEnable()) {
                        $getFileName = uploadAzureImage($file);
                    } else {
                        $file_name = uniqid() . '.' . $file->getClientOriginalExtension();
                        $s3filePath = '/assets/' . $folder . '/agents' . $file_name;
                        $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                        $getFileName = $path;
                    }
                    $agent_docs = AgentDocs::firstOrNew([
                        'agent_id' => $agent->id,
                        'label_name' => $driver_registration_document->name,
                        'document_id' => $driver_registration_document->id,
                        'file_type' => $driver_registration_document->file_type
                    ]);
                    $agent_docs->file_name = $getFileName;
                    $agent_docs->save();
                }
            } else {
                $agent_docs = AgentDocs::firstOrNew([
                    'agent_id' => $agent->id,
                    'label_name' => $driver_registration_document->name,
                    'document_id' => $driver_registration_document->id,
                    'file_type' => $driver_registration_document->file_type
                ]);
                $agent_docs->file_name = $request->$name;
                $agent_docs->save();
            }
        }

        if ($agent) {
            return response()->json([
                'status' => 'success',
                'message' => getAgentNomenclature() . ' updated Successfully!',
                'data' => $agent
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($domain = '', $id)
    {
        DriverGeo::where('driver_id', $id)->delete(); // i have to fix it latter
        $agent = Agent::withTrashed()->where('id', $id)->first();
        Agent::where('id', $agent->id)->update([
            'phone_number' => $agent->phone_number . '_' . $agent->id . "_D",
            'device_token' => '',
            'device_type' => '',
            'access_token' => '',
            'is_available' => 0
        ]);
        $agent->delete();
        Otp::where('phone', $agent->phone_number)->where('is_verified', 1)->delete();
        return redirect()->back()->with('success', __(getAgentNomenclature() . ' deleted successfully!'));
    }

    // ----------------------------------function modified by surendra singh-------------------------------//
    public function payreceive(Request $request, $domain = '')
    {
        try {
            $driver_id = $request->driver_id;
            $agent = Agent::where('id', $driver_id)->where('is_approved', 1)->first();
            $amount = $request->amount;
            $wallet = $agent->wallet;
            if ($amount > 0) {
                if ($request->payment_type == 1) {
                    $wallet->depositFloat($amount, [
                        'Wallet has been <b>Credited</b>'
                    ]);
                } elseif ($request->payment_type == 2) {
                    $final_balance = agentEarningManager::getAgentEarning($agent->id, 0);
                    if ($request->payment_from == 1) {
                        if ($amount > $agent->balanceFloat) {
                            return $this->error(__('Amount is greater than ' . getAgentNomenclature() . ' available funds'), 422);
                        }
                        $wallet->withdrawFloat($amount, [
                            'Wallet has been <b>Dedited</b>'
                        ]);
                    } else {
                        if ($amount > abs($final_balance)) {
                            return $this->error(__('Amount is greater than ' . getAgentNomenclature() . ' final balance'), 422);
                        } else {

                            $wallet->depositFloat($amount, [
                                'Wallet has been <b>Credited</b>'
                            ]);
                        }
                    }
                } else {
                    return $this->error(__('Invalid Data'), 422);
                }
                // $data = [
                //     'driver_id' => $request->driver_id,
                //     'cr' => $request->payment_type == 1 ? $request->amount : null,
                //     'dr' => $request->payment_type == 2 ? $request->amount : null,
                //     'payment_from' => $request->payment_from == 2 ? 1:0
                // ];
                // $agent = AgentPayment::create($data);
                return $this->success('', __('Payment is successfully completed'), 201);
            } else {
                return $this->error(__('Insufficient Amount'), 422);
            }
        } catch (Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    // ----------------------------------------------------------------------------------------//
    public function agentPayDetails($domain = '', $id)
    {
        $data = [];
        $agent = Agent::where('id', $id)->first();
        $wallet_balance = 0;
        if (isset($agent)) {
            if ($agent->wallet) {
                $wallet_balance = $agent->balanceFloat;
            }
            $cash = $agent->order->sum('cash_to_be_collected');
            $driver_cost = $agent->order->sum('driver_cost');
            $order = $agent->order->sum('order_cost');
            $credit = $agent->agentPayment->sum('cr');
            $debit = $agent->agentPayment->sum('dr');
            $final_balance = agentEarningManager::getAgentEarning($agent->id, 1);
        } else {
            $cash = 0;
            $order = 0;
            $driver_cost = 0;
            $credit = 0;
            $debit = 0;
            $final_balance = 0;
        }

        $data['cash_to_be_collected'] = $cash;
        $data['order_cost'] = $order;
        $data['driver_cost'] = $driver_cost;
        $data['credit'] = $credit;
        $data['debit'] = $debit;
        $data['final_balance'] = $final_balance;
        $data['wallet'] = $wallet_balance;

        return response()->json($data);
    }

    /* Change Agent approval status */
    public function approval_status(Request $request)
    {
        try {
            $agent_approval = Agent::find($request->id);
            $agent_approval->is_approved = $request->is_approved;
            $agent_approval->save();
            return response()->json([
                'status' => 1,
                'message' => 'Status change successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    /* Change Agent approval status */
    public function change_approval_status(Request $request)
    {
        try {
            $agent_approval = Agent::withTrashed()->find($request->id);
            $agent_approval->deleted_at = NULL;
            $agent_approval->is_approved = $request->status;
            $agent_approval->is_available = isset($request->status) && $request->status == 1 ? $agent_approval->is_available : $request->status;
            $agent_approval->save();

            // Updtae log also
            $is_active = ($request->status == 1) ? 1 : 0;
            AgentLog::where('agent_id', $request->id)->update([
                'is_active' => $is_active
            ]);
            $slug = ($request->status == 1) ? 'driver-accepted' : 'driver-rejected';
            // $sms_body = AgentSmsTemplate::where('slug', $slug)->first();
            $keyData = [];
            $sms_body = sendSmsTemplate($slug, $keyData);

            if (!empty($sms_body) && !empty($sms_body['body'])) {
                $send = $this->sendSmsNew($agent_approval->phone_number, $sms_body)->getData();
            }

            $agents = Agent::withTrashed()->get();
            $agentsCount = count($agents);
            $employeesCount = count($agents->where('type', 'Employee'));
            $freelancerCount = count($agents->where('type', 'Freelancer'));
            $agentActive = count($agents->where('is_activated', 1));
            $agentInActive = count($agents->where('is_activated', 0));
            $agentIsAvailable = count($agents->where('is_available', 1));
            $agentNotAvailable = count($agents->where('is_available', 0));
            $agentIsApproved = count($agents->where('is_approved', 1));
            $agentNotApproved = count($agents->where('is_approved', 0));
            $agentRejected = count($agents->where('is_approved', 2)->whereNull('deleted_at')) + count($agents->whereIn('is_approved', [0, 1, 2])->whereNotNull('deleted_at'));

            return response()->json([
                'status' => 1,
                'agentIsApproved' => $agentIsApproved,
                'agentNotApproved' => $agentNotApproved,
                'agentRejected' => $agentRejected,
                'message' => 'Status change successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function driverList(Request $request)
    {
        $agents = Agent::select('id', 'name');
        if ((strlen($request->term) > 0)) {
            $agents = $agents->where('name', 'like', '%' . $request->term . '%')->select('id', 'name');
        }
        $agents = $agents->get();
        return response()->json($agents);
    }

    public function search(Request $request, $domain = '')
    {
        // ->limit(10)
        $search = $request->search;
        $vehicle_type = $request->has('vehicle_type') ? $request->vehicle_type : '';
        $drivers = Agent::orderby('name', 'asc')->select('id', 'name', 'phone_number')
            ->where('is_approved', 1);

        if ($search) {
            $drivers =    $drivers->where('name', 'like', '%' . $search . '%');
        }
        if ($vehicle_type) {
            $drivers =    $drivers->whereIn('vehicle_type_id', $vehicle_type);
        }
        $drivers =    $drivers->get();
        $response = array();
        foreach ($drivers as $driver) {
            $response[] = array(
                "value" => $driver->id,
                "label" => $driver->name . '(' . $driver->phone_number . ')'
            );
        }

        return response()->json($response);
    }

    protected function sendSms2($to, $body)
    {
        try {
            $client_preference = getClientPreferenceDetail();

            if ($client_preference->sms_provider == 1) {
                $credentials = json_decode($client_preference->sms_credentials);
                $sms_key = (isset($credentials->sms_key)) ? $credentials->sms_key : $client_preference->sms_provider_key_1;
                $sms_secret = (isset($credentials->sms_secret)) ? $credentials->sms_secret : $client_preference->sms_provider_key_2;
                $sms_from = (isset($credentials->sms_from)) ? $credentials->sms_from : $client_preference->sms_provider_number;

                $client = new TwilioClient($sms_key, $sms_secret);
                $client->messages->create($to, [
                    'from' => $sms_from,
                    'body' => $body
                ]);
            } elseif ($client_preference->sms_provider == 2) // for mtalkz gateway
            {
                $credentials = json_decode($client_preference->sms_credentials);
                $send = $this->mTalkz_sms($to, $body, $credentials);
            } elseif ($client_preference->sms_provider == 3) // for mazinhost gateway
            {
                $credentials = json_decode($client_preference->sms_credentials);
                $send = $this->mazinhost_sms($to, $body, $credentials);
            } elseif ($client_preference->sms_provider == 4) // for unifonic gateway
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->unifonic($to, $body, $crendentials);
            } elseif ($client_preference->sms_provider == 5) // for arkesel_sms gateway
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->arkesel_sms($to, $body, $crendentials);
                if (isset($send->code) && $send->code != 'ok') {
                    return $this->error($send->message, 404);
                }
            } elseif ($client_preference->sms_provider == 6) //for Vonage (nexmo)
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->vonage_sms($to, $body, $crendentials);
            } elseif ($client_preference->sms_provider == 7) // for SMS Partner France
            {
                $crendentials = json_decode($client_preference->sms_credentials);
                $send = $this->sms_partner_gateway($to, $body, $crendentials);
                if (isset($send->code) && $send->code != 200) {
                    return $this->error("SMS could not be deliver. Please check sms gateway configurations", 404);
                }
            } else {
                $credentials = json_decode($client_preference->sms_credentials);
                $sms_key = (isset($credentials->sms_key)) ? $credentials->sms_key : $client_preference->sms_provider_key_1;
                $sms_secret = (isset($credentials->sms_secret)) ? $credentials->sms_secret : $client_preference->sms_provider_key_2;
                $sms_from = (isset($credentials->sms_from)) ? $credentials->sms_from : $client_preference->sms_provider_number;
                $client = new TwilioClient($sms_key, $sms_secret);
                $client->messages->create($to, [
                    'from' => $sms_from,
                    'body' => $body
                ]);
            }
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            // return $this->error(__('Provider service is not configured. Please contact administration.'), 404);
            return $this->error($e->getMessage(), $e->getCode());
        }
        return $this->success([], __('An otp has been sent to your phone. Please check.'), 200);
    }

    public function refreshWalletbalance(Request $request, $domain = '', $id = '')
    {
        if (!empty($id)) {
            $user = Agent::find($id);
            if ($user) {
                if ($user->wallet) {
                    $user->wallet->refreshBalance();
                }
            }
        }

        echo '<pre>';
        echo 'Successfully Done';
        echo '</pre>';
    }
}
