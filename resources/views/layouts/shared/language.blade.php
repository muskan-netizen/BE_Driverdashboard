
{{-- 
* it's use for get translation in js file
* @Author  Mr Harbans singh 
--}}
@php
$lang            = config('app.locale');
$langFile        = resource_path('lang/' . $lang . '.json');

$langFileString  = "";
if (file_exists($langFile)) {
    $langFileString  = file_get_contents($langFile, $lang);
} 

@endphp
@section('variables')
@section('headerJs')

<script src="{{ asset('js/lang/langTranslation.js') }}"></script>
<script>
    var LangObjectJS = <?php  echo @$langFileString  ?>
</script>
@endsection
