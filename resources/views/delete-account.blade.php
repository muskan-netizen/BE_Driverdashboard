@php
     $image = App\Model\Client::first();
     $deleteAccountPhone = session('delete_account_phone');
     $deleteAccountVerified = session('delete_account_verified', false);
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.shared.title-meta', ['title' => "Delete Account"])

        @include('layouts.shared.head-css')
        @if(is_azureEnable())
           
            <style>
            .authentication-bg-pattern {
                background-image: url("{{ !empty($image->admin_signin_image) ? getAzureUrl().$image->admin_signin_image : ''  }}") !important;
            }
    </style>
        @else
        <style>
            .authentication-bg-pattern {
                background-image: url("{{ !empty($image->admin_signin_image) ? Storage::disk('s3')->url($image->admin_signin_image) : ''  }}") !important;
            }
    </style>
        @endif
       
    </head>

    <body class="authentication-bg authentication-bg-pattern">        
        @php
         $subdomain = explode('.',$_SERVER['HTTP_HOST'])[0];           
        @endphp
        

        <div class="account-pages mt-5 mb-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6 col-xl-5">
                        <div class="card bg-pattern">

                            <div class="card-body p-4">
                                        @php
                                       
                                            if(Cache::get('clientdetails')){
                                                $image = Cache::get('clientdetails');
                                                
                                                $pic = $image->logo;
                                                $name = 'Royo';
                                            }else {
                                                $name = "Royo";
                                                $pic = 'assets/Clientlogo/l8DdubW92ccY2n4xKriEUU7cKthSXrjgrJIZ6rvs.png';
                                            }
                                        @endphp
                                <div class="text-center w-75 m-auto">
                                    <div class="auth-logo">
                                        <a href="{{route('index')}}" class="logo logo-dark text-center">
                                            <span class="logo-lg">
                                                    @if(is_azureEnable())
                                                        
                                                <img src="{{'https://imgproxy.royodispatch.com/insecure/fit/90/90/sm/0/plain/'.getAzureUrl().$pic}}" alt="" height="40">

                                                    @else
                                                <img src="{{'https://imgproxy.royodispatch.com/insecure/fit/90/90/sm/0/plain/'.Storage::disk('s3')->url($pic)}}" alt="" height="40">
                                                        
                                                    @endif
                                            </span>
                                        </a>
                                        
                                    </div>
                                    <p class="text-muted mb-4 mt-3">{{ __('Enter the agent phone number, verify OTP, and then permanently delete the account.') }}</p>
                                </div>

                                @if(session('Success'))
                                    <div class="alert alert-success">{{ session('Success') }}</div>
                                @endif

                                @if(session('Error'))
                                    <div class="alert alert-danger">{{ session('Error') }}</div>
                                @endif

                                @if($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0 pl-3">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(empty($deleteAccountPhone))
                                    <form action="{{ route('delete-account.send-otp') }}" method="POST" class="mb-4">
                                        @csrf
                                        <div class="form-group mb-3">
                                            <label for="phone_number">{{ __('Agent Phone Number') }}</label>
                                            <input class="form-control" type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" placeholder="{{ __('Enter phone number') }}" required />
                                        </div>
                                        <div class="form-group mb-0 text-center">
                                            <button class="btn btn-primary btn-block" type="submit">{{ __('Send OTP') }}</button>
                                        </div>
                                    </form>
                                @elseif(!$deleteAccountVerified)
                                    <form action="{{ route('delete-account.verify-otp') }}" method="POST" class="mb-4">
                                        @csrf
                                        <input type="hidden" name="phone_number" value="{{ $deleteAccountPhone }}">
                                        <div class="form-group mb-3">
                                            <label>{{ __('Agent Phone Number') }}</label>
                                            <input class="form-control" type="text" value="{{ $deleteAccountPhone }}" readonly />
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="otp">{{ __('OTP') }}</label>
                                            <input class="form-control" type="text" id="otp" name="otp" placeholder="{{ __('Enter OTP') }}" required />
                                        </div>
                                        <div class="form-group mb-0 text-center">
                                            <button class="btn btn-success btn-block" type="submit">{{ __('Verify Agent') }}</button>
                                        </div>
                                    </form>
                                @else
                                    <form id="deleteAccountForm" action="{{ route('delete-account.confirm') }}" method="POST" data-confirm="{{ __('Are you sure you want to delete this agent account? This action cannot be undone.') }}">
                                        @csrf
                                        <input type="hidden" name="phone_number" value="{{ $deleteAccountPhone }}">
                                        <div class="form-group mb-3">
                                            <label>{{ __('Verified Phone Number') }}</label>
                                            <input class="form-control" type="text" value="{{ $deleteAccountPhone }}" readonly />
                                        </div>
                                        <div class="form-group mb-0 text-center">
                                            <button class="btn btn-danger btn-block" type="submit">
                                                {{ __('Delete Agent Account') }}
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <footer class="footer footer-alt">
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
            <script>document.write(new Date().getFullYear())</script> &copy; All rights reserved by <a href="#" class="text-white-50">{{$image->name??__('Royo')}}</a> 
        </footer>

        @include('layouts.shared.footer-script')

        <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
        
        <script type="text/javascript">
            $(document).on('submit', '#deleteAccountForm', function(e) {
                e.preventDefault();
                var form = this;

                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: $(form).data('confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Yes, delete it') }}",
                    cancelButtonText: "{{ __('Cancel') }}",
                    reverseButtons: true
                }).then(function(result) {
                    if (result && (result.isConfirmed || result.value === true)) {
                        form.submit();
                    }
                });
            });
        </script>
    </body>
    
</html>