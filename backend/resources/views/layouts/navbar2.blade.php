<style>
  .numberCircle {
    border-radius: 50%;
    min-width: 26px;
    max-width: 26px;
    height: 26px;
    justify-content: center;
    align-items: center;
    padding: 3px;
    background: red;
    border: 2px solid red;
    margin-right: -2.1rem;
    margin-top: -2.9rem;
    color: white;
    text-align: center;
}
</style>
<input class="navcheck" type="checkbox" id="nav-toggle" checked>
<div class="sidebar" style="overflow-y:auto">
    <div class="sidebar-brand" id="sidebarbrand" hidden style="margin-botom: 10rem;">
      <h1><img src="{{asset('storage/img/enoteca1.png')}}" alt="" style="width: 160px;height:200px;margin-left:2rem;">
      </h1>
    </div>
    @if (Auth::user()->admin==1 || Auth::user()->moderator== 1)
    <div class="sidebar-menu" style="margin-top: 10rem;">
      <ul>
        <li>
          <a href="{{route('home')}}" class="nav-link p-3">
            <span class="fas fa-tachometer-alt"></span>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="{{route('users.showadmin')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-users" ></span>
            <span>Admin/Mod</span>
          </a>
        </li>
        <li>
          <a href="{{route('waiters.showwaiters')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-concierge-bell" ></span>
            <span>Camerieri</span>
          </a>
        </li>
        <li>
          <a href="{{route('restaurant.restaurant')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-utensils" ></span>
            <span>Ristoranti</span>
          </a>
        </li>
        <li>
          <a href="{{route('ordini.ordini')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-dolly"></span>
            <span>Ordini</span>
          </a>
        </li>
        @if (Auth::user()->admin==1)
        <li>
          <a href="{{route('wines.wines')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-wine-bottle"></span>
            <span>Vini</span>
          </a>
        </li>
        <li>
          <a href="{{route('statistiche.stat')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-chart-line"></span>
            <span>Statistiche</span>
          </a>
        </li>
        @endif
        <li>
          <a href="{{route('logout')}}" class="nav-link p-2" onclick="event.preventDefault();document.getElementById('logout').submit();" style="margin-top:-1rem;">
            <i class="icon ri-shut-down-line"></i>
            <span class="link-text">Logout</span>
         </a>
         <form method="POST" action="{{route('logout')}}" id="logout">
           @csrf
         </form>
      </ul>

    </div>

    @else
    <div class="sidebar-menu mt-5" style="margin-top: 10rem!important;">
      <ul>
        <li>
          <a href="{{route('home')}}" class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-tachometer-alt"></span>
            <span>Dashboard</span>
          </a>
        </li>
{{--         <li>
          <a href=""class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-euro-sign" ></span>
            <span>Provvigioni</span>
          </a>
        </li> --}}
        <li>
          <a href="{{route('sales.sales')}}"class="nav-link p-3" style="margin-top:-1rem;">
            <span class="fas fa-shopping-cart"></span>
            <span>Vendite</span>
          </a>
        </li>
        <li>
          <a href="{{route('logout')}}" class="nav-link p-3" onclick="event.preventDefault();document.getElementById('logout').submit();" style="margin-top:-1rem;">
            <i class="icon ri-shut-down-line icon"></i>
            <span class="link-text">Logout</span>
         </a>
         <form method="POST" action="{{route('logout')}}" id="logout">
           @csrf
         </form>
      </ul>

    </div>
    @endif

</div>
<?php

if ($_SERVER["PHP_SELF"]='/index.php') {
  if (isset($_SERVER["PATH_INFO"])) {
    $path=$_SERVER["PATH_INFO"];
    $split=explode('/',$path);
    $pathpage=$split[1];
    /* dd($pathpage); */
  }else {
    $pathpage="Dashboard";
  }
}

?>
<header>
  @if ($pathpage=="users")
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Utenti
  </h2>   
  @elseif($pathpage=="restaurant")
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Ristoranti
  </h2>  
  @elseif($pathpage=="orders")
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Ordini
  </h2>
  @elseif($pathpage=="wines")
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Vini
  </h2>
  @elseif($pathpage=="statistiche")
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Statistiche
  </h2>
  @else
  <h2>
    <label for="nav-toggle">
      <span class="fas fa-bars"></span>
    </label>
    Dashboard
  </h2>
  @endif
    <?php $cart=App\Models\Cart::all()->count() ?>

    <div class="user-wrapper">
      @if (Auth::user()->admin || Auth::user()->moderator)
      @if ($cart)
      <span id="numbercart" class="numberCircle d-flex" style="font-size: 0.9rem;">{{$cart}}</span><a href="{{route('cart.cart')}}"><i class="fas fa-shopping-cart" style="font-size: 2rem;color:black;margin-right:-0.1rem;"></i></a>
      @else
      <a href="{{route('cart.cart')}}"><i class="fas fa-shopping-cart" style="font-size: 2rem;color:black;margin-right:-0.1rem;"></i></a>
      @endif
      @endif
      @if (Auth::user()->immagine!=null)
      <img src="{{asset("storage/user/".Auth::user()->id."/".Auth::user()->id.".jpg")}}" height="40px" width="40px" alt="avatar"></a>
      @else
      <img src="https://www.pngarts.com/files/10/Default-Profile-Picture-PNG-Background-Image.png" height="40px" width="40px" alt="customer"></a>
      @endif
      <div class="">
        <h4>{{Auth::user()->name}}</h4>
        @if (Auth::user()->admin==1)
        <small>Super Admin</small>
        @elseif(Auth::user()->moderator==1)
        <small>Moderatore</small>
        @elseif(Auth::user()->admin==0 && Auth::user()->moderator==0 && Auth::user()->waiters==0)
        <small>Ruolo non definito</small>
        @else
        <small>Cameriere</small>
        @endif
     </div>
    </div>
  </header>
  @if (Auth::user()->admin==1 || Auth::user()->moderator==1)
  <div class="container-xxl main-content">
    <div class="row secondarynav bg-light border shadow-md"style="margin-top:6.5rem;" hidden>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('home')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-tachometer-alt" ></span><br>
          <span>Dashboard</span>
        </a>
      </div>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('users.showadmin')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-users" ></span><br>
          <span>Admin/Mod</span>
        </a>
      </div>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('waiters.showwaiters')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-concierge-bell" ></span><br>
          <span>Camerieri</span>
        </a>
      </div>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('restaurant.restaurant')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-utensils" ></span><br>
          <span>Ristoranti</span>
        </a>
      </div>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('ordini.ordini')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-shopping-cart" ></span><br>
          <span>Ordini</span>
        </a>
      </div>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('wines.wines')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-wine-bottle" ></span><br>
          <span>Vini</span>
        </a>
      </div>
    </div>
  </div> 
  @else
  <div class="container-xxl">
    <div class="row secondarynav bg-light border shadow-md"style="margin-top:6.5rem;" hidden>
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('home')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-tachometer-alt" ></span><br>
          <span>Dashboard</span>
        </a>
      </div>
{{--       <div class="col text-center"style="padding-top:10px">
        <a href="" style="color: black;text-decoration:none;">
          <span class="fas fa-euro-sign" ></span><br>
          <span>Provvigioni</span>
        </a>
      </div> --}}
      <div class="col text-center"style="padding-top:10px">
        <a href="{{route('sales.sales')}}" style="color: black;text-decoration:none;">
          <span class="fas fa-shopping-cart" ></span><br>
          <span>Vendite</span>
        </a>
      </div>

    </div>
  </div> 
  @endif


