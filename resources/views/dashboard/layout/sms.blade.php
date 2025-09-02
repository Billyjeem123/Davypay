<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name') . ' App Dashboard')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- App favicon -->
    <link rel="shortcut icon" href="/logo.png">


    <!-- Datatables css -->
    <link href="/assets/front-end/assets/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" type="text/css">
    <!-- For checkbox Select-->
    <link href="/assets/front-end/assets/vendor/datatables/select.bootstrap5.min.css" rel="stylesheet" type="text/css">
    <!-- For Buttons -->
    <link href="/assets/front-end/assets/vendor/datatables/buttons.bootstrap5.min.css" rel="stylesheet" type="text/css">
    <!-- Fixe header-->
    <link href="/assets/front-end/assets/vendor/datatables/fixedHeader.bootstrap5.min.css" rel="stylesheet" type="text/css">


    <!-- Daterangepicker css -->
    <link href="/assets/front-end/assets/vendor/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css">

    <!-- Vector Map css -->
    <link href="/assets/front-end/assets/vendor/jsvectormap/jsvectormap.min.css" rel="stylesheet" type="text/css">

    <!-- Theme Config Js -->
    <script src="/assets/front-end/assets/js/hyper-config.js"></script>

    <!-- Vendor css -->
    <link href="/assets/front-end/assets/css/vendor.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="/assets/front-end/assets/css/app.min.css" rel="stylesheet" type="text/css" id="app-style" />
    <link href="/assets/front-end/assets/style.css" rel="stylesheet" type="text/css" />

    <!-- Icons css -->
    <link href="/assets/front-end/assets/css/unicons/css/unicons.css" rel="stylesheet" type="text/css" />
    <link href="/assets/front-end/assets/css/remixicon/remixicon.css" rel="stylesheet" type="text/css" />
    <link href="/assets/front-end/assets/css/mdi/css/materialdesignicons.min.css" rel="stylesheet" type="text/css" />
    <!-- In the <head> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">


</head>

<body>




@include('dashboard.component.topbar')
@include('dashboard.component.sidebar')

@yield('content')


<!-- Vendor js -->
<script src="/assets/front-end/assets/js/vendor.min.js"></script>

<!-- App js -->
<script src="/assets/front-end/assets/js/app.js"></script>

<!-- Daterangepicker js -->
<script src="/assets/front-end/assets/vendor/moment/moment.min.js"></script>
<script src="/assets/front-end/assets/vendor/daterangepicker/daterangepicker.js"></script>

<!-- Apex Charts js -->
<script src="/assets/front-end/assets/vendor/apexcharts/apexcharts.min.js"></script>

<!-- Vector Map Js -->
<script src="/assets/front-end/assets/vendor/jsvectormap/jsvectormap.min.js"></script>
<script src="/assets/front-end/assets/vendor/jsvectormap/world-merc.js"></script>
<script src="/assets/front-end/assets/vendor/jsvectormap/world.js"></script>

<!-- Datatables js -->
<script src="/assets/front-end/assets/vendor/datatables/dataTables.min.js"></script>
<script src="/assets/front-end/assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script src="/assets/front-end/assets/vendor/datatables/dataTables.responsive.min.js"></script>
<script src="/assets/front-end/assets/vendor/datatables/responsive.bootstrap5.min.js"></script>
<!-- Datatable Custom js -->
<script src="/assets/front-end/assets/js/pages/demo.datatable-init.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('notification-datatable');
        if (table) {
            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable('#notification-datatable')) {
                // Destroy existing DataTable
                $('#notification-datatable').DataTable().destroy();
            }

            // Initialize with your settings
            $('#notification-datatable').DataTable({
                "order": [], // No initial sorting - respect backend order
                "ordering": false, // Disable all sorting
                "paging": true,
                "searching": true,
                "info": true
            });
        }
    });
</script>

<!-- Dashboard App js -->
<script src="/assets/front-end/assets/js/pages/demo.dashboard.js"></script>
<!-- Before closing </body> -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="/script.js"></script>

@if ($errors->any())
    <script>
        @foreach ($errors->all() as $error)
        toastr.error(@json($error));
        @endforeach
    </script>
@endif
<script>



    @if (session('success'))
    toastr.success("{{ session('success') }}");
    @endif

    @if (session('error'))
    toastr.error("{{ session('error') }}");
    @endif

    @if (session('info'))
    toastr.info("{{ session('info') }}");
    @endif

    @if (session('warning'))
    toastr.warning("{{ session('warning') }}");
    @endif
</script>

</body>



</html>
