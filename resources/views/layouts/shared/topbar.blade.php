<!-- Topbar Start -->
@php
$clientData = \App\Model\Client::select('id', 'logo','custom_domain','code')->with('getPreference')->where('id', '>', 0)->first();
@endphp
<div class="navbar-custom">
    <div class="container-fluid">
        <ul class="list-unstyled topnav-menu float-right mb-0 d-flex align-items-center">

            {{-- <li class="d-none d-lg-block">
                <form class="app-search">
                    <div class="app-search-box dropdown">
                        <!-- <div class="input-group">
                            <input type="search" class="form-control" placeholder="Search..." id="top-search">
                            <div class="input-group-append">
                                <button class="btn" type="submit">
                                    <i class="fe-search"></i>
                                </button>
                            </div>
                        </div> -->
                        <!-- <div class="dropdown-menu dropdown-lg" id="search-dropdown">

                            <div class="dropdown-header noti-title">
                                <h5 class="text-overflow mb-2">Found 22 results</h5>
                            </div>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="fe-home mr-1"></i>
                                <span>Analytics Report</span>
                            </a>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="fe-aperture mr-1"></i>
                                <span>How can I help you?</span>
                            </a>


                            <a href="javascript:void(0);" class="dropdown-item notify-item">
                                <i class="fe-settings mr-1"></i>
                                <span>User profile settings</span>
                            </a>


                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow mb-2 text-uppercase">Users</h6>
                            </div>

                            <div class="notification-list">

                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <div class="media">
                                        <img class="d-flex mr-2 rounded-circle" src="{{ asset('assets/images/users/user-2.jpg') }}" alt="Generic placeholder image" height="32">
                                        <div class="media-body">
                                            <h5 class="m-0 font-14">Erwin E. Brown</h5>
                                            <span class="font-12 mb-0">UI Designer</span>
                                        </div>
                                    </div>
                                </a>


                                <a href="javascript:void(0);" class="dropdown-item notify-item">
                                    <div class="media">
                                        <img class="d-flex mr-2 rounded-circle" src="{{ asset('assets/images/users/user-5.jpg') }}" alt="Generic placeholder image" height="32">
                                        <div class="media-body">
                                            <h5 class="m-0 font-14">Jacob Deo</h5>
                                            <span class="font-12 mb-0">Developer</span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div> -->
                    </div>
                </form>
            </li> --}}

            <li class="dropdown d-none">
                <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="dropdown" href="#"
                    role="button" aria-haspopup="false" aria-expanded="false">
                    <i class="fe-search noti-icon"></i>
                </a>
                <div class="dropdown-menu dropdown-lg dropdown-menu-right p-0">
                    <form class="p-3">
                        <input type="text" class="form-control" placeholder="{{__('Search')}} ..." aria-label="{{__("Recipient's username")}}">
                    </form>
                </div>
            </li>
                    <?php
                        $allowed = [];
                        if(Auth::user()->is_superadmin == 0){
                           // if(Session::get('usertype') == 'manager'){
                                foreach (Auth::user()->getAllPermissions as $key => $value) {
                                    array_push($allowed,$value->permission->name);
                                 }
                           // }
                        }else{
                            array_push($allowed,'99999');
                        }
                        ?>
        <li>
            <div class="spinner-border text-blue header_spinner mr-2" role="status"></div>
        </li>
        <li class="alToggleSwitch">
            <label class="altoggle">
                <input type="checkbox" class="admin_panel_theme" {{$clientData->getPreference->theme == "dark" ? 'checked' : ''}}>
                <div class="toggle__bg">
                    <div class="toggle__sphere">
                        <div class="toggle__sphere-bg">
                        </div>
                        <div class="toggle__sphere-overlay"></div>
                    </div>
                </div>
            </label>
        </li>
        @if(in_array('Add Route',$allowed) || Auth::user()->is_superadmin == 1)
            
         @php
                    $warehouse_mode = checkWarehouseMode();
                @endphp
          
     @if(!empty($warehouseMode->show_inventory_module) && $warehouseMode->show_inventory_module == 0)
            <li class="d-lg-inline-block" >
                <a class="nav-link" href="#">   <!-- addTaskModalHeader -->
                    <button type="button" class="btn btn-blue waves-effect waves-light addTaskModalHeader klklkl" data-toggle="modal" data-target="" data-backdrop="static" title="{{__('Add Route')}}" data-keyboard="false"><span><i class="mdi mdi-plus-circle mr-1"></i> <span class="mobile_view_btn">{{__('Add Route')}}</span></span></button>
                </a>
            </li>
            @else
          
            <li class="d-lg-inline-block" >
                <a class="nav-link" href="#">   <!-- addTaskModalHeader -->
                    <button type="button" class="btn btn-blue waves-effect waves-light  klklkl" data-toggle="modal" data-target="#addRouteModal" id="route-btn" data-backdrop="static" title="{{__('Add Route')}}" data-keyboard="false"><span><i class="mdi mdi-plus-circle mr-1"></i><span class="mobile_view_btn">{{__('Add Route')}}</span></span></button>
                </a>
            </li>
           @endif
        @endif

            @php
            $applocale = 'en';
            if(session()->has('applocale')){
                $applocale = session()->get('applocale');
            }
            @endphp


            {{-- Languages --}}
            {{-- @php print_r(Session::all()); @endphp --}}
            <li class="dropdown d-xl-block">
                <a class="nav-link dropdown-toggle waves-effect waves-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                <i class="fa fa-language" aria-hidden="true"></i>
                    <span> Language </span>
                    {{session()->get('applocale')}}
                    <i class="mdi mdi-chevron-down"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="/switch/language?lang=ar" class="dropdown-item" langid="1">Arabic</a>
                    <a href="/switch/language?lang=zh" class="dropdown-item" langid="1">Chinese</a>
                    <a href="/switch/language?lang=en" class="dropdown-item" langid="1">English</a>
                    <a href="/switch/language?lang=fr" class="dropdown-item" langid="1">French</a>
                    <a href="/switch/language?lang=hi" class="dropdown-item" langid="1">Hindi</a>
                    <a href="/switch/language?lang=it" class="dropdown-item" langid="1">Italian</a>
                    <a href="/switch/language?lang=fa" class="dropdown-item" langid="1">Persian</a>
                    <a href="/switch/language?lang=ru" class="dropdown-item" langid="1">Russian</a>
                    <a href="/switch/language?lang=es" class="dropdown-item" langid="1">Spanish</a>
                    <a href="/switch/language?lang=sv" class="dropdown-item" langid="1">Swedish</a>
                    <a href="/switch/language?lang=tr" class="dropdown-item" langid="1">Turkish</a>
                    <a href="/switch/language?lang=vi" class="dropdown-item" langid="1">Vietnamese</a>
                    <div class="dropdown-divider"></div>
                </div>
            </li>

            <li class="dropdown d-none d-lg-inline-block">
                <a class="nav-link dropdown-toggle arrow-none waves-effect waves-light" data-toggle="fullscreen"
                    href="#">
                    <i class="fe-maximize noti-icon"></i>
                </a>
            </li>
            
           

            <li class="dropdown notification-list topbar-dropdown">
                <a class="nav-link dropdown-toggle nav-user mr-0 waves-effect waves-light" data-toggle="dropdown"
                    href="#" role="button" aria-haspopup="false" aria-expanded="false">

                    <span class="pro-user-name ml-1">
                        {{ auth()->user()->name }} <i class="mdi mdi-chevron-down"></i>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right profile-dropdown ">

                    <div class="dropdown-header noti-title">
                        <h6 class="text-overflow m-0">{{__('Welcome')}} !</h6>
                    </div>


                    <a href="{{ route('profile.index') }}" class="dropdown-item notify-item">
                        <i class="fe-user"></i>
                        <span>{{__('My Account')}}</span>
                    </a>


                    <!-- <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-lock"></i>
                        <span>Lock Screen</span>
                    </a> -->

                    <div class="dropdown-divider"></div>


                    {{-- <a href="javascript:void(0);" class="dropdown-item notify-item">
                        <i class="fe-log-out"></i>
                        <span>Logout</span>
                    </a>
                    --}}
                    <a class="dropdown-item notify-item" href="{{ route('client.logout') }}" > 
                        {{-- onclick="event.preventDefault(); document.getElementById('logout-form').submit();"> --}}
                        <i class="fe-log-out"></i>
                        <span>{{__('Logout')}}</span>

                    </a>

                    {{-- <form id="logout-form" action="{{ route('client.logout') }}" method="GET">
                        @csrf
                    </form> --}}

                </div>
            </li>


            <!-- <li class="dropdown notification-list">
                <a href="javascript:void(0);" class="nav-link right-bar-toggle waves-effect waves-light">
                    <i class="fe-settings noti-icon"></i>
                </a>
            </li> -->

        </ul>

        <!-- LOGO -->
        <div class="logo-box">
            <?php

            //echo $ur = Storage::disk('s3')->url(Auth::user()->logo);

           //$image = Phumbor::url(''.URL::to('/').'images/users/user-1.jpg')->fitIn(90,50);

            if(isset(Auth::user()->logo)){

               /* $client = Storage::disk('s3')->getDriver()->getAdapter()->getClient();
                $bucket = Config::get('filesystems.disks.s3.bucket');

                $command = $client->getCommand('GetObject', [
                    'Bucket' => $bucket,
                    'Key' => Auth::user()->logo  // file name in s3 bucket which you want to access
                ]);
http://192.168.100.211:8888/unsafe/fit-in/90x50/https://royodelivery-assets.s3.us-west-2.amazonaws.com/assets/Clientlogo/x8EVJTlbXQU8ia2H6Dd4zpJQSl60I5jAZCzZ6dse.jpg
                $request = $client->createPresignedRequest($command, '+20 minutes');

                //$generate_url = $request->getUri();

                $image = (string)$request->getUri();*/

                //echo $url = Storage::disk('s3')->temporaryUrl(Auth::user()->logo , now()->addMinutes(5));
            }
            ?>
            @php
                    $clientPreference = \App\Model\ClientPreference::select('id', 'theme')->where('id', '>', 0)->first();

                    // $urlImg = URL::to('/').'images/users/user-1.jpg';
                    if(isset(Auth::user()->dark_logo) && $clientPreference->theme == 'dark'){
                        $urlImg = Storage::disk('s3')->url(Auth::user()->dark_logo);
                    }else if(isset(Auth::user()->logo)){
                        $urlImg = Storage::disk('s3')->url(Auth::user()->logo);
                    }
                    $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fit/300/100/sm/0/plain/';
                    $image = $imgproxyurl.$urlImg;

            @endphp
            <a href="{{ route('index') }}" class="logo logo-dark text-center">
                <span class="logo-sm">
                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="" height="22">
                    <!-- <span class="logo-lg-text-light">UBold</span> -->
                </span>
                <span class="logo-lg">
                    <img src="{{ asset('assets/images/logo-dark.png') }}" alt="" height="20">
                    <!-- <span class="logo-lg-text-light">U</span> -->
                </span>
            </a>

            <a href="{{ route('index') }}" class="logo logo-light text-center">
                <span class="logo-sm">
                    <img src="{{$image}}"
                        alt="" height="30">
                </span>
                <span class="logo-lg">
                    <img src="{{$image}}"
                        alt="" height="50">
                </span>
            </a>
        </div>

        <ul class="list-unstyled topnav-menu topnav-menu-left m-0">
            <li>
                <button id="shortclick" class="button-menu-mobile waves-effect waves-light">
                    <i class="fe-menu"></i>
                </button>
            </li>

            <li>
                <!-- Mobile menu toggle (Horizontal Layout)-->
                <a class="navbar-toggle nav-link" data-toggle="collapse" data-target="#topnav-menu-content">
                    <div class="lines">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </a>
                <!-- End mobile menu toggle-->
            </li>

            <!-- <li class="dropdown d-none d-xl-block">
                <a class="nav-link dropdown-toggle waves-effect waves-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    Create New
                    <i class="mdi mdi-chevron-down"></i>
                </a>
                <div class="dropdown-menu">

                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="fe-briefcase mr-1"></i>
                        <span>New Projects</span>
                    </a>


                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="fe-user mr-1"></i>
                        <span>Create Users</span>
                    </a>


                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="fe-bar-chart-line- mr-1"></i>
                        <span>Revenue Report</span>
                    </a>


                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="fe-settings mr-1"></i>
                        <span>Settings</span>
                    </a>

                    <div class="dropdown-divider"></div>


                    <a href="javascript:void(0);" class="dropdown-item">
                        <i class="fe-headphones mr-1"></i>
                        <span>Help & Support</span>
                    </a>

                </div>
            </li> -->

            <!-- <li class="dropdown dropdown-mega d-none d-xl-block">
                <a class="nav-link dropdown-toggle waves-effect waves-light" data-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    Mega Menu
                    <i class="mdi mdi-chevron-down"></i>
                </a>
                <div class="dropdown-menu dropdown-megamenu">
                    <div class="row">
                        <div class="col-sm-8">

                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="text-dark mt-0">UI Components</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        <li>
                                            <a href="javascript:void(0);">Widgets</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Nestable List</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Range Sliders</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Masonry Items</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Sweet Alerts</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Treeview Page</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Tour Page</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="col-md-4">
                                    <h5 class="text-dark mt-0">Applications</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        <li>
                                            <a href="javascript:void(0);">eCommerce Pages</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">CRM Pages</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Email</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Calendar</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Team Contacts</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Task Board</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Email Templates</a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="col-md-4">
                                    <h5 class="text-dark mt-0">Extra Pages</h5>
                                    <ul class="list-unstyled megamenu-list">
                                        <li>
                                            <a href="javascript:void(0);">Left Sidebar with User</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Menu Collapsed</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Small Left Sidebar</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">New Header Style</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Search Result</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Gallery Pages</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);">Maintenance & Coming Soon</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="text-center mt-3">
                                <h3 class="text-dark">Special Discount Sale!</h3>
                                <h4>Save up to 70% off.</h4>
                                <button class="btn btn-primary btn-rounded mt-3">Download Now</button>
                            </div>
                        </div>
                    </div>

                </div>
            </li> -->
        </ul>
        <div class="clearfix"></div>
    </div>
</div>
<!-- end Topbar
