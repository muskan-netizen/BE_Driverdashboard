<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name=”format-detection” content=”telephone=no”>
    {{-- <link rel="shortcut icon" type="image/x-icon" href="{{$favicon ?? asset('assets/images/favicon.ico') }}"> --}}
    <!-- Bootstrap CSS -->
    <!-- <link rel="stylesheet" href="css/fontawesome.css"> -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('tracking/css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('tracking/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('tracking/css/responsive.css') }}">
    <title> Order Tracking</title>
</head>
@php
$task_type_array = [__('Pickup'), __('Drop-Off'), __('Appointment')];
@endphp

<body>

    <!--location Area -->
    <section class="location_wrapper py-xl-5 position-relative d-lg-flex align-items-lg-center">
        <div class="container px-0">



            <div class="row no-gutters">
                @if(@$formData && count($formData) > 0)
                @foreach($formData as $form_single)
                <div class="col-sm-6">
                    <span>{{$form_single->attribute->title}} : </span>
                    <span>
                        @if($form_single->attribute->type == 1)
                        {{$form_single->attributeOption->title}}


                        @elseif($form_single->attribute->type == 2)
                       
                        @elseif($form_single->attribute->type == 3)

                        {{$form_single->attributeOption->title}}
                       
                        @elseif($form_single->attribute->type == 4)
                       

                        @elseif($form_single->attribute->type == 5)
                        

                        @elseif($form_single->attribute->type == 6)
                        <img src="{{ 'https://imgproxy.royodispatch.com/insecure/fit/300/100/sm/0/plain/' . Storage::disk('s3')->url($form_single->key_value ?? 'assets/client_00000051/agents605b6deb82d1b.png/XY5GF0B3rXvZlucZMiRQjGBQaWSFhcaIpIM5Jzlv.jpg') }}" alt="">
                        @endif


                        {{--$influencer_user['kyc']['account_name']--}}</span>
                </div>
                @endforeach
                @endif

            </div>
        </div>
        </div>
    </section>


    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="{{ asset('tracking/js/jquery-min.js') }}"></script>

    <script src="{{ asset('tracking/js/common.js') }}"></script>
    <script src="{{ asset('tracking/js/bootstrap.min.js') }}"></script>

    <script>

    </script>
</body>

</html>