<x-layout>
    <style>
        .table td,
        .table th {
            padding: 25px 20px;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
        }

        .table tr:nth-child(even) {
            background: #f1f8f8;
        }

        .table tr:nth-child(4) {
            background: #5bb9c0;
            color: #fff;
        }

        .table img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .table .profile_name {
            display: flex;
            align-items: center;
            gap: 7px;
        }
    </style>
    <main class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <h1 class="mt-5">Clienti</h1>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row justify-content-start">
                <div class="col-12 text-start" style="background-color: white">
                    <label for="cerca">Cerca Cliente</label><br>
                    <button class="fas fa-search"></button>
                    <input type="text" value="" name="search" placeholder="cerca.."
                        style="border:1px solid grey;border-radius:10px;">
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row justify-content-center">
                <table class="table">
                    <thead>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Email</th>
                        <th>tipologia</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </thead>
                    <tbody>
                        {{-- @foreach ($clienti as $cliente) --}}
                        <tr>
                            <td class="profile_name">1</td>
                            <td>Alessio</td>
                            <td>Scionti</td>
                            <td>alessioscionti@gmail.com</td>
                            <td>Amministratore</td>
                            <td>Attivo</td>
                            <td>--</td>
                        </tr>
                        {{-- @endforeach --}}
                    </tbody>
                </table>

            </div>
        </div>
    </main>
</x-layout>
