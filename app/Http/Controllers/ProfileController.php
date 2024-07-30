<?php

namespace App\Http\Controllers;

use App\Model\Client;
use App\Model\ClientPreference;
use Illuminate\Http\Request;
use App\Model\Countries;
use App\Model\Timezone;
use \DateTimeZone;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Jobs\UpdatePassword;
use Auth;
use Illuminate\Support\Facades\Storage;
use DB;
use Log;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $client = Client::where('id', Auth::user()->id)->first();
        $countries = Countries::all();
        //$tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $tzlist = Timezone::get();
        $preference  = ClientPreference::where('client_id', Auth::user()->code)->first();
        return view('profile')->with(['client' => $client, 'preference' => $preference,'countries'=> $countries,'tzlist'=>$tzlist ]);
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $domain = '', $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_number' => ['required'],
            'company_name' => ['required'],
            'company_address' => ['required'],
        ]);
            
        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $user = Auth::user();

        $getClient = $user->logo;
        $getFileName = $getClient;

        // Handle File Upload

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            if(is_azureEnable())
            {
               $getFileName = uploadAzureImage($file);
            }else{
                
                $s3filePath = '/assets/Clientlogo';
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $getFileName = $path;
            }
        }

        if($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            if(is_azureEnable())
            {
                $faviconFileName= uploadAzureImage($file);
            }else{
                $s3filePath = '/assets/Clientfavicon';
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $faviconFileName = $path;
            }
        }
       

        $getDarkLogoFileName = $user->dark_logo;
        if ($request->hasFile('dark_logo')) {
            $file = $request->file('dark_logo');
            if(is_azureEnable())
            {
                $path = uploadAzureImage($file);
            }else{
                $s3filePath = '/assets/Clientlogo';
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
            }
            $getDarkLogoFileName = $path;
        }

        $alldata = [
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'country_id' => $request->country ? $request->country : null,
            'timezone' => $request->timezone ? $request->timezone : null,
            'logo' => $getFileName,
            'dark_logo' => $getDarkLogoFileName,
          //  'admin_signin_image' => $adminSigninImageFileName,
        ];
       
        if ($request->hasFile('admin_signin_image')) {
           $file = $request->file('admin_signin_image');
           if(is_azureEnable())
           {
               $path = uploadAzureImage($file);
           }else{
            $s3filePath = '/assets/adminSigninImage';
            $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
           }
           $alldata['admin_signin_image'] = $path;
        }
        //echo $request->timezone; die;
        if($user->is_superadmin == 1){
            $client = Client::where('code', $id)->where('id', $user->id)->update($alldata);

            $preference = ClientPreference::where('client_id', Auth::user()->code)->first();
            if(isset($faviconFileName)){
                $preference->favicon = $faviconFileName;
            }
            $preference->save();
        }else{
            $data = [
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'company_address' => $request->company_address
            ];
            $client = Client::where('code', $user->id.'_'.$id)->where('id', $user->id)->update($data);
        }

        $password = null;
        $this->dispatchNow(new UpdatePassword($password, $alldata));
  
        return redirect()->back()->with('success', 'Profile Updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
