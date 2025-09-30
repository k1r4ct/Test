{{-- <link rel="stylesheet" href="{{asset('css/app.css')}}"> --}}
<style>
    :root {
        --background: #121212;
        --card: #181818;
        --divider: #212121;
        --sidebar: #181818;
        --primary-text: #d1d5db;
        --secondary-text: #9ca3af;
        --accent: #0275d8;
    }

    .pagination {
        justify-content: center !important;
    }

    .page-item.active .page-link {
        z-index: 3;
        color: #fff;
        background-color: #11101d !important;
        border-color: #11101d !important;
        border-radius: 10%;

    }

    .page-link {
        text-decoration: none !important;
    }

    .card:hover {
        transform: none !important;

    }

    .chart-wrapper {
        position: relative;
        top: 50%;
        left: 50%;
        width: 100%;
        transform: translate(-50%, -50%);
        padding: 30px;
        border-radius: 15px;
        background: #fff;
        color: var(--primary-text);
        -webkit-box-shadow: 5px 5px 29px -12px rgba(0, 0, 0, 0.41);
        box-shadow: 5px 5px 29px -12px rgba(0, 0, 0, 0.41);
    }

    .chart-wrapper h2 {
        margin: 0;
    }
</style>
@php
    $customerall = 0;
    $customer = 0;
    $wines = 0;
    $winesrestaurant = 0;
    $order = 0;
    $ordersrestaurantcount = 0;
    $totalguadagnoetna = 0;
    $totalguadagno = 0;
    $totalespesa = 0;
    $orders = 0;
    $user = 0;
