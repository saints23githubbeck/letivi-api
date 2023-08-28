<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.1.2/socket.io.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
     {{-- <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}"> --}}

</head>

<body class="container p-4">
    {{-- <h5> --}}
    @if (Session::has('success'))
        <div class="alert alert-success">
            {{ Session::get('success') }}
        </div>
    @endif

    @if (Session::has('error'))
        <div class="alert alert-danger">
            {{ Session::get('error') }}
        </div>
    @endif

    <div class="row text-center">
        <h1>Go back to previous tab. Thank you</h1>
    </div>
    {{-- </h5> --}}
    {{-- <form action="{{ route('processTransaction') }}" method="get">
        @csrf
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="amount" class="form-label">Enter Amt</label>
                <input type="text" class="form-control" id="amount" name="amount" placeholder="enter amount">
            </div>
            <div class="col-md-6">
                <label for="userid" class="form-label">Enter User Id</label>
                <input type="text" class="form-control" id="userid" name="userid" placeholder="enter userid">
            </div>
        </div>
        <button class="btn btn-primary btn-lg" type="submit" >PAY</button>
    </form>
    <script src="https://www.paypal.com/sdk/js?client-id={{ env('PAYPAL_SANDBOX_CLIENT_ID') }}"></script>
    <script>
        // function pay() {
        //     const form = document.querySelector('form');
        //     let fd = new FormData(form);
        //     // fd.append("_token", _token);
        //     // $.get("/api/processTransaction?amount="+document.getElementById('amt').value, data,
        //     //     function (data, textStatus, jqXHR) {
        //     //         return data;
        //     //     },
        //     //     "json"
        //     // );
        //     // $.ajax({
        //     //     type: "POST",
        //     //     url: "/api/pay",
        //     //     data: {
        //     //         amount: document.getElementById('amt').value
        //     //     },
        //     //     contentType: false,
        //     //     processData: false,
        //     //     dataType: "json",
        //     //     cache: false,
        //     //     success: function(response) {
        //     //         console.log(response);
        //     //     },
        //     //     error: function (error) {
        //     //         console.log(error.responseText);
        //     //      }
        //     // });
        // }
    </script> --}}
</body>

</html>
