<?php
namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Model\ {
    Agent,
    AgentAttendence
};

class AgentAttendenceController extends BaseController
{
    use ApiResponser;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getTodayAttendance(Request $request)
    {
        $user = Auth::user();
        try {
            $attendanceData = AgentAttendence::where('agent_id', $user->id)->where('start_date', $request->date)->first();
            $getAdditionalPreference = getAdditionalPreference([
                'is_attendence',
                'idle_time'
            ]);
            $idleTime = isset($getAdditionalPreference['idle_time']) ?$getAdditionalPreference['idle_time']: '';
            if (! empty($attendanceData)) {
                $attendanceData['in_status'] = 1;
                $attendanceData['idle_time'] = $idleTime;
                return response()->json([
                    'data' => $attendanceData
                ]);
            } else {
                $attendanceData['agent_id'] = $user->id;
                $attendanceData['start_date'] = '';
                $attendanceData['start_time'] = '';
                $attendanceData['end_date'] = '';
                $attendanceData['end_time'] = '';
                $attendanceData['in_status'] = 0;
                $attendanceData['total'] = "00:00";
                $attendanceData['idle_time'] = $idleTime;
                return response()->json([
                    'data' => $attendanceData
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No record found.'
            ], 404);
        }
    }

    public function getAttendanceHistory(Request $request)
    {
        $user = Auth::user();
        try {
            $limit = $request->has('limit') ? $request->limit : 12;
            $page = $request->has('page') ? $request->page : 1;
            $attendanceData = AgentAttendence::select('*')->where('agent_id', $user->id)
                ->orderBy('id', 'DESC')
                ->paginate($limit, $page);

            if (! empty($attendanceData)) {
                return $this->success(
                    $attendanceData,
                    'Data found successfully'
                    );
            } else {
                return response()->json([
                    'message' => __('Data not found!'),
                    'data' => []
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No record found.'
            ], 404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            $rules = [
                'start_time' => [
                    'required'
                ],
                'agent_id' => [
                    'required'
                ],
                'start_date' => [
                    'required'
                ]
            ];
            $customMessages = [];

            $validator = Validator::make($request->all(), $rules, $customMessages);

            $data = [];
            if ($validator->fails()) {
                return $this->error($validator->errors()
                    ->first(), 422);
            }

            if ($request->start_date != date('Y-m-d')) {
                return $this->error(__('Cannot in for past and coming date'), 400);
            }
            $agent = AgentAttendence::where([
                'agent_id' => $request->agent_id,
                'start_date' => $request->start_date
            ])->first();
            if ($agent) {
                if (! empty($agent->end_date)) {
                    return $this->error(__('Shift for today is completed'), 400);
                } else {
                    return $this->error(__('Agent is already in for the day'), 400);
                }
            }

            $data = [
                'start_time' => $request->start_time,
                'agent_id' => $request->agent_id,
                'start_date' => $request->start_date
            ];

            $agent = AgentAttendence::create($data);
            return response()->json([
                'data' => $agent,
                'status' => 200,
                'message' => "Agent in successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AgentAttendence $id)
    {
        try {

            $rules = [
                'end_time' => [
                    'required'
                ],
                'end_date' => [
                    'required'
                ]
            ];
            $customMessages = [];

            $validator = Validator::make($request->all(), $rules, $customMessages);

            $data = [];
            if ($validator->fails()) {
                return $this->error($validator->errors()
                    ->first(), 422);
            }
            $agentAttendence = AgentAttendence::where([
                'agent_id' => $request->id,
                'start_date' => $request->end_date
            ])->first();
            if (empty($agentAttendence)) {
                return $this->error(__('Attendence data not found'), 400);
            }
            if (! empty($agentAttendence->end_date)) {
                return $this->error(__('Agent is already out for the day'), 400);
            }
            $data = [
                'end_time' => $request->end_time,
                'end_date' => $request->end_date
            ];

            $agentAttendence->update($data);
            return response()->json([
                'data' => $agentAttendence,
                'status' => 200,
                'message' => "Agent out successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
