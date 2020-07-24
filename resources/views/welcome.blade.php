<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>API Test Print</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">

    <script src="ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <script src="jquery.mask/1.14.15/jquery.mask.min.js"></script>

    <script src="sweetalert2/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="sweetalert2/sweetalert2.min.css">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="content">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="row">
                <div class="col-sm-12 col-md-5 col-lg-4 col-xl-3 mx-auto">
                    <div class="">
                        <h1>API Test Print</h1>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>
    
        <!-- Main content -->
        <section class="content">
            <div class="row">
                <!-- left column -->
                <div class="col-sm-12 col-md-5 col-lg-4 col-xl-3 mx-auto">
                    <!-- general form elements -->
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Test</h3>
                        </div>
                        <!-- /.card-header -->
                        <!-- form start -->
                        <form role="form" method="POST" action="/test">
                            {{ csrf_field() }}
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="ip_address">IP Address</label>
                                    <input type="text" name="ip_address" value="192.168.0.201" value="{{ old('ip_address') }}" class="form-control ip_address @error('ip_address') is-invalid @enderror" placeholder="Enter IP... Ex 192.168.0.100" minlength="7" maxlength="15" size="15" pattern="^((\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$">
                                </div>
                                <div class="form-group">
                                    <label for="text">Text</label>
                                    <textarea name="text" onfocus="this.innerHTML='{{ old('text') }}'" class="form-control text @error('text') is-invalid @enderror" placeholder="{{ old('text') }}">Texto de prueba                                        
                                    </textarea>
                                </div>
                            </div>
                            <!-- /.card-body -->
            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <script src="jquery.mask/1.14.15/jquery.mask.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $('.ip_address').mask('0ZZ.0ZZ.0ZZ.0ZZ', {
                translation: {
                    'Z': {
                        pattern: /[0-9]/, optional: true
                    }
                }
            });
            $('.ip_address').mask('099.099.099.099');
            //$('.ip_address').val('192.168.0.100');
        });

        @if($errors->any())
        @endif

        @if($errors->any())
            @if ($errors->has('connection'))
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: "{{ $errors->first('connection') }}",
                })
            @endif
        @endif
    </script>
</body>
</html>
