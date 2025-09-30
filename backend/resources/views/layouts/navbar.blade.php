
   <div class="l-navbar" id="navbar">
    <div class=".nav">
      <!-- Logo+Text -->
      <a href="#" class="nav-logo-container">
        {{-- <img src="{{asset('public/logo_2x.png')}}" alt=""> --}}
        <span class="logo-text">Etnatribe</span>
      </a>
      <!-- Toggle -->
      <div class="nav-toggle" id="nav-toggle">
        <i class="ri-arrow-right-line" style="color:white"></i>
      </div>
      <!-- List -->
      @guest
      <ul class="nav-list">
     {{--    <a href="{{route('register')}}" class="nav-link">
         <i class="far fa-address-card icon" style="color:white"></i>
          <span class="link-text">Registrati</span>
        </a> --}}

        <a href="{{route('login')}}" class="nav-link">
         <i class="fas fa-sign-in-alt icon" style="color:white"></i>
          <span class="link-text">Login</span>
        </a>
      @else
      
        <a href="{{route('home')}}" class="nav-link">
          <i class="fa fa-folder-open icon" style="color:white"></i>
          <span class="link-text">Homepage</span>
        </a>
      
        <a href="{{route('users.users')}}" class="nav-link">
          <i class="fas fa-users icon" style="color:white"></i>
          <span class="link-text">Gestione Utenti</span>
        </a>

        <a href="{{route('restaurant.create')}}" class="nav-link">
          <i class="fas fa-utensils icon" style="color:white"></i>
          <span class="link-text">Crea Ristorante</span>
        </a>

        <a href="" class="nav-link">
          <i class="fas fa-clipboard icon" style="color:white"></i>
          <span class="link-text">Segnalazioni</span>
        </a>
        <a href="" class="nav-link">
         <i class="fas fa-wrench icon" style="color:white"></i>
         <span class="link-text">Operazioni</span>
       </a>
       <a href="" class="nav-link">
         <i class="fas fa-car-crash icon" style="color:white"></i>
         <span class="link-text">Assicurazioni</span>
       </a>
       <a href="{{route('logout')}}" class="nav-link" onclick="event.preventDefault();document.getElementById('logout').submit();">
        <i class="icon ri-shut-down-line icon"></i>
        <span class="link-text">Logout</span>
     </a>
     <form method="POST" action="{{route('logout')}}" id="logout">
       @csrf
     </form>
     
      @endguest
    </div>
  </div>



