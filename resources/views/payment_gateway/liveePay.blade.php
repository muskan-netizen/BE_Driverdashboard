
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <title>Document</title>
</head>
<body>
        <div class="d-flex justify-content-center mt-5">
            <form  action="{{ $apiUrl }}" method="POST"  class="d-flex flex-column gap-3 liveesForm" id="liveesForm">

                <input type="hidden" name="_" value="{{ $trade_key }}" class="form-control">
                <input type="hidden" name="__" value="{{ $resource_key }}" class="form-control">
                <input type="hidden" name="postURL" value="{{$postURL}}" class="form-control">
                <input type="hidden" name="amt2" readonly value="{{@$amount}}" class="form-control">
                <input type="hidden" name="currency" value="BOB" class="form-control">
                <input type="hidden" name="invno" value="   " class="form-control">
                <input type="hidden" name="name" value="{{ strtok($user->name, " ")}}" class="form-control">
                <input type="hidden" name="lastname" value="{{empty(substr(strstr(auth()->user()->name, " "), 1))?$user->name:substr(strstr(auth()->user()->name, " "), 1)}}" class="form-control">
                <input type="hidden" name="email" value="driver@yopmail.com" class="form-control">
                <input type="hidden" name="phone" value="{{$user->phone_number}}">
                <select name="pais" class="form-control invisible">
                <option value="BO">Bolivia</option>
                <option value="US">Estados Unidos</option>
                </select>
                <input type="hidden" name="ciudad" value="Santa Cruz de la Sierra" class="form-control">
                <select name="estado_lbl" class="form-select invisible">
                <option value="La Paz">La Paz</option>
                <option value="Santa Cruz">Santa Cruz</option>
                    <input type="submit" class="btn btn-primary invisible" value="submit">
                </form>
        </div>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
 document.getElementById("liveesForm").submit();
</script>
</body>
</html>