@endphp
<x-layout>
    @if (Auth::user()->Role->descrizione == 'Administrator' || Auth::user()->moderator == 1)


        <div class="main-content">


            <main>
                <div class="cards">
                    <div class="card-single w-75">
                        <div>
                            @if (Auth::user()->Role->descrizione == 'Administrator')
                                <h1>{{ $customerall }}</h1>
                            @else
                                <h1>{{ $customer }}</h1>
                            @endif
                            <span>Punti Carriera</span>
                        </div>
                        <div>
                            <span class="fas fa-hands-helping"></span>
                        </div>
                    </div>
                    <div class="card-single w-75">
                        <div>
                            @if (Auth::user()->Role->descrizione == 'Administrator')
                                <h1>{{ $wines }}</h1>
                                <span>Punti Valore</span>
                            @else
                                <h1>{{ $winesrestaurant }}</h1>
                                <span>Punti Valore</span>
                            @endif
                        </div>
                        <div>
                            <span class="fas fa-hands-helping"></span>
                        </div>
                    </div>
                    <div class="card-single w-75">
                        <div>
                            @if (Auth::user()->Role->descrizione == 'Administrator')
                                <h1>{{ $wines }}</h1>
                                <span>Lavorazioni</span>
                            @else
                                <h1>{{ $winesrestaurant }}</h1>
                                <span>Lavorazioni</span>
                            @endif
                        </div>
                        <div>
                            <span class="fas fa-code-branch"></span>
                        </div>
                    </div>
                    <div class="card-single w-75">
                        <div>
                            <h1>{{ $wines }}</h1>
                            <span>Contratti</span>

                        </div>
                        <div>
                            <span class="fas fa-file-signature"></span>
                        </div>
                    </div>
                    <div class="card-single w-75">
                        <div>
                            @if (Auth::user()->Role->descrizione == 'Administrator')
                                <h1>{{ $orders }}</h1>
                            @else
                                <h1>{{ $ordersrestaurantcount }}</h1>
                            @endif
                            <span>Clienti</span>
                        </div>
                        <div>
                            <span class="fas fa-smile"></span>
                        </div>
                    </div>
                    <div class="card-single w-75">
                        @if (Auth::user()->Role->descrizione == 'Administrator')
                            <div>
                                <span>Wallet</span>
                                <h2 style="color: green">€ {{ $totalguadagnoetna }}</h2>
                            </div>
                        @elseif(Auth::user()->moderator == 1)
                            <div>
                                <span>Wallet</span>
                                @if ($totalguadagno < $totalespesa)
                                    <h2 style="color: red">€ {{ $totalguadagno }}</h2>
                                    <hr style="color: white">
                                @else
                                    <h2 style="color: green">€ {{ $totalguadagno }}</h2>
                                    <hr style="color: white">
                                @endif
                                <span>Spese dall'acquisto dei vini</span>
                                <h2 style="color: white">€ {{ $totalespesa }}</h2>
                            </div>
                        @endif

                        <div>
                            <span class="fas fa-wallet"></span>
                        </div>
                    </div>

                </div>
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <div class="recent-grid2">
                                <div class="customers2">
                                    <div class="card-calendar">
                                        <div class="card-header">



                                            <div class="card-body">
                                                <div id='calendar'></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xs-12 col-sm-12 col-md-6 col-lg-6 col-xl-6">
                            <div class="recent-grid">
                                <div class="wrapper">
                                    <div class="chart-wrapper">
                                        <div class="progress-wrapper">
                                            <h2 style="color: rgb(17 16 29)">Statistiche conversione Leads ->
                                                Cliente</h2>
                                            <canvas id="myChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="recent-grid2">
                                @if (Auth::user()->Role->descrizione == 'Administrator')
                                    <div class="customers" style="height: 200px">
                                        <div class="card">
                                            <div class="card-header">
                                                <h2>Team</h2>
                                                {{-- <a href="{{route('users.users')}}"><button>Guarda tutti <span class="fas fa-arrow-right"></span> </button></a> --}}
                                            </div>
                                            <div class="card-body">
                                                {{-- @foreach ($users as $user) --}}
                                                <div class="customer">
                                                    <div class="info">

                                                        <a href="{{-- {{ route('users.details', $user) }} --}}"> <img
                                                                src="https://www.pngarts.com/files/10/Default-Profile-Picture-PNG-Background-Image.png"
                                                                height="40px" width="40px" alt="customer">
                                                        </a>

                                                        <div>
                                                            <h4>Alessio</h4>
                                                            <small>Super Admin</small>
                                                            {{-- @if ($user->admin == 1)
                                                                @elseif($user->moderator == 1)
                                                                    <small>Moderatore</small>
                                                                @elseif($user->admin == 0 && $user->moderator == 0 && $user->waiters == 0)
                                                                    <small>Ruolo non definito</small>
                                                                @else
                                                                    <small>Cameriere</small>
                                                                @endif --}}
                                                            <h4>Semprechiaro</h4>
                                                        </div>
                                                    </div>
                                                    <div class="contact">
                                                        <span class="fas fa-user-circle"></span>
                                                        <span class="fas fa-comment"></span>
                                                        <span class="fas fa-phone-alt"></span>
                                                    </div>
                                                </div>
                                                {{-- @endforeach --}}
                                            </div>
                                        </div>

                                    </div>
                                    
                                        
                                    

                                @endif

                            </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <div class="container-xxl mt-1">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    {{-- {{ $salespaginate->links() }} --}}
                </div>
            </div>
        </div>
        </main>
        </div>
    @else
        {{-- <div class="main-content">


            <main>
                <div class="cards">

                    <div class="card-single">
                        <div>
                            <span>Mie Vendite</span>
                            <h1 style="color: #11102d;">{{ $sales }}</h1>
                        </div>
                        <div>
                            <span class="fas fa-wallet"></span>
                        </div>
                    </div>
                    <div class="card-single">
                        <div>
                            <span>Provvigioni Guadagnate</span>
                            @if ($guadagno)
                                <h1 style="color: green;">{{ $guadagno }} €</h1>
                            @else
                                <h1 style="color: green;">0 €</h1>
                            @endif
                        </div>
                        <div>
                            <span class="fas fa-euro-sign"></span>
                        </div>
                    </div>

                </div>

                <div class="recent-grid">
                    <div class="projects">
                        <div class="card">
                            <div class="card-header">
                                <h2>Vendite recenti</h2>
                                <a href=""><button>Guarda tutti <span
                                            class="fas fa-arrow-right"></span></button></a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table width="100%">
                                        <thead>
                                            <tr>
                                                <td>Data vendita</td>
                                                <td>Vino</td>
                                                <td>Quantita Bottiglie</td>
                                                <td>Quantita Bicchieri</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($salespaginate as $salespag)
                                                <tr>
                                                    <td>{{ $salespag->created_at }}</td>
                                                    <td>{{ $salespag->wine->nome }}</td>
                                                    <td>
                                                        <span class="status purple"></span>
                                                        {{ $salespag->quantita_bottiglie }}
                                                    </td>
                                                    <td>
                                                        <span class="status purple"></span>
                                                        {{ $salespag->quantita_bicchieri }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container-xxl mt-1">
                    <div class="row justify-content-center">
                        <div class="col-12 text-center">
                            
                        </div>
                    </div>
                </div>
        </div> --}}
        </main>
        </div>
    @endif

    <script>
        $(document).ready(function() {
            var calendar = $('#calendar').fullCalendar({
                editable: true,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                events: '/',
                selectable: true,
                selectHelper: true,
                select: function(start, end, allDay) {
                    var title = prompt('Event Title:');

                    if (title) {
                        var start = $.fullCalendar.formatDate(start, 'Y-MM-DD HH:mm:ss');

                        var end = $.fullCalendar.formatDate(end, 'Y-MM-DD HH:mm:ss');

                        $.ajax({
                            url: "{{ route('event.store') }}",
                            type: "POST",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                title: title,
                                start: start,
                                end: end,
                                type: 'add'
                            },
                            success: function(data) {
                                calendar.fullCalendar('refetchEvents');
                                alert("Event created");
                            }
                        })
                    }
                },
                editable: true,
                eventResize: function(event, delta) {
                    var start = $.fullCalendar.formatDate(event.start, 'Y-MM-DD HH:mm:ss');
                    var end = $.fullCalendar.formatDate(event.end, 'Y-MM-DD HH:mm:ss');
                    var title = event.title;
                    var id = event.id;
                    $.ajax({
                        url: "{{ route('event.store') }}",
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            title: title,
                            start: start,
                            end: end,
                            id: id,
                            type: 'update'
                        },
                        success: function(response) {
                            calendar.fullCalendar('refetchEvents');
                            alert("Event updated");
                        }
                    })
                },
                eventDrop: function(event, delta) {
                    var start = $.fullCalendar.formatDate(event.start, 'Y-MM-DD HH:mm:ss');
                    var end = $.fullCalendar.formatDate(event.end, 'Y-MM-DD HH:mm:ss');
                    var title = event.title;
                    var id = event.id;
                    $.ajax({
                        url: "{{ route('event.store') }}",
                        type: "POST",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            title: title,
                            start: start,
                            end: end,
                            id: id,
                            type: 'update'
                        },
                        success: function(response) {
                            calendar.fullCalendar('refetchEvents');
                            alert("Event updated");
                        }
                    })
                },
                eventClick: function(event) {
                    if (confirm("Are you sure you want to remove it?")) {
                        var id = event.id;
                        $.ajax({
                            url: "{{ route('event.store') }}",
                            type: "POST",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                id: id,
                                type: "delete"
                            },
                            success: function(response) {
                                calendar.fullCalendar('refetchEvents');
                                alert("Event deleted");
                            }
                        })
                    }
                }
            });
        });
    </script>
</x-layout>
