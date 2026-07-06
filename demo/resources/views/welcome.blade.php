@extends('layouts.blankLayout')

@section('content')
<style>
    @media only screen and(max-width:320px){
        .ph{
            margin-left:-135px ;
        }
    }
    </style>
        <div >
            @if (Route::has('login'))
                <div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
                    @auth
                    <script type="text/javascript">
                        window.location = "{{ route('dashboard') }}";//here double curly bracket
                     </script>
                    @else
                       @include('auth\login');
                    @endauth
                </div>
            @endif
           
        </div>
@endsection
